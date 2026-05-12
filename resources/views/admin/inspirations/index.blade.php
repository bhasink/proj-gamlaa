@extends('admin.layouts.app', [
    'title'  => 'Inspirations',
    'crumbs' => [
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Inspirations'],
    ],
])

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Inspirations</h1>
            <p class="page-sub">Add, edit, arrange and manage the design library.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.inspirations.create') }}" class="btn btn--accent">+ New inspiration</a>
        </div>
    </div>

    <form class="filters" method="GET" action="{{ route('admin.inspirations.index') }}">
        <label class="filters__search">
            <input type="search" name="q" value="{{ $filters['q'] }}" data-admin-search placeholder="Search title, subtitle, or source… ( / )" autocomplete="off">
        </label>
        <select name="category" data-auto-submit aria-label="Filter by category">
            <option value="all" {{ $filters['slug'] === 'all' ? 'selected' : '' }}>All categories</option>
            @foreach($categories as $c)
                <option value="{{ $c->slug }}" {{ $filters['slug'] === $c->slug ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="status" data-auto-submit aria-label="Filter by status">
            <option value="all"       {{ $filters['status'] === 'all'       ? 'selected' : '' }}>All statuses</option>
            <option value="published" {{ $filters['status'] === 'published' ? 'selected' : '' }}>Published</option>
            <option value="draft"     {{ $filters['status'] === 'draft'     ? 'selected' : '' }}>Drafts</option>
        </select>
        <select name="sort" data-auto-submit aria-label="Sort">
            <option value="curated" {{ $filters['sort'] === 'curated' ? 'selected' : '' }}>Sort: Curated</option>
            <option value="newest"  {{ $filters['sort'] === 'newest'  ? 'selected' : '' }}>Sort: Newest</option>
            <option value="alpha"   {{ $filters['sort'] === 'alpha'   ? 'selected' : '' }}>Sort: Alphabetical</option>
            <option value="updated" {{ $filters['sort'] === 'updated' ? 'selected' : '' }}>Sort: Recently updated</option>
        </select>
        <div class="filters__spacer"></div>
        @if($filters['q'] !== '' || $filters['slug'] !== 'all' || $filters['status'] !== 'all' || $filters['sort'] !== 'curated')
            <a href="{{ route('admin.inspirations.index') }}" class="btn btn--ghost btn--sm">Clear</a>
        @endif
    </form>

    <div class="table-wrap" data-bulk-root data-bulk-url="{{ route('admin.inspirations.bulk') }}">
        <div class="table-toolbar">
            <div class="table-toolbar__lead">
                <strong>{{ $rows->total() }}</strong> result{{ $rows->total() === 1 ? '' : 's' }}
                @if($filters['slug'] !== 'all') · in <strong>{{ optional($categories->firstWhere('slug', $filters['slug']))->name ?? $filters['slug'] }}</strong> @endif
                @if($filters['status'] !== 'all') · <strong>{{ ucfirst($filters['status']) }}</strong> @endif
            </div>
            <div class="table-toolbar__bulk" data-bulk-toolbar hidden>
                <span data-bulk-count class="muted" style="font-size:12.5px;">0 selected</span>
                <button type="button" class="btn btn--ghost btn--sm" data-bulk-action="publish">Publish</button>
                <button type="button" class="btn btn--ghost btn--sm" data-bulk-action="unpublish">Move to drafts</button>
                <button type="button" class="btn btn--danger btn--sm" data-bulk-action="delete"
                        data-confirm-title="Delete inspirations?"
                        data-confirm-body="This will delete {n} inspiration(s). This cannot be undone.">Delete</button>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th class="table__cell--check"><input type="checkbox" class="table__check" data-bulk-all aria-label="Select all"></th>
                    <th style="width:24px;"></th>
                    <th class="table__cell--media"></th>
                    <th>Title</th>
                    <th style="width:160px;">Category</th>
                    <th style="width:110px;">Status</th>
                    <th style="width:90px;text-align:right;">Order</th>
                    <th class="table__cell--actions">Actions</th>
                </tr>
            </thead>
            <tbody data-reorder-root data-reorder-url="{{ route('admin.inspirations.reorder') }}">
                @forelse($rows as $row)
                    <tr data-row-id="{{ $row->id }}" draggable="true">
                        <td class="table__cell--check">
                            <input type="checkbox" class="table__check" data-bulk-id value="{{ $row->id }}" aria-label="Select row">
                        </td>
                        <td class="table__grip" title="Drag to reorder">⋮⋮</td>
                        <td class="table__cell--media">
                            <img class="table__thumb" src="{{ $row->image_url }}" alt="" loading="lazy">
                        </td>
                        <td>
                            <div class="table__title">
                                <strong><a href="{{ route('admin.inspirations.edit', $row) }}">{{ $row->title }}</a></strong>
                                <span>{{ $row->subtitle ?: 'Subtitle not added' }}</span>
                            </div>
                        </td>
                        <td>
                            @if($row->category)
                                <span class="badge badge--cat">{{ $row->category->name }}</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td class="table__cell--status">
                            <button type="button" class="toggle {{ $row->is_published ? 'is-on' : '' }}"
                                    data-toggle-publish
                                    data-toggle-url="{{ route('admin.inspirations.toggle', $row) }}"
                                    aria-label="Toggle published"></button>
                            <span data-status-badge class="badge {{ $row->is_published ? 'badge--ok' : 'badge--draft' }}">
                                {{ $row->is_published ? 'Published' : 'Draft' }}
                            </span>
                        </td>
                        <td style="text-align:right;color:var(--ink-500);font-variant-numeric:tabular-nums;">{{ $row->sort_order }}</td>
                        <td class="table__cell--actions">
                            <button type="button"
                                    class="btn btn--ghost btn--sm"
                                    data-quick-edit
                                    data-action="{{ route('admin.inspirations.update', $row) }}"
                                    data-title="{{ $row->title }}"
                                    data-subtitle="{{ $row->subtitle }}"
                                    data-category-id="{{ $row->category_id }}"
                                    data-source-url="{{ $row->source_url }}"
                                    data-source-label="{{ $row->source_label }}"
                                    data-sort-order="{{ $row->sort_order }}"
                                    data-is-published="{{ $row->is_published ? '1' : '0' }}">Edit here</button>
                            <a href="{{ route('admin.inspirations.edit', $row) }}" class="btn btn--ghost btn--sm">Open</a>
                            <form method="POST" action="{{ route('admin.inspirations.destroy', $row) }}" style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn--danger btn--sm" data-confirm-delete
                                        data-confirm-title="Delete inspiration?"
                                        data-confirm-body="“{{ $row->title }}” will be removed. This can't be undone.">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="table__empty">
                        <strong>No inspirations match these filters.</strong>
                        Try clearing the search or adding a new one.
                        <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-top:14px;">
                            @if($filters['q'] !== '')
                                <a class="btn btn--ghost btn--sm" href="{{ route('admin.inspirations.index', request()->except(['q', 'page'])) }}">Remove search</a>
                            @endif
                            @if($filters['slug'] !== 'all')
                                <a class="btn btn--ghost btn--sm" href="{{ route('admin.inspirations.index', array_merge(request()->except(['category', 'page']), ['category' => 'all'])) }}">Show all categories</a>
                            @endif
                            @if($filters['status'] !== 'all')
                                <a class="btn btn--ghost btn--sm" href="{{ route('admin.inspirations.index', array_merge(request()->except(['status', 'page']), ['status' => 'all'])) }}">Show every status</a>
                            @endif
                            <a class="btn btn--accent btn--sm" href="{{ route('admin.inspirations.create') }}">Add inspiration</a>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>

        @if($rows->hasPages())
            <div class="pager">
                <div class="pager__meta">Page {{ $rows->currentPage() }} of {{ $rows->lastPage() }} · {{ $rows->total() }} total</div>
                <div class="pager__links">{{ $rows->onEachSide(1)->links('pagination::simple-default') }}</div>
            </div>
        @endif
    </div>

    <aside class="drawer" id="quickEditDrawer" aria-hidden="true" aria-label="Edit inspiration">
        <div class="drawer__panel">
            <div class="drawer__head">
                <div>
                    <h2 class="card__title">Edit inspiration</h2>
                    <p class="page-sub" style="margin:2px 0 0;">Update the main details without leaving this list.</p>
                </div>
                <button type="button" class="btn btn--ghost btn--sm" data-drawer-close>Close</button>
            </div>
            <form method="POST" id="quickEditForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="image_url" value="">
                <div class="drawer__body">
                    <div class="field">
                        <label class="field__label" for="qe_title">Title</label>
                        <input class="input" id="qe_title" name="title" type="text" required maxlength="160">
                    </div>
                    <div class="field">
                        <label class="field__label" for="qe_subtitle">Subtitle</label>
                        <input class="input" id="qe_subtitle" name="subtitle" type="text" maxlength="240">
                    </div>
                    <div class="field">
                        <label class="field__label" for="qe_category_id">Category</label>
                        <select class="input" id="qe_category_id" name="category_id" required>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label class="field__label" for="qe_source_url">Source URL</label>
                        <input class="input" id="qe_source_url" name="source_url" type="url" maxlength="500">
                    </div>
                    <div class="field">
                        <label class="field__label" for="qe_source_label">Source label</label>
                        <input class="input" id="qe_source_label" name="source_label" type="text" maxlength="120">
                    </div>
                    <div class="field">
                        <label class="field__label" for="qe_sort_order">Sort order</label>
                        <input class="input" id="qe_sort_order" name="sort_order" type="number" min="0" max="9999">
                    </div>
                    <label class="switch">
                        <input type="hidden" name="is_published" value="0">
                        <input type="checkbox" id="qe_is_published" name="is_published" value="1">
                        <span class="knob"></span>
                        <span><span class="switch-label">Published</span><br><span class="switch-hint">Visible on the public site.</span></span>
                    </label>
                </div>
                <div class="drawer__foot">
                    <button type="button" class="btn btn--ghost" data-drawer-close>Cancel</button>
                    <button type="submit" class="btn btn--primary">Save</button>
                </div>
            </form>
        </div>
    </aside>
@endsection
