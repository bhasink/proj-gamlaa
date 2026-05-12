# Gamlaa — Project Context

> **Purpose of this document.** Share this with any other engineer / AI to bring them up to speed without reading the full history. It captures architecture, the completed work, known quirks and the pending roadmap.

---

## 1. High-level brief

The original deliverable was a single static HTML file (`_original-html/design-insp.html` — preserved intact for reference) which rendered a "Design Inspiration" gallery page with a carousel of category chips, a masonry grid of images, and a lot of GSAP / ScrollTrigger / SplitText animations. The ask was to turn this into a **real Laravel 8.75 application** with:

1. A database-backed **category list** driving the chip carousel.
2. A database-backed **inspiration feed** rendered into the masonry grid.
3. **Click a chip → filter the grid** (dynamic, no full page reload).
4. **Click a tile → open a lightbox** with the image, metadata, source credit.
5. **Share** any tile (Facebook / X / WhatsApp / LinkedIn / Copy-link / Web Share API) and share the page itself.
6. **Infinite scroll** to paginate through a potentially huge catalog.
7. **Deep-link** a specific inspiration via `?inspiration=ID`.
8. **Admin panel** (upload image / title / subtitle / source link / category, drag-order) — *pending*.

All the original HTML's look-and-feel must be preserved pixel-for-pixel: header / footer / marquee / Lenis smooth scroll / all GSAP reveals.

---

## 2. Tech stack

| Layer | Choice | Version | Why |
|---|---|---|---|
| PHP runtime | Homebrew `shivammathur/php/php@8.0` | 8.0.30 | Matches the existing `composer.json` constraint `^7.3\|^8.0` |
| Framework | `laravel/framework` | 8.83.29 | Pinned by `composer.json` (`^8.75`) |
| Dependency manager | Composer | 2.9.7 | Installed via the official `getcomposer.org` installer — the brew `composer` formula conflicts with `php@8.0` |
| Database | MariaDB (drop-in MySQL) | 12.2 via brew | Already on the machine; Laravel sees it as `mysql` |
| Front-end animation | GSAP 3.13 + ScrollTrigger + SplitText + MorphSVGPlugin | CDN | Identical to the original HTML |
| Smooth scroll | `@studio-freight/lenis` 1.0.33 | CDN | Identical to the original HTML |
| Carousel lib | Swiper 11 | CDN | Used elsewhere in the original layout |
| UI helpers | jQuery 3.7.1 | CDN | Original layout uses `$(...)` for scroll/toggle handlers |

No build step. No `node_modules`. No Vite / Mix. The whole front end is plain ES2019 + CDN libs + Blade.

---

## 3. Local dev setup

The app is already installed and running on this machine.

```bash
# Start dev server if it's not running:
cd /Users/karan/Downloads/gamla-new
php artisan serve          # http://127.0.0.1:8000

# Start / stop MariaDB:
brew services start mariadb
brew services stop  mariadb
```

