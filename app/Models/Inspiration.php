<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Inspiration extends Model
{
    use HasFactory;

    protected static array $imageDimensionCache = [];

    protected $fillable = [
        'category_id',
        'title',
        'subtitle',
        'image_path',
        'source_url',
        'source_label',
        'sort_order',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    protected $appends = ['image_url', 'image_sm_url', 'image_md_url', 'share_url', 'image_width', 'image_height'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getImageUrlAttribute(): string
    {
        $path = $this->image_path ?? '';

        return Str::startsWith($path, ['http://', 'https://', '/'])
            ? $path
            : asset($path);
    }

    public function getImageSmUrlAttribute(): string
    {
        return $this->variantUrl('sm');
    }

    public function getImageMdUrlAttribute(): string
    {
        return $this->variantUrl('md');
    }

    public function getShareUrlAttribute(): string
    {
        return url(route('design-inspiration.index').'?inspiration='.$this->id);
    }

    public function getImageWidthAttribute(): ?int
    {
        return $this->getImageDimensions()['width'];
    }

    public function getImageHeightAttribute(): ?int
    {
        return $this->getImageDimensions()['height'];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeForCategory($query, $categorySlug)
    {
        if (! $categorySlug || $categorySlug === 'all') {
            return $query;
        }

        return $query->whereHas('category', function ($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        });
    }

    protected function getImageDimensions(): array
    {
        $path = $this->resolveLocalImagePath();
        if (! $path) {
            return ['width' => null, 'height' => null];
        }

        if (isset(self::$imageDimensionCache[$path])) {
            return self::$imageDimensionCache[$path];
        }

        $size = @getimagesize($path);

        return self::$imageDimensionCache[$path] = [
            'width' => $size[0] ?? null,
            'height' => $size[1] ?? null,
        ];
    }

    protected function resolveLocalImagePath(): ?string
    {
        $path = $this->image_path ?? '';
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            $parsedPath = parse_url($path, PHP_URL_PATH);
            if (! is_string($parsedPath) || $parsedPath === '') {
                return null;
            }
            $path = $parsedPath;
        }

        $fullPath = public_path(ltrim($path, '/'));

        return is_file($fullPath) ? $fullPath : null;
    }

    protected function variantUrl(string $suffix): string
    {
        $path = $this->image_path ?? '';
        if (! Str::startsWith($path, '/storage/inspirations/')) {
            return $this->image_url;
        }

        $variant = preg_replace('/(\.[a-z0-9]+)$/i', '_'.$suffix.'.webp', $path);
        if (! is_string($variant) || $variant === $path) {
            return $this->image_url;
        }

        return is_file(public_path(ltrim($variant, '/'))) ? $variant : $this->image_url;
    }
}
