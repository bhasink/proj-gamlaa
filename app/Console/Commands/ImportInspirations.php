<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Inspiration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ImportInspirations extends Command
{
    protected $signature = 'inspirations:import {path : CSV path} {--dry-run : Validate and preview without writing}';

    protected $description = 'Import design inspirations from CSV. Columns: category,title,subtitle,image_url,source_url,source_label,sort_order,is_published';

    public function handle(): int
    {
        $path = $this->argument('path');
        if (! is_string($path) || ! is_file($path)) {
            $this->error('CSV file not found: '.$path);
            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            $this->error('Unable to open CSV file.');
            return self::FAILURE;
        }

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            $this->error('CSV is empty.');
            fclose($handle);
            return self::FAILURE;
        }
        $header = array_map(fn ($h) => Str::snake(trim((string) $h)), $header);

        $required = ['category', 'title', 'image_url'];
        foreach ($required as $col) {
            if (! in_array($col, $header, true)) {
                $this->error('Missing required column: '.$col);
                fclose($handle);
                return self::FAILURE;
            }
        }

        $dryRun = (bool) $this->option('dry-run');
        $created = 0;
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            $data = array_combine($header, array_pad($row, count($header), null));
            if (! is_array($data)) {
                $this->warn("Line {$line}: skipped malformed row.");
                continue;
            }

            $categoryKey = trim((string) ($data['category'] ?? ''));
            $category = Category::where('slug', $categoryKey)->orWhere('name', $categoryKey)->first();
            if (! $category) {
                $this->warn("Line {$line}: category not found ({$categoryKey}).");
                continue;
            }

            $title = trim((string) ($data['title'] ?? ''));
            $image = trim((string) ($data['image_url'] ?? ''));
            if ($title === '' || $image === '') {
                $this->warn("Line {$line}: title/image_url required.");
                continue;
            }

            $payload = [
                'category_id'  => $category->id,
                'title'        => $title,
                'subtitle'     => trim((string) ($data['subtitle'] ?? '')) ?: null,
                'image_path'   => $image,
                'source_url'   => trim((string) ($data['source_url'] ?? '')) ?: null,
                'source_label' => trim((string) ($data['source_label'] ?? '')) ?: null,
                'sort_order'   => (int) ($data['sort_order'] ?? 0),
                'is_published' => filter_var($data['is_published'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
                'published_at' => now(),
            ];

            if ($dryRun) {
                $this->line("DRY line {$line}: {$payload['title']} → {$category->slug}");
            } else {
                Inspiration::create($payload);
            }
            $created++;
        }

        fclose($handle);

        if (! $dryRun && $created > 0) {
            Cache::forever('inspirations.version', now()->timestamp);
        }

        $this->info(($dryRun ? 'Validated ' : 'Imported ').$created.' inspiration(s).');
        return self::SUCCESS;
    }
}