**DB credentials (local only, in `.env`)**

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gamlaa
DB_USERNAME=gamlaa
DB_PASSWORD=gamlaa
```

> We created a dedicated `gamlaa` MySQL user because MariaDB root authenticates via `unix_socket` plugin (not usable over TCP from PHP).

**Reset the DB from scratch**

```bash
php artisan migrate:fresh --seed
```

---

## 4. Routes

| Method | Path | Controller → method | Purpose |
|---|---|---|---|
| GET | `/` | redirect closure | → `/design-inspiration` |
| GET | `/design-inspiration` | `DesignInspirationController@index` | SSR first page, filter-aware, deep-link-aware |
| GET | `/api/inspirations` | `Api\InspirationApiController@index` | Paginated JSON feed for infinite scroll |
| GET | `/api/inspirations/{id}` | `Api\InspirationApiController@show` | Single item JSON |

Query params on `/design-inspiration` and `/api/inspirations`:

- `category=<slug>` — `hospitality-design` / `office-design` / `residential-design` / `retail-design` / `all`
- `page=<n>` — page number (API only)
- `per_page=<n>` — capped at 48 (API only)
- `inspiration=<id>` — open lightbox on load (web route only)

---

## 5. Data model

### `categories`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | display name |
| `slug` | string unique | URL slug |
| `thumbnail` | string nullable | relative `/images/...` path *or* absolute URL |
| `sort_order` | unsigned int | `asc` |
| `is_active` | boolean | defaults true |
| `created_at` / `updated_at` | timestamps | |

Scopes: `active()`, `ordered()`. Slug auto-generated from name on save.

### `inspirations`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `category_id` | FK → categories, `cascadeOnDelete` | |
| `title` | string | |
| `subtitle` | string nullable | |
| `image_path` | string | relative `/images/...` or absolute URL |
| `source_url` | string nullable | external credit |
| `source_label` | string nullable | e.g. "ArchDaily" |
| `sort_order` | unsigned int | |
| `is_published` | boolean | |
| `published_at` | timestamp nullable | used by `published()` scope |
| `created_at` / `updated_at` | timestamps | |

Scopes: `published()` (respects `published_at <= now()`), `forCategory(slug)`.

Virtual attributes: `image_url` (absolute), `share_url` (absolute deep-link to lightbox).

### Seeded content (`DatabaseSeeder`)

- 4 categories (Hospitality / Office / Residential / Retail Design), thumbnails point at `/images/design-insp/img-insp-{1..4}.png`.
- 32 inspirations **per category** = 128 rows, so infinite scroll is visible immediately.

---

## 6. File structure (relevant parts)

```
gamla-new/
├── _original-html/                 # Untouched copies of the original static HTML
│   ├── design-insp.html           # The page we converted
│   └── index.html, about.html, ...
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DesignInspirationController.php
│   │   │   └── Api/InspirationApiController.php
│   │   ├── Middleware/ (all 8 stock ones; TrustProxies uses the Illuminate built-in)
│   │   └── Kernel.php
│   └── Models/
│       ├── Category.php
│       ├── Inspiration.php
│       └── User.php             # stock, unused so far
├── bootstrap/app.php
├── config/                       # 12 stock config files + sanctum + cors
├── database/
│   ├── factories/UserFactory.php
│   ├── migrations/               # users, password_resets, failed_jobs, personal_access_tokens, categories, inspirations
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── CategorySeeder.php
│       └── InspirationSeeder.php
├── public/
│   ├── css/
│   │   ├── app.css                # Untouched original stylesheet (relative urls rewritten to /images/)
│   │   └── design-inspiration.css # Additive: chip active state, progress bar, lightbox, loader, heading pill
│   ├── js/
│   │   └── design-inspiration.js  # Dynamic behaviour layer (filter / lightbox / share / infinite scroll)
│   ├── images/                    # Moved from the original HTML root
│   ├── font/                      # Moved from the original HTML root
│   ├── index.php
│   └── .htaccess, robots.txt
├── resources/
│   └── views/
│       ├── layouts/app.blade.php  # Shared layout + all layout-level GSAP (header, footer, menu)
│       ├── partials/
│       │   ├── header.blade.php
│       │   └── footer.blade.php
│       └── design-inspiration/
│           ├── index.blade.php    # The page — markup + inline GSAP (carousel, DIGallery, banner SplitText)
│           └── partials/
│               ├── card.blade.php      # One grid tile (original markup exactly)
│               └── lightbox.blade.php  # Shared lightbox shell
├── routes/
│   ├── web.php
│   ├── api.php
│   ├── console.php
│   └── channels.php
├── storage/, vendor/, server.php, artisan, .env.example, composer.json, README.md, PROJECT_CONTEXT.md (this file)
```

---

## 7. Front-end architecture (design-insp page)

Three layers, cleanly separated:

```
┌──────────────────────────────────────────────────────────────────┐
│  layouts/app.blade.php                                           │
│  ──────────────────────                                          │
│  • Loads CDN: jQuery, Lenis, Swiper, GSAP+ScrollTrigger+         │
│    SplitText+MorphSVGPlugin                                      │
│  • Inline JS: device class, dark-header-on-scroll, Lenis RAF,    │
│    mobile-menu timeline, header intro (logo + menu stagger),     │
│    footer scroll-trigger timeline (verbatim from original HTML)  │
└──────────────────────────────────────────────────────────────────┘
          │
          ▼
