<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Inspiration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class InspirationController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q'         => ['nullable', 'string', 'max:120'],
            'category'  => ['nullable', 'string', 'max:100'],
            'status'    => ['nullable', 'in:all,published,draft'],
            'sort'      => ['nullable', 'in:curated,newest,alpha,updated'],
            'page'      => ['nullable', 'integer', 'min:1'],
        ]);

        $q        = trim($validated['q']        ?? '');
        $slug     = $validated['category']      ?? 'all';
        $status   = $validated['status']        ?? 'all';
        $sort     = $validated['sort']          ?? 'curated';

        $query = Inspiration::with('category:id,name,slug');

        if ($slug !== 'all' && $slug !== '') {
            $query->whereHas('category', fn ($c) => $c->where('slug', $slug));
        }
        if ($status === 'published') {
            $query->where('is_published', true);
        } elseif ($status === 'draft') {
            $query->where('is_published', false);
        }
        if ($q !== '') {
            $needle = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
            $query->where(function ($w) use ($needle) {
                $w->where('title',         'like', $needle)
                  ->orWhere('subtitle',    'like', $needle)
                  ->orWhere('source_label','like', $needle);
            });
        }

        switch ($sort) {
            case 'newest':
                $query->orderByDesc('published_at')->orderBy('sort_order')->orderByDesc('id');
                break;
            case 'alpha':
                $query->orderBy('title')->orderBy('id');
                break;
            case 'updated':
                $query->orderByDesc('updated_at');
                break;
            case 'curated':
            default:
                $query->orderBy('sort_order')->orderByDesc('published_at')->orderBy('id');
                break;
        }

        $rows       = $query->paginate(20)->withQueryString();
        $categories = Category::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.inspirations.index', [
            'rows'            => $rows,
            'categories'      => $categories,
            'filters'         => compact('q', 'slug', 'status', 'sort'),
        ]);
    }

    public function create()
    {
        $categories = Category::orderBy('sort_order')->orderBy('name')->get();
        $inspiration = new Inspiration([
            'is_published' => true,
            'sort_order'   => 0,
        ]);
        return view('admin.inspirations.form', compact('inspiration', 'categories'));
    }

    public function edit(Inspiration $inspiration)
    {
        $categories = Category::orderBy('sort_order')->orderBy('name')->get();
        return view('admin.inspirations.form', compact('inspiration', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validateInspiration($request);
        $data['image_path'] = $this->resolveImagePath($request, null);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_published'] = (bool) ($data['is_published'] ?? false);
        if (! empty($data['is_published']) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $inspiration = Inspiration::create($data);
        $this->bumpPublicCacheVersion();

        return redirect()
            ->route('admin.inspirations.edit', $inspiration)
            ->with('flash.success', 'Inspiration created.');
    }

    public function update(Request $request, Inspiration $inspiration)
    {
        $data = $this->validateInspiration($request);
        $newPath = $this->resolveImagePath($request, $inspiration);
        if ($newPath !== null) {
            $data['image_path'] = $newPath;
        }
        $data['is_published'] = (bool) ($data['is_published'] ?? false);
        if ($data['is_published'] && ! $inspiration->published_at) {
            $data['published_at'] = now();
        }

        $inspiration->update($data);
        $this->bumpPublicCacheVersion();

        return redirect()
            ->route('admin.inspirations.edit', $inspiration)
            ->with('flash.success', 'Inspiration saved.');
    }

    public function destroy(Inspiration $inspiration)
    {
        $this->maybeDeleteUploadedImage($inspiration->image_path);
        $inspiration->delete();
        $this->bumpPublicCacheVersion();
        return redirect()
            ->route('admin.inspirations.index')
            ->with('flash.success', 'Inspiration deleted.');
    }

    public function bulk(Request $request)
    {
        $payload = $request->validate([
            'action' => ['required', 'in:publish,unpublish,delete'],
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer', 'min:1'],
        ]);

        $ids = $payload['ids'];

        if ($payload['action'] === 'delete') {
            $rows = Inspiration::whereIn('id', $ids)->get(['id', 'image_path']);
            foreach ($rows as $row) {
                $this->maybeDeleteUploadedImage($row->image_path);
            }
            Inspiration::whereIn('id', $ids)->delete();
        } else {
            $publishing = $payload['action'] === 'publish';
            $update = ['is_published' => $publishing];
            if ($publishing) {
                $update['published_at'] = now();
            }
            Inspiration::whereIn('id', $ids)->update($update);
        }
        $this->bumpPublicCacheVersion();

        return response()->json([
            'ok'      => true,
            'action'  => $payload['action'],
            'count'   => count($ids),
        ]);
    }

    public function reorder(Request $request)
    {
        $payload = $request->validate([
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer', 'min:1'],
        ]);

        foreach ($payload['ids'] as $position => $id) {
            Inspiration::where('id', $id)->update(['sort_order' => $position + 1]);
        }
        $this->bumpPublicCacheVersion();

        return response()->json(['ok' => true, 'count' => count($payload['ids'])]);
    }

    public function togglePublish(Inspiration $inspiration)
    {
        $inspiration->is_published = ! $inspiration->is_published;
        if ($inspiration->is_published && ! $inspiration->published_at) {
            $inspiration->published_at = now();
        }
        $inspiration->save();
        $this->bumpPublicCacheVersion();

        return response()->json([
            'ok'           => true,
            'is_published' => $inspiration->is_published,
            'published_at' => optional($inspiration->published_at)->toIso8601String(),
        ]);
    }

    protected function validateInspiration(Request $request): array
    {
        return $request->validate([
            'category_id'  => ['required', 'integer', 'exists:categories,id'],
            'title'        => ['required', 'string', 'max:160'],
            'subtitle'     => ['nullable', 'string', 'max:240'],
            'source_url'   => ['nullable', 'url', 'max:500'],
            'source_label' => ['nullable', 'string', 'max:120'],
            'sort_order'   => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['nullable', 'boolean'],
            'image_url'    => ['nullable', 'string', 'max:1000'],
            'image_file'   => ['nullable', 'file', 'image', 'max:6144'],
        ]);
    }

    protected function resolveImagePath(Request $request, ?Inspiration $existing): ?string
    {
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $name = Str::random(20);
            $path = $this->storeResponsiveImageSet($file->getRealPath(), 'inspirations', $name);
            if ($existing) {
                $this->maybeDeleteUploadedImage($existing->image_path);
            }
            return '/storage/'.$path;
        }

        $url = trim((string) $request->input('image_url', ''));
        if ($url !== '') {
            if ($existing && $existing->image_path !== $url) {
                $this->maybeDeleteUploadedImage($existing->image_path);
            }
            return $url;
        }

        return $existing ? null : '';
    }

    protected function maybeDeleteUploadedImage(?string $path): void
    {
        if (! $path) return;
        if (! Str::startsWith($path, '/storage/inspirations/')) return;

        $relative = Str::after($path, '/storage/');
        if (Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
        foreach (['sm', 'md'] as $suffix) {
            $variant = preg_replace('/(\.[a-z0-9]+)$/i', '_'.$suffix.'.webp', $relative);
            if (is_string($variant) && Storage::disk('public')->exists($variant)) {
                Storage::disk('public')->delete($variant);
            }
        }
    }

    protected function bumpPublicCacheVersion(): void
    {
        Cache::forever('inspirations.version', now()->timestamp);
    }

    protected function storeResponsiveImageSet(string $sourcePath, string $directory, string $baseName): string
    {
        Storage::disk('public')->makeDirectory($directory);
        $targets = [
            ''    => 2400,
            '_md' => 1200,
            '_sm' => 600,
        ];

        foreach ($targets as $suffix => $width) {
            $image = Image::make($sourcePath)->orientate();
            $image->resize($width, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $image->encode('webp', $suffix === '' ? 86 : 82);
            Storage::disk('public')->put($directory.'/'.$baseName.$suffix.'.webp', (string) $image);
            $image->destroy();
        }

        return $directory.'/'.$baseName.'.webp';
    }
}
