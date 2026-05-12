@php
    $isEdit = $inspiration->exists;
    $action = $isEdit ? route('admin.inspirations.update', $inspiration) : route('admin.inspirations.store');
@endphp

@extends('admin.layouts.app', [
    'title'  => $isEdit ? 'Edit inspiration' : 'New inspiration',
    'crumbs' => [
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Inspirations', 'url' => route('admin.inspirations.index')],
        ['label' => $isEdit ? 'Edit' : 'New'],
    ],
])

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">{{ $isEdit ? 'Edit inspiration' : 'New inspiration' }}</h1>
            <p class="page-sub">
                {{ $isEdit ? 'Update fields and republish anytime.' : 'Add a curated image to the library.' }}
                @if($isEdit) · <span class="muted">ID <span class="table__id">#{{ $inspiration->id }}</span></span> @endif
            </p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.inspirations.index') }}" class="btn btn--ghost">Back</a>
            @if($isEdit)
                <a href="{{ route('design-inspiration.index', ['inspiration' => $inspiration->id]) }}" target="_blank" rel="noopener" class="btn btn--ghost">View on site ↗</a>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" id="inspirationForm">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="form-grid">
            <div class="card">
                <div class="card__body">
                    <div class="field">
                        <label class="field__label" for="title">Title</label>
                        <input class="input" id="title" name="title" type="text" required maxlength="160"
                               value="{{ old('title', $inspiration->title) }}" placeholder="e.g. Velvet Library Lounge">
                        @error('title') <div class="field__error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="subtitle">Subtitle</label>
                        <input class="input" id="subtitle" name="subtitle" type="text" maxlength="240"
                               value="{{ old('subtitle', $inspiration->subtitle) }}" placeholder="A short one-line caption.">
                        @error('subtitle') <div class="field__error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field" data-uploader>
                        <label class="field__label">Image</label>
                        <div class="uploader">
                            <div class="uploader__preview" data-uploader-preview
                                 data-initial="{{ $inspiration->image_url }}"
                                 style="{{ $inspiration->image_url ? 'background-image: url(\''.e($inspiration->image_url).'\');' : '' }}"></div>
                            <div class="uploader__body">
                                <label class="uploader__file-btn">
                                    Upload file
                                    <input type="file" name="image_file" accept="image/*">
                                </label>
                                <span class="uploader__file-name" data-uploader-name></span>
                                <div class="uploader__hint">PNG, JPG, or WebP up to 6 MB. You can also drop an image here.</div>
                                <input type="url" name="image_url" class="uploader__url" data-uploader-url
                                       placeholder="…or paste an image URL"
                                       value="{{ old('image_url', $inspiration->image_path && \Illuminate\Support\Str::startsWith($inspiration->image_path, ['http://', 'https://']) ? $inspiration->image_path : '') }}">
                            </div>
                        </div>
                        @error('image_file') <div class="field__error">{{ $message }}</div> @enderror
                        @error('image_url') <div class="field__error">{{ $message }}</div> @enderror
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div class="field">
                            <label class="field__label" for="source_url">Source URL</label>
                            <input class="input" id="source_url" name="source_url" type="url" maxlength="500"
                                   value="{{ old('source_url', $inspiration->source_url) }}" placeholder="https://example.com/article">
                            @error('source_url') <div class="field__error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label class="field__label" for="source_label">Source label</label>
                            <input class="input" id="source_label" name="source_label" type="text" maxlength="120"
                                   value="{{ old('source_label', $inspiration->source_label) }}" placeholder="e.g. Studio name or magazine">
                            @error('source_label') <div class="field__error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="card" style="margin-bottom:14px;">
                    <div class="card__body">
                        <div class="field">
                            <label class="field__label" for="category_id">Category</label>
                            <select class="input" id="category_id" name="category_id" required>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}" {{ old('category_id', $inspiration->category_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <div class="field__error">{{ $message }}</div> @enderror
                        </div>

                        <div class="field">
                            <label class="switch">
                                <input type="hidden" name="is_published" value="0">
                                <input type="checkbox" name="is_published" value="1"
                                       {{ old('is_published', $inspiration->is_published) ? 'checked' : '' }}>
                                <span class="knob"></span>
                                <span>
                                    <span class="switch-label">Published</span><br>
                                    <span class="switch-hint">Visible on the public site.</span>
                                </span>
                            </label>
                        </div>

                        <div class="field">
                            <label class="field__label" for="sort_order">Sort order</label>
                            <input class="input" id="sort_order" name="sort_order" type="number" min="0" max="9999"
                                   value="{{ old('sort_order', $inspiration->sort_order ?? 0) }}">
                            <div class="field__hint">Lower numbers appear first within the category.</div>
                            @error('sort_order') <div class="field__error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn--primary" data-form-submit style="width:100%;justify-content:center;padding:12px;">
                    {{ $isEdit ? 'Save changes' : 'Create inspiration' }}
                </button>
                <p class="muted" style="margin-top:8px;font-size:12px;text-align:center;">
                    Or press <span class="kbd">⌘</span> <span class="kbd">S</span>.
                </p>
            </div>
        </div>
    </form>
@endsection