┌──────────────────────────────────────────────────────────────────┐
│  design-inspiration/index.blade.php                              │
│  ──────────────────────────────────                              │
│  • Same markup as _original-html/design-insp.html                │
│    (including `.arrow`, `#track`, `.di-gallery`, `.di-item >     │
│    img` direct child — CSS depends on that selector).            │
│  • @php block exposes $categories / $deepLink to JS.             │
│  • Inline scripts (ported verbatim from the original):           │
│      – Carousel builder: clones items ×20, #prev/#next move      │
│      – `class DIGallery` for grid-row-end masonry sizing         │
│      – Banner `SplitText` stagger                                │
│      – `.di-item` per-tile scrollTrigger reveal                  │
│      – `.design-inspiration-content` scrollTrigger reveal        │
│  • The `design-inspiration.js` script is pushed LAST.            │
└──────────────────────────────────────────────────────────────────┘
          │
          ▼
┌──────────────────────────────────────────────────────────────────┐
│  public/js/design-inspiration.js                                 │
│  ───────────────────────────────                                 │
│  • Chip click → category filter (delegated on #track,            │
│    URL-synced, pushState, premium stagger-out / stagger-in)      │
│  • Tile click → lightbox (focus-trap, ←/→/Esc)                   │
│  • Share buttons (FB / X / WhatsApp / LinkedIn / Copy /          │
│    navigator.share when available)                               │
│  • Infinite scroll via IntersectionObserver → paginated fetch    │
│  • Appended tiles registered with DIGallery + given the same     │
│    reveal ScrollTrigger as initial items                         │
│  • Deep-link: `?inspiration=ID` opens lightbox on load           │
└──────────────────────────────────────────────────────────────────┘
```

Global hooks the dynamic layer depends on (all set by the page-level inline script):

- `window.__DI_CATEGORIES__` — `[{id, name, slug, image}, …]`
- `window.__DI_ACTIVE_SLUG__` — current filter (defaults to `"all"`)
- `window.__DI_DEEPLINK__` — full inspiration object or `null`
- `window.__DI_GALLERY__` — the `DIGallery` instance, exposes `.register(el)` for new items
- `window.__DI_CAROUSEL__` — `{ track, move }` if the filter layer needs to scroll the chip strip programmatically

---

## 8. What's been done ✔

- [x] Laravel 8.83.29 skeleton scaffolded (config, providers, kernels, middleware, bootstrap).
- [x] MariaDB running on port 3306, `gamlaa` DB + dedicated user created, `.env` wired.
- [x] Migrations: `users`, `password_resets`, `failed_jobs`, `personal_access_tokens`, `categories`, `inspirations`.
- [x] Seeders: 4 categories × 32 inspirations = 128 rows.
- [x] Web route `/design-inspiration` with category filter + deep-link handling.
- [x] API route `/api/inspirations` paginated + `/api/inspirations/{id}` single fetch.
- [x] Layout with CDN libs + all shared GSAP animations (header intro, mobile menu, footer scroll-trigger timeline).
- [x] Page view mirrors the original HTML exactly; original inline JS ported verbatim.
- [x] `design-inspiration.js` dynamic layer: chip filter, lightbox, share, infinite scroll, deep-link.
- [x] `design-inspiration.css` additive styles: chip active state + glint, progress bar, heading pill, lightbox, loader, empty/end messages.
- [x] HTTP smoke tests all green: `/`, `/design-inspiration`, `/design-inspiration?category=office-design`, `/api/inspirations`, `/api/inspirations/{id}`.
- [x] Important bug fixes:
    - `Fideloper\Proxy` → `Illuminate\Http\Middleware\TrustProxies`
    - `Facade::defaultAliases()` → explicit alias array (incompatible with installed 8.83 patch)
    - `@json([...])` Blade parsing issue → `@php` + `json_encode()` block
    - `.di-item__media` wrapper broke `.di-item > img` direct-child selector → removed, restored original card markup
    - `.di-loader[hidden]` was being overridden by `.di-loader { display: flex }` → `[hidden] { display: none !important }` specificity fix
    - Missing `MorphSVGPlugin` CDN → added to layout
    - Missing banner `SplitText`, footer scroll-trigger timeline, header logo + menu intro → added to layout / page

---

## 9. Known quirks / gotchas

- The PHP binary is **`/opt/homebrew/opt/php@8.0/bin/php`** — when running commands from the absolute path, use that, not `php` unless `PATH` already contains `/opt/homebrew/bin`.
- The MariaDB `root@localhost` account uses `unix_socket` auth — you can't connect as root via TCP. Use the `gamlaa` account.
- Brew's `composer` formula pulls in a newer `php` that clashes with `php@8.0`. We installed Composer via the official installer script instead.
- The dev server is `php artisan serve` which is single-threaded. For parallel requests (e.g. scrolling fast triggers multiple `/api/inspirations` calls), switch to `php -S` multi-workers or Laravel Octane.
- Laravel's view cache lives in `storage/framework/views`. Most Blade changes auto-invalidate by file-mtime; if something looks stale, run `php artisan view:clear`.

---

## 10. Roadmap / What's pending

### Admin panel (step 4 — next milestone)
1. `routes/web.php` group under `/admin` behind `auth` middleware.
2. `App\Http\Controllers\Admin\CategoryController` — CRUD (index / create / store / edit / update / destroy / reorder).
3. `App\Http\Controllers\Admin\InspirationController` — CRUD + image upload via `Storage::disk('public')`. Don't forget `php artisan storage:link` so uploaded images are web-accessible.
4. Blade admin UI (TailwindCSS or Bootstrap) OR drop in **Filament v2** (`filament/filament:^2.17`) for a zero-build admin.
5. Drag-sortable ordering using `sort_order` (e.g. SortableJS + a `POST /admin/inspirations/reorder` endpoint that takes an array of `[id, sort_order]`).
6. Authentication — Laravel 8 Breeze (`composer require laravel/breeze --dev && php artisan breeze:install`) is the fastest path.
7. Image validation: `image|mimes:jpg,jpeg,png,webp|max:6144` and store at `storage/app/public/inspirations/{yyyy}/{mm}/`.

### Other pages
All other pages in `_original-html/` (about, services, career, project, experience, contact, resource, ct) still need to be ported to Blade and wired into the layout. They don't need DB backing unless we want them CMS-editable later.

### Production hardening (later)
- Swap the dev server for nginx + php-fpm, PHP 8.1+ recommended long-term (will require reviewing deprecations — we've already patched the nullsafe-operator & request-integer ones).
- Switch `.env` `APP_DEBUG=false`, `APP_ENV=production`, `LOG_LEVEL=error`.
- `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
- CDN / S3 for user uploads — flip `FILESYSTEM_DISK=s3` and configure `AWS_*` in `.env`.
- Page-level cache for the SSR first paint (`Cache::remember('di-page-'.$slug, 600, …)`).
- Rate-limit the API endpoints (`Route::middleware('throttle:60,1')`).
- Add proper error monitoring (Sentry).

