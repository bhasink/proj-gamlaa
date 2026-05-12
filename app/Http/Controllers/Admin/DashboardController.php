<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Inspiration;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $stats = [
            'inspirations'  => Inspiration::count(),
            'published'     => Inspiration::where('is_published', true)->count(),
            'drafts'        => Inspiration::where('is_published', false)->count(),
            'categories'    => Category::count(),
            'activeCats'    => Category::where('is_active', true)->count(),
        ];

        $recent = Inspiration::with('category:id,name,slug')
            ->latest('updated_at')
            ->limit(8)
            ->get();

        $byCategory = Category::withCount('inspirations')
            ->orderByDesc('inspirations_count')
            ->limit(6)
            ->get();

        return view('admin.dashboard.index', compact('stats', 'recent', 'byCategory'));
    }
}
