<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Inspiration;
use Illuminate\Http\Request;

class DesignInspirationController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::active()->ordered()->get();

        $activeSlug = $request->query('category');
        $activeCategory = $activeSlug
            ? $categories->firstWhere('slug', $activeSlug)
            : null;

        $sort = $request->query('sort', 'curated');
        if (! in_array($sort, ['curated', 'newest', 'alpha'], true)) {
            $sort = 'curated';
        }

        $search = trim((string) $request->query('q', ''));
        if (mb_strlen($search) > 120) {
            $search = mb_substr($search, 0, 120);
        }

        $perPage = 12;
        $query = Inspiration::with('category')
            ->published()
            ->forCategory($activeCategory ? $activeCategory->slug : null);

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

        $firstPage = $query->paginate($perPage);

        $deepLinkInspiration = null;
        if ($request->filled('inspiration')) {
            $deepLinkInspiration = Inspiration::with('category')
                ->published()
                ->find((int) $request->input('inspiration'));
        }

        return view('design-inspiration.index', [
            'categories'         => $categories,
            'activeCategory'     => $activeCategory,
            'activeSlug'         => $activeCategory ? $activeCategory->slug : 'all',
            'sort'               => $sort,
            'query'              => $search,
            'inspirations'       => $firstPage,
            'perPage'            => $perPage,
            'deepLinkInspiration'=> $deepLinkInspiration,
        ]);
    }
}
