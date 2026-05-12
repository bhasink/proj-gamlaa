@php
    $isEdit = $category->exists;
    $action = $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store');
@endphp

@extends('admin.layouts.app', [
    'title'  => $isEdit ? 'Edit category' : 'New category',
    'crumbs' => [
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Categories', 'url' => route('admin.categories.index')],
        ['label' => $isEdit ? 'Edit' : 'New'],
    ],
])

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">{{ $isEdit ? 'Edit category' : 'New category' }}</h1>
            <p class="page-sub">{{ $isEdit ? 'Update name, slug, thumbnail, or visibility.' : 'Create a theme to group inspirations under.' }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.categories.index') }}" class="btn btn--ghost">Back</a>
        </div>
    </div>

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="form-grid">
            <div class="card">
                <div class="card__body">
                    <div class="field">
                        <label class="field__label" for="name">Name</label>
                        <input class="input" id="name" name="name" type="text" required maxlength="120"
                               value="{{ old('name', $category->name) }}" placeholder="e.g. Cafe Spaces">
                        @error('name') <div class="field__error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="slug">Slug</label>
                        <input class="input" id="slug" name="slug" type="text" maxlength="120"
                               value="{{ old('slug', $category->slug) }}" placeholder="auto-generated from name if left blank">
                        <div class="field__hint">URL-friendly identifier — letters, numbers, dashes. Leave blank to auto-fill.</div>
                        @error('slug') <div class="field__error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field" data-uploader>
                        <label class="field__label">Thumbnail</label>
                        <div class="uploader">
                            <div class="uploader__preview" data-uploader-preview
                                 data-initial="{{ $category->thumbnail_url }}"
                                 style="{{ $category->thumbnail_url ? 'background-image: url(\''.e($category->thumbnail_url).'\');' : '' }}"></div>
                            <div class="uploader__body">
                                <label class="uploader__file-btn">
                                    Upload file
                                    <input type="file" name="image_file" accept="image/*">
                                </label>
                                <span class="uploader__file-name" data-uploader-name></span>
                                <div class="uploader__hint">Used in the public carousel chip. Square works best.</div>
                                <input type="url" name="image_url" class="uploader__url" data-uploader-url
                                       placeholder="…or paste an image URL"
                                       value="{{ old('image_url', $category->thumbnail && \Illuminate\Support\Str::startsWith($category->thumbnail, ['http://', 'https://']) ? $category->thumbnail : '') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="card" style="margin-bottom:14px;">
                    <div class="card__body">
                        <div class="field">
                            <label class="switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1"
                                       {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                                <span class="knob"></span>
                                <span>
                                    <span class="switch-label">Active</span><br>
                                    <span class="switch-hint">Appears in the public carousel.</span>
                                </span>
                            </label>
                        </div>
                        <div class="field">
                            <label class="field__label" for="sort_order">Sort order</label>
                            <input class="input" id="sort_order" name="sort_order" type="number" min="0" max="9999"
                                   value="{{ old('sort_order', $category->sort_order ?? 0) }}">
                            <div class="field__hint">Lower numbers appear first.</div>
                            @error('sort_order') <div class="field__error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn--primary" data-form-submit style="width:100%;justify-content:center;padding:12px;">
                    {{ $isEdit ? 'Save changes' : 'Create category' }}
                </button>
            </div>
        </div>
    </form>
@endsection
