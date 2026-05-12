@extends('layouts.app')

@section('title', 'Design Inspiration · Gamlaa')

@push('head-styles')
    @if(isset($deepLinkInspiration) && $deepLinkInspiration)
        <meta property="og:title" content="{{ $deepLinkInspiration->title }} · Gamlaa">
        <meta property="og:description" content="{{ $deepLinkInspiration->subtitle ?: 'Design inspiration by Gamlaa.' }}">
        <meta property="og:image" content="{{ $deepLinkInspiration->image_url }}">
        <meta property="og:type" content="article">
        <meta property="og:url" content="{{ route('design-inspiration.index', ['inspiration' => $deepLinkInspiration->id]) }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif
    <style>
        .top_banner p { color: #ffffff; font-family: 'Montserrat', sans-serif; font-size: 1.4vw; }
        .top_banner h2 { line-height: 0.9 !important; }
    </style>
    <link rel="stylesheet" href="{{ versioned_asset('css/design-inspiration.css') }}">
@endpush

@section('content')
    <div class="top_banner">
        <img src="{{ asset('images/res-img/banner.jpg') }}" alt="" class="for-dsk">
        <img src="{{ asset('images/banner-mb.png') }}" alt="" class="for-mb">
        <div class="banner_content">
            <h2>Design Inspiration</h2>
            <br><br>
            <p>Ideas that help your spaces thrive — naturally.</p>
        </div>
    </div>

    <div class="design-inspirations-section"
         data-initial-category="{{ $activeSlug }}"
         data-api-url="{{ route('api.inspirations.index') }}">
        <button type="button" class="arrow left" id="prev" aria-label="Previous categories">&#10094;</button>
        <button type="button" class="arrow right" id="next" aria-label="Next categories">&#10095;</button>
        <div class="carousel-wrapper">
            <div class="carousel-track" id="track"></div>
        </div>
    </div>

    <div class="design-inspiration-text">
        <div class="design-inspiration-content">
            <p>Discover curated spaces where greenery, craft, and architecture come together. Browse by category, open any image for a closer look, and share the ones that move you.</p>
        </div>
    </div>

    <div class="design-inspiration-bgimg">

        @php
            $diActiveLabel = $activeCategory ? $activeCategory->name : 'All Inspirations';
            $diTotalCount  = $inspirations->total();
            $diActiveSort  = $sort ?? 'curated';
            $diActiveQuery = $query ?? '';
        @endphp
        <div class="di-toolbar" id="diToolbar" aria-live="polite">
            <div class="di-toolbar__lead">
                <span class="di-toolbar__pill" id="diHeadingPill">
                    <span class="di-toolbar__dot" aria-hidden="true"></span>
                    <span class="di-toolbar__label" id="diHeadingLabel">{{ $diActiveLabel }}</span>
                </span>
                <span class="di-toolbar__count" id="diHeadingCount">{{ $diTotalCount }} {{ $diTotalCount === 1 ? 'inspiration' : 'inspirations' }}</span>
            </div>

            <div class="di-toolbar__search {{ $diActiveQuery !== '' ? 'has-value' : '' }}" role="search">
                <span class="di-toolbar__search-icon" aria-hidden="true"></span>
                <input id="diSearch"
                       type="search"
                       autocomplete="off"
                       spellcheck="false"
                       placeholder="Search inspirations…"
                       aria-label="Search inspirations by title or source"
                       value="{{ $diActiveQuery }}">
                <button type="button" id="diSearchClear" class="di-toolbar__search-clear" aria-label="Clear search">×</button>
                <span class="di-toolbar__search-hint" aria-hidden="true">/</span>
            </div>

            <div class="di-toolbar__controls">
                <label class="di-toolbar__sort" for="diSort">
                    <span class="di-toolbar__sort-label">Sort</span>
                    <select id="diSort" class="di-toolbar__select" aria-label="Sort inspirations">
                        <option value="curated" @if($diActiveSort === 'curated') selected @endif>Curated</option>
                        <option value="newest"  @if($diActiveSort === 'newest')  selected @endif>Newest</option>
                        <option value="alpha"   @if($diActiveSort === 'alpha')   selected @endif>Alphabetical</option>
                    </select>
                </label>
            </div>

            <div class="di-active-filters" id="diActiveFilters" {{ $diActiveQuery === '' ? 'hidden' : '' }}>
                <span class="di-active-filters__label">Filtering by</span>
                @if($diActiveQuery !== '')
                    <span class="di-filter-chip" data-filter="search" data-value="{{ $diActiveQuery }}">
                        <span>“{{ $diActiveQuery }}”</span>
                        <button type="button" class="di-filter-chip__remove" aria-label="Remove search filter">×</button>
                    </span>
                @endif
            </div>
        </div>

        <div class="di-progress" id="diProgress" aria-hidden="true">
            <span class="di-progress__bar" id="diProgressBar"></span>
        </div>

        <div class="di-gallery"
             id="diGallery"
             data-current-page="{{ $inspirations->currentPage() }}"
             data-last-page="{{ $inspirations->lastPage() }}"
             data-per-page="{{ $perPage }}"
             data-api-url="{{ route('api.inspirations.index') }}"
             aria-live="polite">
            @foreach($inspirations as $item)
                @include('design-inspiration.partials.card', ['item' => $item])
            @endforeach
        </div>

        <div class="di-loader" id="diLoader" hidden>
            <span class="di-loader__dot"></span>
            <span class="di-loader__dot"></span>
            <span class="di-loader__dot"></span>
        </div>

        <div class="di-sentinel" id="diSentinel" aria-hidden="true"></div>

        <p class="di-end-message" id="diEndMessage" hidden><strong>That's every piece in this set.</strong> Try another category from the carousel above or change the sort.</p>
        <p class="di-empty-message" id="diEmptyMessage" hidden><strong>Nothing matched.</strong> Adjust the search, switch the sort, or pick a different category.</p>
    </div>

    <button type="button" id="diBackToTop" class="di-fab" aria-label="Back to top" tabindex="-1">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 19V5"/>
            <path d="m5 12 7-7 7 7"/>
        </svg>
    </button>

    @php
        $diCategories = collect([[
            'id'    => 'all',
            'name'  => 'All',
            'slug'  => 'all',
            'image' => null,
        ]])->merge($categories->map(fn ($c) => [
            'id'    => $c->id,
            'name'  => $c->name,
            'slug'  => $c->slug,
            'image' => $c->thumbnail_url ?: asset('images/design-insp/img-insp-1.png'),
        ]))->values();
        $diDeepLink = $deepLinkInspiration ? [
            'id'           => $deepLinkInspiration->id,
            'title'        => $deepLinkInspiration->title,
            'subtitle'     => $deepLinkInspiration->subtitle,
            'image_url'    => $deepLinkInspiration->image_url,
            'source_url'   => $deepLinkInspiration->source_url,
            'source_label' => $deepLinkInspiration->source_label,
            'share_url'    => $deepLinkInspiration->share_url,
        ] : null;
        $jsonFlags = JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
    @endphp
    <script>
        window.__DI_CATEGORIES__ = {!! json_encode($diCategories, $jsonFlags) !!};
        window.__DI_ACTIVE_SLUG__ = {!! json_encode($activeSlug, $jsonFlags) !!};
        window.__DI_DEEPLINK__    = {!! json_encode($diDeepLink, $jsonFlags) !!};
    </script>

    @include('design-inspiration.partials.lightbox')
@endsection

@push('scripts')
    <script>
        (function () {
            var track = document.getElementById("track");
            if (!track) return;

            var section = document.querySelector(".design-inspirations-section");
            var wrap = document.querySelector(".carousel-wrapper");
            var prev = document.getElementById("prev");
            var next = document.getElementById("next");
            var cats = window.__DI_CATEGORIES__ || [];
            if (!section || !wrap || !prev || !next || !cats.length) return;

            var itemsHTML = cats.map(function (c) {
                if (c.slug === 'all') {
                    return '<div class="design-inspiration-item design-inspiration-item--all" data-slug="' + c.slug + '" role="button" tabindex="0" aria-label="Show all inspirations">'
                         +   '<div class="circle"><span>All</span></div>'
                         +   '<p>All Inspirations</p>'
                         + '</div>';
                }
                return '<div class="design-inspiration-item" data-slug="' + c.slug + '">'
                     +   '<div class="circle"><img src="' + c.image + '" alt="' + c.name + '"></div>'
                     +   '<p>' + c.name + '</p>'
                     + '</div>';
            }).join('');

            track.innerHTML = itemsHTML;

            Array.from(track.querySelectorAll('.design-inspiration-item:not(.design-inspiration-item--all)')).forEach(function (chip) {
                chip.setAttribute('role', 'button');
                chip.setAttribute('tabindex', '0');
                chip.setAttribute('aria-label', (chip.querySelector('p') || {}).textContent || chip.dataset.slug.replace(/-/g, ' '));
            });

            function getStep() {
                var first = track.querySelector(".design-inspiration-item");
                if (!first) return wrap.clientWidth * 0.72;
                var styles = window.getComputedStyle(track);
                var gap = parseFloat(styles.getPropertyValue("gap")) || 0;
                return first.getBoundingClientRect().width + gap;
            }

            function hasOverflow() {
                return (wrap.scrollWidth - wrap.clientWidth) > 6;
            }

            function syncControls() {
                var overflowing = hasOverflow();
                var atStart = wrap.scrollLeft <= 6;
                var atEnd = wrap.scrollLeft >= (wrap.scrollWidth - wrap.clientWidth - 6);

                section.classList.toggle('has-overflow', overflowing);
                section.classList.toggle('is-static', !overflowing);

                prev.classList.toggle('is-disabled', !overflowing || atStart);
                next.classList.toggle('is-disabled', !overflowing || atEnd);
                prev.disabled = !overflowing || atStart;
                next.disabled = !overflowing || atEnd;
                prev.setAttribute('aria-disabled', (!overflowing || atStart).toString());
                next.setAttribute('aria-disabled', (!overflowing || atEnd).toString());
            }

            function centerChip(chip, behavior) {
                if (!chip) return;
                if (!hasOverflow()) {
                    syncControls();
                    return;
                }
                var target = chip.offsetLeft - ((wrap.clientWidth - chip.offsetWidth) / 2);
                var max = Math.max(0, wrap.scrollWidth - wrap.clientWidth);
                var left = Math.max(0, Math.min(target, max));
                if (behavior === "smooth") {
                    wrap.scrollTo({ left: left, behavior: "smooth" });
                } else {
                    wrap.scrollLeft = left;
                }

                requestAnimationFrame(syncControls);
            }

            function move(direction) {
                if (!hasOverflow()) return;
                wrap.scrollBy({ left: direction * (getStep() * 2), behavior: "smooth" });
                requestAnimationFrame(syncControls);
            }

            next.onclick = function () { move(1); };
            prev.onclick = function () { move(-1); };
            wrap.addEventListener('scroll', syncControls, { passive: true });

            window.addEventListener("resize", function () {
                var active = track.querySelector('.design-inspiration-item.is-active')
                    || track.querySelector('.design-inspiration-item[data-slug="' + (window.__DI_ACTIVE_SLUG__ || '') + '"]');
                if (active) centerChip(active);
                syncControls();
            });

            requestAnimationFrame(function () {
                var active = track.querySelector('.design-inspiration-item[data-slug="' + (window.__DI_ACTIVE_SLUG__ || '') + '"]');
                if (active) centerChip(active);
                syncControls();
                if (window.gsap) {
                    window.gsap.fromTo(track.querySelectorAll('.design-inspiration-item'),
                        { opacity: 0, y: 18, scale: 0.97 },
                        {
                            opacity: 1,
                            y: 0,
                            scale: 1,
                            duration: 0.52,
                            ease: "power3.out",
                            stagger: { each: 0.05, from: 'start' },
                            clearProps: 'transform,opacity',
                        }
                    );
                }
            });

            window.__DI_CAROUSEL__ = { track: track, wrapper: wrap, move: move, center: centerChip, sync: syncControls };
        })();

        class DIGallery {
            constructor(container) {
                this.container = container;
                this.items = new Set();
                this._settings = this._readSettings();
                window.addEventListener('resize', () => {
                    this._settings = this._readSettings();
                    this.items.forEach((item) => this._sizeFromData(item));
                }, { passive: true });

                Array.from(container.querySelectorAll('.di-item')).forEach((item) => this.register(item));
            }
            _readSettings() {
                const styles = getComputedStyle(this.container);
                return {
                    rowHeight: parseInt(styles.getPropertyValue('grid-auto-rows'), 10) || 10,
                    gap:       parseInt(styles.getPropertyValue('gap'), 10) || 18,
                };
            }
            _sizeFromData(item) {
                const { rowHeight, gap } = this._settings;
                const dataWidth  = parseInt(item.dataset.imageWidth  || '0', 10);
                const dataHeight = parseInt(item.dataset.imageHeight || '0', 10);
                if (dataWidth <= 0 || dataHeight <= 0) {
                    item.style.setProperty('--di-ar', '4 / 5');
                    return false;
                }
                const colWidth = item.clientWidth || this.container.clientWidth / Math.max(1, Math.floor(this.container.clientWidth / 280));
                const tileHeight = Math.round((colWidth * dataHeight) / dataWidth);
                if (!tileHeight) return false;
                const span = Math.ceil((tileHeight + gap) / (rowHeight + gap));
                item.style.gridRowEnd = `span ${span}`;
                item.style.setProperty('--di-ar', `${dataWidth} / ${dataHeight}`);
                return true;
            }
            _markLoaded(item) {
                item.classList.add('is-loaded');
            }
            register(item) {
                if (!item || this.items.has(item)) return;
                this.items.add(item);
                this._sizeFromData(item);
                const img = item.querySelector('img');
                if (!img) return;
                if (img.complete && img.naturalWidth > 0) {
                    this._markLoaded(item);
                } else {
                    img.addEventListener('load',  () => this._markLoaded(item), { once: true });
                    img.addEventListener('error', () => this._markLoaded(item), { once: true });
                }
            }
            forget(item) { this.items.delete(item); }
            reset() { this.items.clear(); }
        }
        window.__DI_GALLERY__ = new DIGallery(document.getElementById('diGallery'));
        const DI_REDUCED_MOTION = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        (function () {
            if (DI_REDUCED_MOTION || !window.gsap || !window.SplitText) return;
            gsap.registerPlugin(ScrollTrigger, SplitText);
            const bannerH2 = new SplitText('.top_banner .banner_content h2', { type: "words" });
            const bannerP = document.querySelector('.top_banner .banner_content p');
            gsap.set('.top_banner .banner_content h2', { opacity: 1 });
            gsap.set(bannerH2.words, { y: 40, opacity: 0 });
            gsap.to(bannerH2.words,  { y: 0,  opacity: 1, duration: 0.4, stagger: 0.2 });
            if (bannerP) {
                gsap.fromTo(bannerP,
                    { opacity: 0, y: 22 },
                    {
                        opacity: 1,
                        y: 0,
                        duration: 0.55,
                        ease: "power3.out",
                        delay: 0.28,
                        clearProps: 'transform,opacity',
                    }
                );
            }
        })();

        (function () {
            if (DI_REDUCED_MOTION || !window.gsap) return;
            const initialItems = Array.from(document.querySelectorAll('.di-item'));

            function reveal(batch) {
                const items = Array.from(batch || []);
                if (!items.length) return;
                const images = items.map((item) => item.querySelector('img')).filter(Boolean);
                gsap.set(items, { clipPath: 'inset(12% 0% 0% 0 round 24px)' });
                if (images.length) {
                    gsap.set(images, { scale: 1.06 });
                }
                gsap.fromTo(items,
                    { opacity: 0, y: 30 },
                    {
                        opacity: 1,
                        y: 0,
                        clipPath: 'inset(0% 0% 0% 0 round 24px)',
                        duration: 0.78,
                        ease: "expo.out",
                        stagger: { each: 0.045, from: 'start' },
                        overwrite: true,
                        clearProps: 'transform,opacity,clipPath',
                    }
                );
                if (images.length) {
                    gsap.to(images, {
                        scale: 1,
                        duration: 1.05,
                        ease: "power3.out",
                        stagger: { each: 0.045, from: 'start' },
                        overwrite: true,
                        clearProps: 'transform',
                    });
                }
            }

            if (initialItems.length) {
                requestAnimationFrame(function () { reveal(initialItems); });
            }

            window.__DI_REVEAL__ = function (els) {
                if (!els || !els.length) return;
                reveal(Array.from(els));
            };
        })();

        (function () {
            if (DI_REDUCED_MOTION || !window.gsap) return;
            const introWrap = document.querySelector('.design-inspiration-content');
            var intro = document.querySelector('.design-inspiration-content p');
            if (!intro || !introWrap) return;
            if (window.SplitText) {
                var split = new SplitText(intro, { type: "lines" });
                gsap.set(intro, { opacity: 1 });
                gsap.fromTo(introWrap,
                    { opacity: 0, y: 18 },
                    {
                        opacity: 1,
                        y: 0,
                        duration: 0.45,
                        ease: "power2.out",
                        scrollTrigger: { trigger: ".design-inspiration-text", start: "top 88%", toggleActions: "play none none reverse" },
                    }
                );
                gsap.fromTo(split.lines,
                    { opacity: 0, y: 22 },
                    {
                        opacity: 1,
                        y: 0,
                        duration: 0.58,
                        ease: "power3.out",
                        stagger: 0.07,
                        delay: 0.06,
                        scrollTrigger: { trigger: ".design-inspiration-text", start: "top 88%", end: "bottom 20%", toggleActions: "play none none reverse" },
                    }
                );
                return;
            }
            gsap.fromTo(introWrap,
                { opacity: 0, y: 24 },
                {
                    opacity: 1, y: 0, duration: 0.65, ease: "power3.out",
                    scrollTrigger: { trigger: ".design-inspiration-text", start: "top 88%", end: "bottom 20%", toggleActions: "play none none reverse" },
                }
            );
        })();
    </script>

    <script src="{{ versioned_asset('js/design-inspiration.js') }}"></script>
@endpush
