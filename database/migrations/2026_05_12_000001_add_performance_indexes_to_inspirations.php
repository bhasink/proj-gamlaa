<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerformanceIndexesToInspirations extends Migration
{
    public function up()
    {
        Schema::table('inspirations', function (Blueprint $table) {
            $table->index(['is_published', 'category_id', 'sort_order'], 'insp_pub_cat_sort_idx');
            $table->index('updated_at', 'insp_updated_at_idx');
        });
    }

    public function down()
    {
        Schema::table('inspirations', function (Blueprint $table) {
            $table->dropIndex('insp_pub_cat_sort_idx');
            $table->dropIndex('insp_updated_at_idx');
        });
    }
}
