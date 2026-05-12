<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inspiration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class InspirationApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'sort'     => ['nullable', 'string', 'in:curated,newest,alpha'],
            'q'        => ['nullable', 'string', 'max:120'],
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
        ]);

        $perPage = $validated['per_page'] ?? 12;
        $slug    = $validated['category'] ?? 'all';
        $sort    = $validated['sort']     ?? 'curated';
        $search  = trim($validated['q']   ?? '');

        $query = Inspiration::with('category:id,name,slug')
            ->published()
            ->forCategory($slug);

        if ($search !== '') {
            $needle = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';
            $query->where(function ($q) use ($needle) {
                $q->where('title',        'like', $needle)
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
            case 'curated':
            default:
                $query->orderBy('sort_order')->orderByDesc('published_at')->orderBy('id');
                break;
        }

        $paginator = $query->paginate($perPage);

        $items = $paginator->getCollection()->map(fn (Inspiration $i) => [
            'id'           => $i->id,
            'title'        => $i->title,
            'subtitle'     => $i->subtitle,
            'image_url'    => $i->image_url,
            'image_sm_url' => $i->image_sm_url,
            'image_md_url' => $i->image_md_url,
            'image_width'  => $i->image_width,
            'image_height' => $i->image_height,
            'source_url'   => $i->source_url,
            'source_label' => $i->source_label,
            'share_url'    => $i->share_url,
            'category'     => $i->category ? [
                'id'   => $i->category->id,
                'name' => $i->category->name,
                'slug' => $i->category->slug,
            ] : null,
        ]);

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'has_more'     => $paginator->hasMorePages(),
                'next_page'    => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
                'category'     => $slug,
                'version'      => Cache::get('inspirations.version', 1),
            ],
        ])->header('Cache-Control', 'public, s-maxage=60, stale-while-revalidate=300');
    }

    public function show(Inspiration $inspiration): JsonResponse
    {
        $inspiration->load('category:id,name,slug');

        return response()->json([
            'data' => [
                'id'           => $inspiration->id,
                'title'        => $inspiration->title,
                'subtitle'     => $inspiration->subtitle,
                'image_url'    => $inspiration->image_url,
                'image_sm_url' => $inspiration->image_sm_url,
                'image_md_url' => $inspiration->image_md_url,
                'image_width'  => $inspiration->image_width,
                'image_height' => $inspiration->image_height,
                'source_url'   => $inspiration->source_url,
                'source_label' => $inspiration->source_label,
                'share_url'    => $inspiration->share_url,
                'category'     => $inspiration->category ? [
                    'id'   => $inspiration->category->id,
                    'name' => $inspiration->category->name,
                    'slug' => $inspiration->category->slug,
                ] : null,
            ],
        ])->header('Cache-Control', 'public, s-maxage=300, stale-while-revalidate=600');
    }
}
