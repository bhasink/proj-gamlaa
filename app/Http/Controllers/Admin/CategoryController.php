<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Intervention\Image\Facades\Image;

class CategoryController extends Controller
{
    public function index()
    {
        $rows = Category::withCount('inspirations')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', compact('rows'));
    }

    public function create()
    {
        $category = new Category([
            'is_active'  => true,
            'sort_order' => 0,
        ]);
        return view('admin.categories.form', compact('category'));
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', compact('category'));
    }

    public function store(Request $request)
    {
        $data = $this->validateCategory($request, null);
        $data['thumbnail'] = $this->resolveThumbnailPath($request, null);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category = Category::create($data);
        $this->bumpPublicCacheVersion();
        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('flash.success', 'Category created.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validateCategory($request, $category);
        $newPath = $this->resolveThumbnailPath($request, $category);
        if ($newPath !== null) {
            $data['thumbnail'] = $newPath;
        }
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);
        $this->bumpPublicCacheVersion();
        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('flash.success', 'Category saved.');
    }

    public function destroy(Category $category)
    {
        if ($category->inspirations()->count() > 0) {
            return redirect()
                ->route('admin.categories.index')
                ->with('flash.error', 'Move or delete the inspirations in this category first.');
        }
        $this->maybeDeleteUploadedImage($category->thumbnail);
        $category->delete();
        $this->bumpPublicCacheVersion();
        return redirect()
            ->route('admin.categories.index')
            ->with('flash.success', 'Category deleted.');
    }

    public function reorder(Request $request)
    {
        $payload = $request->validate([
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer', 'min:1'],
        ]);

        foreach ($payload['ids'] as $position => $id) {
            Category::where('id', $id)->update(['sort_order' => $position + 1]);
        }
        $this->bumpPublicCacheVersion();
        return response()->json(['ok' => true, 'count' => count($payload['ids'])]);
    }

    public function toggleActive(Category $category)
    {
        $category->is_active = ! $category->is_active;
        $category->save();
        $this->bumpPublicCacheVersion();
        return response()->json(['ok' => true, 'is_active' => $category->is_active]);
    }

    protected function validateCategory(Request $request, ?Category $existing): array
    {
        $id = $existing?->id;
        return $request->validate([
            'name'       => ['required', 'string', 'max:120'],
            'slug'       => [
                'nullable', 'string', 'max:120', 'alpha_dash',
                Rule::unique('categories', 'slug')->ignore($id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'  => ['nullable', 'boolean'],
            'image_url'  => ['nullable', 'string', 'max:1000'],
            'image_file' => ['nullable', 'file', 'image', 'max:4096'],
        ]);
    }

    protected function resolveThumbnailPath(Request $request, ?Category $existing): ?string
    {
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $name = Str::random(20).'.webp';
            $image = Image::make($file->getRealPath())->orientate();
            $image->fit(900, 900, function ($constraint) {
                $constraint->upsize();
            });
            $image->encode('webp', 84);
            $path = 'categories/'.$name;
            Storage::disk('public')->put($path, (string) $image);
            $image->destroy();
            if ($existing) {
                $this->maybeDeleteUploadedImage($existing->thumbnail);
            }
            return '/storage/'.$path;
        }

        $url = trim((string) $request->input('image_url', ''));
        if ($url !== '') {
            if ($existing && $existing->thumbnail !== $url) {
                $this->maybeDeleteUploadedImage($existing->thumbnail);
            }
            return $url;
        }

        return $existing ? null : null;
    }

    protected function maybeDeleteUploadedImage(?string $path): void
    {
        if (! $path) return;
        if (! Str::startsWith($path, '/storage/categories/')) return;

        $relative = Str::after($path, '/storage/');
        if (Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }

    protected function bumpPublicCacheVersion(): void
    {
        Cache::forever('inspirations.version', now()->timestamp);
    }
}
