@extends('admin.layouts.app', [
    'title'  => 'Dashboard',
    'crumbs' => [
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Dashboard'],
    ],
])

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Hello, {{ config('admin.name') }}.</h1>
            <p class="page-sub">Manage your content, categories, and site settings.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.inspirations.create') }}" class="btn btn--accent">+ New inspiration</a>
            <a href="{{ route('admin.categories.create') }}"    class="btn btn--ghost">+ New category</a>
        </div>
    </div>

    <div class="kpis">
        <div class="kpi">
            <div class="kpi__label">Inspirations</div>
            <div class="kpi__value">{{ $stats['inspirations'] }}</div>
            <div class="kpi__hint">{{ $stats['published'] }} live · {{ $stats['drafts'] }} drafts</div>
        </div>
        <div class="kpi">
            <div class="kpi__label">Categories</div>
            <div class="kpi__value">{{ $stats['categories'] }}</div>
            <div class="kpi__hint">{{ $stats['activeCats'] }} active in the carousel</div>
        </div>
        <div class="kpi">
            <div class="kpi__label">Published share</div>
            <div class="kpi__value">{{ $stats['inspirations'] > 0 ? round($stats['published'] / max(1, $stats['inspirations']) * 100) : 0 }}<span style="font-size:18px;color:var(--ink-400);">%</span></div>
            <div class="kpi__hint">of all inspirations are visible on the site</div>
        </div>
        <div class="kpi">
            <div class="kpi__label">Avg per category</div>
            <div class="kpi__value">{{ $stats['categories'] > 0 ? round($stats['inspirations'] / max(1, $stats['categories'])) : 0 }}</div>
            <div class="kpi__hint">healthy distribution target: 24+</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:18px;">
        <div class="card">
            <div class="card__head">
                <h2 class="card__title">Recently updated</h2>
                <a href="{{ route('admin.inspirations.index', ['sort' => 'updated']) }}" class="btn btn--ghost btn--sm">See all</a>
            </div>
            <div class="card__body" style="padding:0;">
                <table class="table">
                    <thead><tr>
                        <th class="table__cell--media"></th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th style="text-align:right;">Updated</th>
                    </tr></thead>
                    <tbody>
                        @forelse($recent as $r)
                            <tr>
                                <td class="table__cell--media">
                                    <img class="table__thumb" src="{{ $r->image_url }}" alt="" loading="lazy">
                                </td>
                                <td>
                                    <div class="table__title">
                                        <strong><a href="{{ route('admin.inspirations.edit', $r) }}">{{ $r->title }}</a></strong>
                                        <span>{{ $r->subtitle ?: '—' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge--cat">{{ $r->category?->name ?? 'Uncategorised' }}</span>
                                </td>
                                <td>
                                    @if($r->is_published)
                                        <span class="badge badge--ok">Published</span>
                                    @else
                                        <span class="badge badge--draft">Draft</span>
                                    @endif
                                </td>
                                <td style="text-align:right;color:var(--ink-500);font-size:12.5px;">{{ $r->updated_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="table__empty"><strong>No inspirations yet.</strong>Start by creating one.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card__head">
                <h2 class="card__title">By category</h2>
                <a href="{{ route('admin.categories.index') }}" class="btn btn--ghost btn--sm">Manage</a>
            </div>
            <div class="card__body" style="padding:6px 0;">
                @forelse($byCategory as $c)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 18px;border-bottom:1px solid var(--ink-100);">
                        <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                            <span class="badge badge--cat" style="flex:0 0 auto;">{{ $c->name }}</span>
                            <span class="muted" style="font-size:12.5px;">{{ $c->slug }}</span>
                        </div>
                        <strong style="font-variant-numeric:tabular-nums;">{{ $c->inspirations_count }}</strong>
                    </div>
                @empty
                    <div class="table__empty"><strong>No categories yet.</strong>Add one to start grouping inspirations.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
