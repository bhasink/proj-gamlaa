(function () {
    "use strict";

    const $  = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

    const gallery = $('#diGallery');
    if (!gallery) return;

    const API_URL  = gallery.dataset.apiUrl;
    const PER_PAGE = parseInt(gallery.dataset.perPage, 10) || 12;
    const REDUCED_MOTION = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const FILTER_STORAGE_KEY = 'gamlaa.di.filters.v1';

    const state = {
        category: window.__DI_ACTIVE_SLUG__ || 'all',
        sort:     (document.getElementById('diSort') && document.getElementById('diSort').value) || 'curated',
        query:    (document.getElementById('diSearch') && document.getElementById('diSearch').value.trim()) || '',
        page:     parseInt(gallery.dataset.currentPage, 10) || 1,
        lastPage: parseInt(gallery.dataset.lastPage, 10) || 1,
        loading:  false,
        switching: false,
        items:    [],
        scrollActive: true,
    };

    const CATS = (window.__DI_CATEGORIES__ || []).reduce((acc, c) => (acc[c.slug] = c, acc), {});

    function readPersistedFilters() {
        try { return JSON.parse(window.localStorage.getItem(FILTER_STORAGE_KEY) || '{}') || {}; }
        catch (_) { return {}; }
    }

    function persistFilters() {
        try {
            window.localStorage.setItem(FILTER_STORAGE_KEY, JSON.stringify({
                category: state.category,
                sort: state.sort,
            }));
        } catch (_) {}
    }
    const nameFor = (slug) => slug === 'all' ? 'All Inspirations' : (CATS[slug]?.name || slug);

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }
    const escapeAttr = (s) => escapeHtml(s);

    function extractData(el) {
        return {
            id:          el.dataset.id,
            image:       el.dataset.image,
            title:       el.dataset.title,
            subtitle:    el.dataset.subtitle,
            sourceUrl:   el.dataset.sourceUrl,
            sourceLabel: el.dataset.sourceLabel,
            shareUrl:    el.dataset.shareUrl,
            el,
        };
    }

    function wireCard(el) {
        if (el.dataset.wired === '1') return;
        el.dataset.wired = '1';
        state.items.push(extractData(el));

        el.addEventListener('click', (e) => {
            const shareBtn = e.target.closest('.di-share');
            if (shareBtn) {
                e.stopPropagation();
                openLightbox(el, { focusShare: true });
                return;
            }
            openLightbox(el);
        });
    }

    function buildCard(i) {
        const div = document.createElement('div');
        div.className = 'di-item';
        div.dataset.id          = i.id;
        div.dataset.image       = i.image_url;
        div.dataset.imageWidth  = i.image_width || '';
        div.dataset.imageHeight = i.image_height || '';
        div.dataset.title       = i.title || '';
        div.dataset.subtitle    = i.subtitle || '';
        div.dataset.sourceUrl   = i.source_url || '';
        div.dataset.sourceLabel = i.source_label || '';
        div.dataset.shareUrl    = i.share_url || '';
        const sm = i.image_sm_url || i.image_url;
        const md = i.image_md_url || i.image_url;
        div.innerHTML =
              `<img src="${escapeAttr(md)}" srcset="${escapeAttr(sm)} 600w, ${escapeAttr(md)} 1200w, ${escapeAttr(i.image_url)} 2400w" sizes="(max-width: 640px) 92vw, (max-width: 1100px) 45vw, 33vw" alt="${escapeAttr(i.title || '')}" width="${escapeAttr(i.image_width || '')}" height="${escapeAttr(i.image_height || '')}" loading="lazy" decoding="async">`
            + `<div class="di-overlay"></div>`
            + `<div class="di-share"><img src="/images/share.png" alt=""></div>`
            + `<div class="di-content">`
            +   `<div class="di-title">${escapeHtml(i.title || '')}</div>`
            +   `<div class="di-desc">${escapeHtml(i.subtitle || '')}</div>`
            + `</div>`;
        return div;
    }

    function revealAppended(els) {
        const arr = Array.isArray(els) ? els : [els];
        if (!arr.length) return;
        if (typeof window.__DI_REVEAL__ === 'function') {
            window.__DI_REVEAL__(arr);
            return;
        }
        if (!REDUCED_MOTION && window.gsap) {
            window.gsap.fromTo(arr,
                { opacity: 0, y: 40 },
                { opacity: 1, y: 0, duration: 0.7, ease: 'power3.out',
                  stagger: { each: 0.07, from: 'start' } }
            );
        }
    }

    const progressEl    = $('#diProgress');
    const progressBarEl = $('#diProgressBar');
    const headingPillEl = $('#diHeadingPill');
    const headingLabelEl = $('#diHeadingLabel');
    const headingCountEl = $('#diHeadingCount');
    const gridSectionEl = document.querySelector('.design-inspiration-bgimg');

    function paintActiveChips(pendingSlug = null) {
        document.querySelectorAll('.design-inspiration-item').forEach((chip) => {
            const slug = chip.dataset.slug;
            const isActive = slug === state.category;
            chip.classList.toggle('is-active', isActive);
            chip.classList.toggle('is-pending', slug === pendingSlug);
            chip.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            chip.setAttribute('aria-current', isActive ? 'true' : 'false');
        });
        if (window.__DI_CAROUSEL__ && typeof window.__DI_CAROUSEL__.sync === 'function') {
            window.__DI_CAROUSEL__.sync();
        }
    }

    function bindChipClicks() {
        const track = document.getElementById('track');
        if (!track) return;
        const activateChip = (chip) => {
            if (!chip) return;
            const slug = chip.dataset.slug || 'all';
            switchCategory(slug);
        };

        track.addEventListener('click', (e) => {
            activateChip(e.target.closest('.design-inspiration-item'));
        });

        track.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            const chip = e.target.closest('.design-inspiration-item');
            if (!chip) return;
            e.preventDefault();
            activateChip(chip);
        });
    }

    function centerChipInCarousel(chip, behavior = 'auto') {
        const carousel = window.__DI_CAROUSEL__;
        if (!carousel || typeof carousel.center !== 'function' || !chip) return;
        carousel.center(chip, behavior);
    }

    function shouldShiftViewportToGrid() {
        if (!gridSectionEl) return false;
        const rect = gridSectionEl.getBoundingClientRect();
        return rect.top > (window.innerHeight * 0.62);
    }

    function scrollToGrid({ behavior = 'auto' } = {}) {
        if (!gridSectionEl) return;
        const rect = gridSectionEl.getBoundingClientRect();
        if (rect.top >= 88 && rect.top <= (window.innerHeight * 0.55)) return;
        if (!REDUCED_MOTION && window.__lenis && typeof window.__lenis.scrollTo === 'function' && window.__lenis.options?.smoothWheel !== false) {
            window.__lenis.scrollTo(gridSectionEl, {
                offset: -88,
                duration: behavior === 'smooth' ? 1 : 0,
                immediate: false,
                lock: false,
            });
            return;
        }
        const targetTop = Math.max(0, window.scrollY + rect.top - 88);
        window.scrollTo({ top: targetTop, behavior });
    }

    function progressStart() {
        if (!progressEl || !progressBarEl) return;
        progressEl.classList.remove('is-done');
        progressEl.classList.add('is-active');
        progressBarEl.style.width = '0%';
        requestAnimationFrame(() => { progressBarEl.style.width = '72%'; });
    }
    function progressFinish() {
        if (!progressEl || !progressBarEl) return;
        progressBarEl.style.width = '100%';
        setTimeout(() => {
            progressEl.classList.remove('is-active');
            progressEl.classList.add('is-done');
            setTimeout(() => { progressBarEl.style.width = '0%'; }, 300);
        }, 180);
    }

    const toolbarEl = $('#diToolbar');

    function showHeading(slug, { count = null, loading = false } = {}) {
        if (!headingPillEl || !headingLabelEl || !headingCountEl) return;
        const name = nameFor(slug);
        headingLabelEl.textContent = name;
        headingCountEl.textContent = loading
            ? 'Updating collection…'
            : (Number.isFinite(count) ? `${count} inspiration${count === 1 ? '' : 's'}` : '');
        headingPillEl.hidden = false;
        headingPillEl.classList.toggle('is-loading', loading);
        headingPillEl.classList.add('is-visible');
        if (toolbarEl) toolbarEl.classList.toggle('is-busy', loading);
        if (!REDUCED_MOTION && window.gsap) {
            window.gsap.killTweensOf([headingLabelEl, headingCountEl]);
            window.gsap.fromTo([headingLabelEl, headingCountEl],
                { opacity: 0, y: 6 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 0.3,
                    ease: 'power2.out',
                    stagger: 0.03,
                    overwrite: true,
                    clearProps: 'transform,opacity',
                }
            );
        }
    }
    function hideHeading() {
        if (!headingPillEl) return;
        headingPillEl.classList.add('is-loading');
        if (toolbarEl) toolbarEl.classList.add('is-busy');
    }

    function killTileTriggers() {
        if (!window.ScrollTrigger) return;
        window.ScrollTrigger.getAll().forEach((t) => {
            if (t.trigger && t.trigger.classList && t.trigger.classList.contains('di-item')) {
                t.kill();
            }
        });
    }

    async function switchCategory(slug) {
        if (slug === state.category || state.loading || state.switching) return;
        state.switching = true;
        state.category = slug;
        persistFilters();
        const lockedHeight = Math.max(gallery.getBoundingClientRect().height, 320);
        gallery.style.minHeight = `${lockedHeight}px`;
        gallery.classList.add('is-transitioning');
        const shouldShiftViewport = shouldShiftViewportToGrid();

        const url = new URL(window.location.href);
        if (slug === 'all') url.searchParams.delete('category');
        else                url.searchParams.set('category', slug);
        url.searchParams.delete('page');
        window.history.pushState({}, '', url.toString());

        paintActiveChips(slug);
        const activeChip = document.querySelector(`.design-inspiration-item[data-slug="${CSS.escape(slug)}"]`);
        if (activeChip) centerChipInCarousel(activeChip, shouldShiftViewport ? 'auto' : 'smooth');
        hideHeading();
        hideMsg();
        if (shouldShiftViewport) {
            scrollToGrid({ behavior: 'auto' });
        }

        try {
            killTileTriggers();
            gallery.innerHTML = '';
            state.items    = [];
            state.page     = 0;
            state.lastPage = 1;

            const result = await fetchPage(1, true, 'append');
            const body = result?.body || null;

            if (sentinel && state.page < state.lastPage && !state.scrollActive) {
                scrollObserver.observe(sentinel);
                state.scrollActive = true;
            }

            paintActiveChips();
            showHeading(slug, { count: body?.meta?.total ?? null });
        } finally {
            requestAnimationFrame(() => {
                gallery.style.minHeight = '';
                gallery.classList.remove('is-transitioning');
            });
            state.switching = false;
        }
    }

    const sortSelectEl = $('#diSort');
    async function switchSort(newSort) {
        if (!newSort || newSort === state.sort) return;
        if (state.loading || state.switching) {
            if (sortSelectEl) sortSelectEl.value = state.sort;
            return;
        }
        state.switching = true;
        state.sort = newSort;
        persistFilters();

        const url = new URL(window.location.href);
        if (newSort === 'curated') url.searchParams.delete('sort');
        else                       url.searchParams.set('sort', newSort);
        url.searchParams.delete('page');
        window.history.replaceState({}, '', url.toString());

        const lockedHeight = Math.max(gallery.getBoundingClientRect().height, 320);
        gallery.style.minHeight = `${lockedHeight}px`;
        gallery.classList.add('is-transitioning');
        hideHeading();
        hideMsg();

        try {
            killTileTriggers();
            gallery.innerHTML = '';
            state.items    = [];
            state.page     = 0;
            state.lastPage = 1;

            const result = await fetchPage(1, true, 'append');
            const body = result?.body || null;

            if (sentinel && state.page < state.lastPage && !state.scrollActive) {
                scrollObserver.observe(sentinel);
                state.scrollActive = true;
            }

            showHeading(state.category, { count: body?.meta?.total ?? null });
        } finally {
            requestAnimationFrame(() => {
                gallery.style.minHeight = '';
                gallery.classList.remove('is-transitioning');
            });
            state.switching = false;
        }
    }

    const searchInputEl    = $('#diSearch');
    const searchWrapEl     = searchInputEl ? searchInputEl.closest('.di-toolbar__search') : null;
    const searchClearEl    = $('#diSearchClear');
    const activeFiltersEl  = $('#diActiveFilters');

    function syncSearchUi() {
        if (!searchWrapEl) return;
        searchWrapEl.classList.toggle('has-value', !!state.query);
        if (!activeFiltersEl) return;
        $$('.di-filter-chip[data-filter="search"]', activeFiltersEl).forEach((c) => c.remove());
        if (state.query) {
            const chip = document.createElement('span');
            chip.className = 'di-filter-chip';
            chip.dataset.filter = 'search';
            chip.dataset.value = state.query;
            chip.innerHTML = `<span>“${escapeHtml(state.query)}”</span><button type="button" class="di-filter-chip__remove" aria-label="Remove search filter">×</button>`;
            activeFiltersEl.appendChild(chip);
        }
        activeFiltersEl.hidden = !state.query;
    }

    async function switchSearch(newQuery) {
        const next = (newQuery || '').trim();
        if (next === state.query) return;
        if (state.loading || state.switching) return;
        state.switching = true;
        state.query = next;

        const url = new URL(window.location.href);
        if (next === '') url.searchParams.delete('q');
        else             url.searchParams.set('q', next);
        url.searchParams.delete('page');
        window.history.replaceState({}, '', url.toString());

        syncSearchUi();

        const lockedHeight = Math.max(gallery.getBoundingClientRect().height, 320);
        gallery.style.minHeight = `${lockedHeight}px`;
        gallery.classList.add('is-transitioning');
        hideHeading();
        hideMsg();

        try {
            killTileTriggers();
            gallery.innerHTML = '';
            state.items    = [];
            state.page     = 0;
            state.lastPage = 1;

            const result = await fetchPage(1, true, 'append');
            const body = result?.body || null;

            if (sentinel && state.page < state.lastPage && !state.scrollActive) {
                scrollObserver.observe(sentinel);
                state.scrollActive = true;
            }

            showHeading(state.category, { count: body?.meta?.total ?? null });
        } finally {
            requestAnimationFrame(() => {
                gallery.style.minHeight = '';
                gallery.classList.remove('is-transitioning');
            });
            state.switching = false;
        }
    }

    const fabEl = $('#diBackToTop');
    let fabRaf = 0;
    function evaluateFab() {
        if (!fabEl) return;
        const threshold = Math.max(window.innerHeight * 1.5, 720);
        const visible = window.scrollY > threshold;
        fabEl.classList.toggle('is-visible', visible);
        fabEl.tabIndex = visible ? 0 : -1;
    }
    function onScrollMaybeShowFab() {
        if (fabRaf) return;
        fabRaf = requestAnimationFrame(() => { fabRaf = 0; evaluateFab(); });
    }
    function scrollToTopSmooth() {
        if (!REDUCED_MOTION && window.__lenis && typeof window.__lenis.scrollTo === 'function') {
            window.__lenis.scrollTo(0, { duration: 1.1, immediate: false, lock: false });
            return;
        }
        window.scrollTo({ top: 0, behavior: REDUCED_MOTION ? 'auto' : 'smooth' });
    }

    const sentinel = $('#diSentinel');
    const loader   = $('#diLoader');
    const endMsg   = $('#diEndMessage');
    const emptyMsg = $('#diEmptyMessage');

    const scrollObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting && !state.loading && state.page < state.lastPage) {
                fetchPage(state.page + 1);
            }
        });
    }, { rootMargin: '400px 0px' });
    if (sentinel) scrollObserver.observe(sentinel);

    async function fetchPage(pageNumber, replace = false, mode = 'append') {
        loader.hidden = false;
        state.loading = true;
        try {
            const url = new URL(API_URL, window.location.origin);
            url.searchParams.set('category', state.category);
            url.searchParams.set('sort',     state.sort);
            if (state.query) url.searchParams.set('q', state.query);
            url.searchParams.set('page',     pageNumber);
            url.searchParams.set('per_page', PER_PAGE);

            const res = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const body = await res.json();

            const inserted = appendItems(body.data || [], mode);
            state.page     = body.meta?.current_page || pageNumber;
            state.lastPage = body.meta?.last_page || state.lastPage;

            if (replace && (body.data || []).length === 0) {
                emptyMsg.hidden = false;
            }
            if (!body.meta?.has_more) {
                scrollObserver.disconnect();
                state.scrollActive = false;
                if (!replace || (body.data || []).length > 0) endMsg.hidden = false;
            } else if (replace && !state.scrollActive && sentinel) {
                scrollObserver.observe(sentinel);
                state.scrollActive = true;
            }
            return { body, inserted };
        } catch (err) {
            console.error('[DI] fetch failed', err);
            endMsg.textContent = 'Something went wrong loading more items.';
            endMsg.hidden = false;
            return null;
        } finally {
            loader.hidden = true;
            state.loading = false;
        }
    }

    function appendItems(items, mode = 'append') {
        const frag = document.createDocumentFragment();
        const freshEls = [];
        items.forEach((i) => {
            const el = buildCard(i);
            if (mode === 'swap') {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.clipPath = 'inset(14% 0% 0% 0 round 24px)';
                el.classList.add('is-swapping');
            }
            frag.appendChild(el);
            freshEls.push(el);
        });
        gallery.appendChild(frag);

        freshEls.forEach((el) => {
            wireCard(el);
            if (window.__DI_GALLERY__ && typeof window.__DI_GALLERY__.register === 'function') {
                window.__DI_GALLERY__.register(el);
            }
        });

        if (mode !== 'swap') {
            revealAppended(freshEls);
        }

        return freshEls;
    }

    function hideMsg() {
        endMsg.hidden = true;
        emptyMsg.hidden = true;
    }

    const lightbox     = $('#diLightbox');
    const lbImage      = $('#diLightboxImage');
    const lbTitle      = $('#diLightboxTitle');
    const lbSubtitle   = $('#diLightboxSubtitle');
    const lbSource     = $('#diLightboxSource');
    const lbSourceLbl  = $('#diLightboxSourceLabel');
    const lbClose      = $('#diLightboxClose');
    const lbPrev       = $('#diLightboxPrev');
    const lbNext       = $('#diLightboxNext');
    const lbThumbs     = $('#diLightboxThumbs');
    const toast        = $('#diToast');

    let currentIndex = -1;
    let lastFocused  = null;

    function openLightbox(el, { focusShare = false } = {}) {
        const idx = state.items.findIndex((i) => i.el === el);
        if (idx === -1) return;
        currentIndex = idx;
        renderLightbox();
        lastFocused = document.activeElement;
        document.body.classList.add('di-lightbox-open');
        lightbox.classList.add('is-open');
        lightbox.setAttribute('aria-hidden', 'false');
        (focusShare
            ? lightbox.querySelector('[data-share="copy"]')
            : lbClose
        )?.focus({ preventScroll: true });
    }

    function closeLightbox() {
        lightbox.classList.remove('is-open');
        lightbox.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('di-lightbox-open');
        currentIndex = -1;
        if (lastFocused && typeof lastFocused.focus === 'function') {
            lastFocused.focus({ preventScroll: true });
        }
    }

    function renderLightbox() {
        const data = state.items[currentIndex];
        if (!data) return;
        lbImage.src          = data.image;
        lbImage.alt          = data.title || '';
        lbTitle.textContent  = data.title || '';
        lbSubtitle.textContent = data.subtitle || '';
        if (data.sourceUrl) {
            lbSource.hidden = false;
            lbSource.href   = data.sourceUrl;
            lbSourceLbl.textContent = data.sourceLabel || 'Source';
        } else {
            lbSource.hidden = true;
        }
        if (data.id) {
            try {
                const u = new URL(window.location.href);
                u.searchParams.set('inspiration', data.id);
                window.history.replaceState({}, '', u.toString());
            } catch (_) {}
        }
        renderLightboxNav();
        renderLightboxThumbs();
    }

    function step(delta) {
        if (currentIndex === -1) return;
        const next = currentIndex + delta;
        if (next < 0 || next >= state.items.length) return;
        currentIndex = next;
        renderLightbox();
    }

    function renderLightboxNav() {
        if (lbPrev) lbPrev.disabled = currentIndex <= 0;
        if (lbNext) lbNext.disabled = currentIndex < 0 || currentIndex >= state.items.length - 1;
    }

    function renderLightboxThumbs() {
        if (!lbThumbs) return;
        const total = state.items.length;
        if (total <= 1 || currentIndex < 0) {
            lbThumbs.innerHTML = '';
            lbThumbs.hidden = true;
            return;
        }
        const start = Math.max(0, Math.min(currentIndex - 3, total - 7));
        const end = Math.min(total, start + 7);
        lbThumbs.hidden = false;
        lbThumbs.innerHTML = state.items.slice(start, end).map((item, offset) => {
            const absoluteIndex = start + offset;
            const active = absoluteIndex === currentIndex ? ' is-active' : '';
            return `<button type="button" class="di-lightbox__thumb${active}" data-index="${absoluteIndex}" aria-label="Open ${escapeAttr(item.title || 'inspiration')}"><img src="${escapeAttr(item.image)}" alt=""></button>`;
        }).join('');
        const activeBtn = lbThumbs.querySelector('.is-active');
        activeBtn?.scrollIntoView({ inline: 'center', block: 'nearest', behavior: REDUCED_MOTION ? 'auto' : 'smooth' });
    }

    lbClose?.addEventListener('click', closeLightbox);
    lbPrev ?.addEventListener('click', () => step(-1));
    lbNext ?.addEventListener('click', () => step(1));
    lbThumbs?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-index]');
        if (!btn) return;
        const idx = parseInt(btn.dataset.index, 10);
        if (!Number.isFinite(idx) || idx < 0 || idx >= state.items.length) return;
        currentIndex = idx;
        renderLightbox();
    });
    lightbox?.addEventListener('click', (e) => { if (e.target === lightbox) closeLightbox(); });
    document.addEventListener('keydown', (e) => {
        if (!lightbox || !lightbox.classList.contains('is-open')) return;
        if (e.key === 'Escape')     closeLightbox();
        if (e.key === 'ArrowLeft')  step(-1);
        if (e.key === 'ArrowRight') step(1);
    });

    const nativeBtn = lightbox ? lightbox.querySelector('[data-share="native"]') : null;
    if (nativeBtn && typeof navigator.share === 'function') nativeBtn.hidden = false;

    function shareUrl()  { return state.items[currentIndex]?.shareUrl || window.location.href; }
    function shareText() {
        const d = state.items[currentIndex];
        return d ? `${d.title} — Gamlaa Design Inspiration` : document.title;
    }

    function buildShareHref(type, url, text) {
        const u = encodeURIComponent(url);
        const t = encodeURIComponent(text);
        switch (type) {
            case 'facebook': return `https://www.facebook.com/sharer/sharer.php?u=${u}`;
            case 'twitter':  return `https://twitter.com/intent/tweet?url=${u}&text=${t}`;
            case 'whatsapp': return `https://wa.me/?text=${t}%20${u}`;
            case 'linkedin': return `https://www.linkedin.com/sharing/share-offsite/?url=${u}`;
            default: return '';
        }
    }

    function showToast(msg) {
        if (!toast) return;
        toast.textContent = msg;
        toast.classList.add('is-visible');
        clearTimeout(showToast._t);
        showToast._t = setTimeout(() => toast.classList.remove('is-visible'), 1800);
    }

    lightbox && $$('.di-share-btn', lightbox).forEach((btn) => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const type = btn.dataset.share;
            const url  = shareUrl();
            const text = shareText();

            if (type === 'copy') {
                try {
                    await navigator.clipboard.writeText(url);
                    btn.classList.add('is-success');
                    showToast('Link copied');
                    setTimeout(() => btn.classList.remove('is-success'), 1200);
                } catch (_) { showToast('Could not copy'); }
                return;
            }
            if (type === 'native') {
                try { await navigator.share({ title: text, url }); } catch (_) {}
                return;
            }
            const href = buildShareHref(type, url, text);
            if (href) window.open(href, '_blank', 'noopener,width=640,height=620');
        });
    });

    function boot() {
        $$('.di-item', gallery).forEach(wireCard);

        bindChipClicks();
        paintActiveChips();
        if (window.__DI_CAROUSEL__ && typeof window.__DI_CAROUSEL__.sync === 'function') {
            window.__DI_CAROUSEL__.sync();
        }

        const deep = window.__DI_DEEPLINK__;
        if (deep) {
            const existing = state.items.find((i) => String(i.id) === String(deep.id));
            if (existing) {
                openLightbox(existing.el);
            } else {
                const synthetic = {
                    id: deep.id, image: deep.image_url, title: deep.title,
                    subtitle: deep.subtitle, sourceUrl: deep.source_url,
                    sourceLabel: deep.source_label, shareUrl: deep.share_url,
                    el: document.body,
                };
                state.items.unshift(synthetic);
                currentIndex = 0;
                renderLightbox();
                lastFocused = document.activeElement;
                document.body.classList.add('di-lightbox-open');
                lightbox.classList.add('is-open');
                lightbox.setAttribute('aria-hidden', 'false');
            }
        }

        if (sortSelectEl) {
            sortSelectEl.addEventListener('change', (e) => {
                switchSort(e.target.value);
            });
        }

        if (searchInputEl) {
            let searchTimer = 0;
            const scheduleSearch = (val) => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => switchSearch(val), 350);
            };
            searchInputEl.addEventListener('input', (e) => {
                const val = e.target.value;
                if (searchWrapEl) searchWrapEl.classList.toggle('has-value', !!val.trim());
                scheduleSearch(val);
            });
            searchInputEl.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchTimer);
                    switchSearch(e.target.value);
                } else if (e.key === 'Escape' && e.target.value) {
                    e.preventDefault();
                    e.target.value = '';
                    clearTimeout(searchTimer);
                    switchSearch('');
                }
            });
        }
        if (searchClearEl && searchInputEl) {
            searchClearEl.addEventListener('click', () => {
                searchInputEl.value = '';
                searchInputEl.focus();
                switchSearch('');
            });
        }
        if (activeFiltersEl) {
            activeFiltersEl.addEventListener('click', (e) => {
                const removeBtn = e.target.closest('.di-filter-chip__remove');
                if (!removeBtn) return;
                const chip = removeBtn.closest('.di-filter-chip');
                if (chip && chip.dataset.filter === 'search') {
                    if (searchInputEl) searchInputEl.value = '';
                    switchSearch('');
                }
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key !== '/' || e.metaKey || e.ctrlKey || e.altKey) return;
            const t = e.target;
            if (!t) return;
            const tag = (t.tagName || '').toLowerCase();
            if (tag === 'input' || tag === 'textarea' || t.isContentEditable) return;
            if (!searchInputEl) return;
            e.preventDefault();
            searchInputEl.focus();
            searchInputEl.select();
        });

        if (fabEl) {
            evaluateFab();
            window.addEventListener('scroll', onScrollMaybeShowFab, { passive: true });
            fabEl.addEventListener('click', scrollToTopSmooth);
        }

        const initialParams = new URL(window.location.href).searchParams;
        if (!initialParams.has('category') && !initialParams.has('sort') && !initialParams.has('q') && !deep) {
            const saved = readPersistedFilters();
            const savedCategory = saved.category && (saved.category === 'all' || CATS[saved.category]) ? saved.category : null;
            const savedSort = ['curated', 'newest', 'alpha'].includes(saved.sort) ? saved.sort : null;
            if (savedCategory && savedCategory !== state.category) {
                switchCategory(savedCategory);
            } else if (savedSort && savedSort !== state.sort) {
                if (sortSelectEl) sortSelectEl.value = savedSort;
                switchSort(savedSort);
            }
        }

        window.addEventListener('popstate', () => {
            const params = new URL(window.location.href).searchParams;
            const slug = params.get('category') || 'all';
            const sort = params.get('sort') || 'curated';
            const q    = (params.get('q') || '').trim();
            if (slug !== state.category) {
                switchCategory(slug);
            } else if (sort !== state.sort) {
                if (sortSelectEl) sortSelectEl.value = sort;
                switchSort(sort);
            } else if (q !== state.query) {
                if (searchInputEl) searchInputEl.value = q;
                switchSearch(q);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
