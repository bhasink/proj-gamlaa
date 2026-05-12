@extends('admin.layouts.app', [
    'title'  => 'Categories',
    'crumbs' => [
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Categories'],
    ],
])

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Categories</h1>
            <p class="page-sub">Group inspirations into filterable themes. Drag to reorder the public carousel.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.categories.create') }}" class="btn btn--accent">+ New category</a>
        </div>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:24px;"></th>
                    <th class="table__cell--media"></th>
                    <th>Name</th>
                    <th style="width:160px;">Slug</th>
                    <th style="width:120px;text-align:right;">Inspirations</th>
                    <th style="width:110px;">Active</th>
                    <th class="table__cell--actions">Actions</th>
                </tr>
            </thead>
            <tbody data-reorder-root data-reorder-url="{{ route('admin.categories.reorder') }}">
                @forelse($rows as $row)
                    <tr data-row-id="{{ $row->id }}" draggable="true">
                        <td class="table__grip" title="Drag to reorder">⋮⋮</td>
                        <td class="table__cell--media">
                            <img class="table__thumb"
                                 src="{{ $row->thumbnail_url ?: asset('images/design-insp/img-insp-1.png') }}"
                                 alt="" loading="lazy">
                        </td>
                        <td>
                            <div class="table__title">
                                <strong><a href="{{ route('admin.categories.edit', $row) }}">{{ $row->name }}</a></strong>
                                <span>Position {{ $row->sort_order }}</span>
                            </div>
                        </td>
                        <td><span class="table__id">{{ $row->slug }}</span></td>
                        <td style="text-align:right;font-variant-numeric:tabular-nums;">{{ $row->inspirations_count }}</td>
                        <td>
                            <button type="button" class="toggle {{ $row->is_active ? 'is-on' : '' }}"
                                    data-toggle-active
                                    data-toggle-url="{{ route('admin.categories.toggle', $row) }}"
                                    aria-label="Toggle active"></button>
                        </td>
                        <td class="table__cell--actions">
                            <a href="{{ route('admin.categories.edit', $row) }}" class="btn btn--ghost btn--sm">Edit</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $row) }}" style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn--danger btn--sm" data-confirm-delete
                                        data-confirm-title="Delete category?"
                                        data-confirm-body="“{{ $row->name }}” will be removed. The category must be empty.">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="table__empty">
                        <strong>No categories yet.</strong>
                        Categories power the public carousel — create your first one to get started.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