### Nice-to-have UX improvements
- Category **auto-center** in the carousel when a chip is clicked (scroll the `#track` to keep the clicked chip visually centered).
- **Masonry layout shift** on image size variance — add `aspect-ratio` hints from `Inspiration::$intrinsic_ratio` if we start storing it.
- **Search** by title/subtitle.
- **Sort** options (latest / oldest / most-shared).
- **Favourites** — a session/cookie-backed wishlist, optionally tied to a user account once auth is in.

---

## 11. Handy commands cheat sheet

```bash
# Serve (127.0.0.1:8000)
php artisan serve

# Rebuild DB
php artisan migrate:fresh --seed

# Tail app log
tail -f storage/logs/laravel.log

# Tinker (REPL)
php artisan tinker

# Check which migrations have run
php artisan migrate:status

# Show routes
php artisan route:list

# Clear everything
php artisan optimize:clear
```

---

*Last updated: performance + premium-polish pass. Next milestone: admin CRUD panel.*

---

## 12. Latest polish pass — scroll-jerk + premium feel (log)

All the following shipped in one pass to address the user's "page jerks when scrolling / still not premium" feedback:

### Performance

- **Lenis ↔ ScrollTrigger now share a single ticker** in `@/Users/karan/Downloads/gamla-new/resources/views/layouts/app.blade.php:87-99`:
  ```js
  lenis.on('scroll', ScrollTrigger.update);
  gsap.ticker.add((time) => lenis.raf(time * 1000));
  gsap.ticker.lagSmoothing(0);
  ```
  Previously Lenis ran its own `requestAnimationFrame` loop AND ScrollTrigger hooked the native `scroll` event, so every frame both fired back-to-back with stale scroll values. **This was the single biggest source of jank.**
