# Gamlaa — Laravel 8.75

`design-insp.html` has been converted into a full Laravel 8 application.
Raw HTML is preserved inside `_original-html/`. All site assets now live under `public/`.

## The page you asked for
- **URL:** `http://localhost:8000/design-inspiration`
- **Filter carousel**: click any category chip to switch the grid. URL updates (`?category=office-design`), browser back/forward works.
- **Infinite scroll**: `IntersectionObserver` triggers a paginated JSON fetch from `/api/inspirations?category=...&page=N`. Premium shimmer skeleton + bounce loader while loading.
- **Lightbox**: click any grid tile (or its share icon) to open. Keyboard: `←`, `→`, `Esc`. Click outside or the `×` to close. Source-credit pill shows when present.
- **Share buttons**: Facebook, X, WhatsApp, LinkedIn, Copy-link, plus Web Share API (auto-shown on supported devices). Each copies/shares the deep-link `?inspiration=ID`.
- **Page-level share button** (top, below the intro): tries `navigator.share()`, falls back to copying the current category-aware URL.
- **Deep-linking**: opening `/design-inspiration?inspiration=42` opens straight into the lightbox.
- **Admin upload** (step 4): DB schema, models, relationships, and image/title/subtitle/link/category fields are already in place. Only the CRUD UI is pending.

## Prerequisites
Your Mac doesn't currently have PHP/Composer. Install them:

```bash
# PHP 8.0 (matches composer.json "^7.3|^8.0")
brew install shivammathur/php/php@8.0
brew link --force --overwrite php@8.0

# Composer
brew install composer

# MySQL is already installed on your machine (verified at /opt/homebrew/bin/mysql)
brew services start mysql      # if not running
```

## One-time project setup

```bash
cd /Users/karan/Downloads/gamla-new

# 1) Copy env and generate an app key
cp .env.example .env

# 2) Install framework + packages
composer install

# 3) Generate APP_KEY
php artisan key:generate

# 4) Create the database (adjust user/pass as needed)
mysql -uroot -p -e "CREATE DATABASE IF NOT EXISTS gamlaa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 5) Edit .env if your MySQL user/pass aren't root/empty
#    DB_DATABASE=gamlaa
#    DB_USERNAME=root
#    DB_PASSWORD=

# 6) Migrate + seed categories & inspirations
php artisan migrate --seed

# 7) Serve
php artisan serve
# => http://localhost:8000/design-inspiration
```

## Data model

| Table | Columns |
| --- | --- |
| `categories` | `id`, `name`, `slug` (unique), `thumbnail`, `sort_order`, `is_active`, timestamps |
| `inspirations` | `id`, `category_id` (FK), `title`, `subtitle`, `image_path`, `source_url`, `source_label`, `sort_order`, `is_published`, `published_at`, timestamps |

Seeded with 4 categories × 32 inspirations each = 128 rows so infinite scroll is visible.

## HTTP endpoints

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/` | redirect → `/design-inspiration` |
| GET | `/design-inspiration` | the page (SSR'd first page of grid) |
| GET | `/design-inspiration?category=SLUG` | SSR with a category pre-selected |
| GET | `/design-inspiration?inspiration=ID` | opens lightbox on that item |
| GET | `/api/inspirations?category=SLUG&page=N&per_page=12` | JSON feed for infinite scroll |
| GET | `/api/inspirations/{id}` | single inspiration JSON |

## Key files

- `resources/views/design-inspiration/index.blade.php` — page
- `resources/views/design-inspiration/partials/card.blade.php` — grid tile
- `resources/views/layouts/app.blade.php` — master layout
- `resources/views/partials/header.blade.php`, `footer.blade.php`
- `public/css/design-inspiration.css` — premium additions (lightbox, loader, chips)
- `public/css/app.css` — original site CSS (relative image URLs re-pointed to `/images/`)
- `public/js/design-inspiration.js` — client logic
- `app/Http/Controllers/DesignInspirationController.php` — web controller
- `app/Http/Controllers/Api/InspirationApiController.php` — JSON feed
- `app/Models/Category.php`, `app/Models/Inspiration.php`
- `database/migrations/2024_01_01_000001_create_categories_table.php`
- `database/migrations/2024_01_01_000002_create_inspirations_table.php`
- `database/seeders/CategorySeeder.php`, `InspirationSeeder.php`

## Admin panel (step 4 — next milestone)
Everything to plug in is already modelled. Quickest path:

1. Add route group in `routes/web.php`:
   ```php
   Route::middleware(['auth'])->prefix('admin')->group(function () {
       Route::resource('inspirations', Admin\InspirationController::class);
       Route::resource('categories',   Admin\CategoryController::class);
   });
   ```
2. Generate controllers under `app/Http/Controllers/Admin/`.
3. Use `Storage::disk('public')` + `php artisan storage:link` for image uploads (`image_path` will be `/storage/inspirations/xxx.jpg`).
4. Optional quick win: use Laravel Nova or Filament for a ready admin UI.

Tell me when you want step 4 and I'll scaffold the admin with login + drag-sortable ordering.
