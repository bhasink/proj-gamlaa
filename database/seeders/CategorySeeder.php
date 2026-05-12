<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Hospitality Design',
                'slug' => 'hospitality-design',
                'thumbnail' => '/images/design-insp/img-insp-1.png',
                'sort_order' => 1,
            ],
            [
                'name' => 'Office Design',
                'slug' => 'office-design',
                'thumbnail' => '/images/design-insp/img-insp-2.png',
                'sort_order' => 2,
            ],
            [
                'name' => 'Residential Design',
                'slug' => 'residential-design',
                'thumbnail' => '/images/design-insp/img-insp-3.png',
                'sort_order' => 3,
            ],
            [
                'name' => 'Retail Design',
                'slug' => 'retail-design',
                'thumbnail' => '/images/design-insp/img-insp-4.png',
                'sort_order' => 4,
            ],
        ];

        foreach ($categories as $data) {
            Category::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