- **`ScrollTrigger.batch('.di-item', …)`** in `@/Users/karan/Downloads/gamla-new/resources/views/design-inspiration/index.blade.php:214-245` replaces the per-tile `scrollTrigger` that used to run for *every* image. One `IntersectionObserver` for the whole grid.
- **Debounced `ScrollTrigger.refresh()`** (150 ms) after infinite-scroll appends to avoid re-laying out 100+ triggers on every page tick.
- **Removed `backdrop-filter: blur()`** from `.di-heading__pill` (it's positioned over the scrolling grid — blur on a scroll layer is the #1 compositor killer).
- **Removed redundant `will-change`** from chip circle; left only on `.di-item.is-swapping` (a short-lived state cleared by JS `onComplete`).

### Premium feel

- **Sticky filter carousel** (`@/Users/karan/Downloads/gamla-new/public/css/design-inspiration.css:59-74`) — chips now pin to the top as the grid scrolls, with a subtle shadow + brighter background applied via the existing `.scrolling-down` / `.scrolling-up` body classes.
- **Progress bar + heading pill** — a slim 3 px shimmering gradient at the top of the grid plus a "VIEWING: OFFICE DESIGN"-style uppercase pill while fetching. Both orchestrated from `@/Users/karan/Downloads/gamla-new/public/js/design-inspiration.js:186-238`.
- **Blur-up image placeholder** — `.di-item` now has a neutral sage gradient background; `<img>` fades + sharpens in after browser decode (class `is-loaded` toggled by `DIGallery.markLoaded()`). Combined with `loading="lazy" decoding="async"` on every `<img>` (both Blade and JS template) — massively improves perceived performance.
- **Tile hover micro-zoom** — image scales to 1.04 under the overlay on hover (`.di-item:hover > img`), giving a gallery-grade sense of depth without layout shift.
- **Active chip glow + glint** — triple drop-shadow ring + rotating conic-gradient glint (`.design-inspiration-item.is-active .circle::after`).
- **Unified stagger-in on category swap** — old tiles fade + blur + scale out with a randomised stagger, new ones fade + unblur + scale in from `blur(8px) → 0`. See `appendItems(items, 'swap')` in `@/Users/karan/Downloads/gamla-new/public/js/design-inspiration.js:344-358`.
- **Centered-chip auto-scroll** — the carousel track smoothly re-translates so the clicked chip lands in the center of the viewport (`centerChipInCarousel` in `@/Users/karan/Downloads/gamla-new/public/js/design-inspiration.js:145-154`).
- **Refined lightbox typography** — Montserrat 22 px bold heading, max 60 char measure on the body, better contrast ratios.

### Bugs fixed

- **Loader stuck after end-of-feed** — `.di-loader { display: flex }` was winning over `[hidden]`; added `.di-loader[hidden] { display:none !important }` as the canonical fix.
- **Scroll observer never reconnected** after a category that filled in one page — now tracked via `state.scrollActive` in the JS state machine.
- **Image over-reveal** when swapping categories — cleared stale `ScrollTrigger` instances attached to DOM-removed items via `killTileTriggers()` before inserting new tiles.
