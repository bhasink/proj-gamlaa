@php
    $current = request()->route()?->getName() ?? '';
    $isActive = fn ($prefix) => str_starts_with($current, $prefix);
@endphp
<aside class="sidebar">
    <div class="sidebar__brand">
        <span class="sidebar__brand-dot"></span>
        Gamlaa <span style="opacity:.5;font-weight:500;">/ Admin</span>
    </div>

    <div class="sidebar__group">
        <div class="sidebar__group-label">Overview</div>
        <nav class="sidebar__nav">
            <a href="{{ route('admin.dashboard') }}" class="{{ $current === 'admin.dashboard' ? 'is-active' : '' }}">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12 12 4l9 8"/><path d="M5 10v10h4v-6h6v6h4V10"/></svg>
                Dashboard
            </a>
        </nav>
    </div>

    <div class="sidebar__group">
        <div class="sidebar__group-label">Library</div>
        <nav class="sidebar__nav">
            <a href="{{ route('admin.inspirations.index') }}" class="{{ $isActive('admin.inspirations') ? 'is-active' : '' }}">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.4"/><rect x="14" y="3" width="7" height="7" rx="1.4"/><rect x="3" y="14" width="7" height="7" rx="1.4"/><rect x="14" y="14" width="7" height="7" rx="1.4"/></svg>
                Inspirations
            </a>
            <a href="{{ route('admin.categories.index') }}" class="{{ $isActive('admin.categories') ? 'is-active' : '' }}">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18"/><path d="M3 12h18"/><path d="M3 17h12"/></svg>
                Categories
            </a>
        </nav>
    </div>

    <div class="sidebar__group">
        <div class="sidebar__group-label">External</div>
        <nav class="sidebar__nav">
            <a href="{{ route('design-inspiration.index') }}" target="_blank" rel="noopener">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7"/><path d="M9 7h8v8"/></svg>
                View public site
            </a>
        </nav>
    </div>

    <div class="sidebar__footer">
        Press <span class="kbd">/</span> to search, <span class="kbd">⌘S</span> to save.
    </div>
</aside>
