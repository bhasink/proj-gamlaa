<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Inspiration;
use Illuminate\Database\Seeder;

class InspirationSeeder extends Seeder
{
    public function run()
    {
        $pool = [
            ['title' => 'Living Within Nature', 'subtitle' => 'Organic architecture meets landscape', 'image' => '/images/design-insp/img-insp-1.png'],
            ['title' => 'Modern Workspace', 'subtitle' => 'Clean and minimal office design', 'image' => '/images/design-insp/img-insp-2.png'],
            ['title' => 'Green Interior', 'subtitle' => 'Nature inside the building', 'image' => '/images/design-insp/img-insp-3.png'],
            ['title' => 'Curated Retail', 'subtitle' => 'Where botanicals meet brand story', 'image' => '/images/design-insp/img-insp-4.png'],
            ['title' => 'Luxury Lounge', 'subtitle' => 'Soft lighting and premium feel', 'image' => '/images/design-insp/img-insp-5.png'],
            ['title' => 'Cafe Space', 'subtitle' => 'Relaxing seating with plants', 'image' => '/images/design-insp/img-insp-6.png'],
        ];

        $sources = [
            ['label' => 'ArchDaily',       'url' => 'https://www.archdaily.com/'],
            ['label' => 'Dezeen',          'url' => 'https://www.dezeen.com/'],
            ['label' => 'Design Milk',     'url' => 'https://design-milk.com/'],
            ['label' => 'Architonic',      'url' => 'https://www.architonic.com/'],
            ['label' => 'Yellowtrace',     'url' => 'https://www.yellowtrace.com.au/'],
        ];

        $categories = Category::ordered()->get();

        foreach ($categories as $category) {
            // Seed a healthy dataset so infinite scroll is obvious
            for ($i = 0; $i < 32; $i++) {
                $pick = $pool[($i + $category->id) % count($pool)];
                $src  = $sources[($i + $category->id) % count($sources)];

                Inspiration::create([
                    'category_id'  => $category->id,
                    'title'        => $pick['title'],
                    'subtitle'     => $pick['subtitle'],
                    'image_path'   => $pick['image'],
                    'source_url'   => $src['url'],
                    'source_label' => $src['label'],
                    'sort_order'   => $i,
                    'is_published' => true,
                    'published_at' => now()->subDays($i),
                ]);
            }
        }
    }
}
