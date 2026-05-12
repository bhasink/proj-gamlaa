<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inspiration;
use Tests\TestCase;

class AdminAndInspirationTest extends TestCase
{
    public function test_redirects_unauthenticated_admin_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login.show'));
    }

    public function test_logs_in_with_default_credentials(): void
    {
        $this->post(route('admin.login'), [
            'email' => config('admin.email'),
            'password' => config('admin.password'),
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_inspirations_index_requires_session(): void
    {
        $this->get(route('admin.inspirations.index'))->assertRedirect(route('admin.login.show'));

        $this->withSession(['admin.authenticated' => true])
            ->get(route('admin.inspirations.index'))
            ->assertOk();
    }

    public function test_public_api_filters_by_search_query(): void
    {
        $row = Inspiration::query()->published()->first();
        if (! $row) {
            $this->markTestSkipped('No published inspirations are seeded.');
        }

        $needle = mb_substr($row->title, 0, min(6, mb_strlen($row->title)));

        $this->getJson(route('api.inspirations.index', ['q' => $needle, 'per_page' => 3]))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'total', 'version']]);
    }

    public function test_reorder_endpoint_persists_order(): void
    {
        $rows = Inspiration::query()->limit(2)->get();
        if ($rows->count() < 2) {
            $this->markTestSkipped('At least two inspirations are required.');
        }

        $ids = $rows->pluck('id')->reverse()->values()->all();

        $this->withSession(['admin.authenticated' => true])
            ->postJson(route('admin.inspirations.reorder'), ['ids' => $ids])
            ->assertOk()
            ->assertJson(['ok' => true, 'count' => 2]);

        foreach ($ids as $position => $id) {
            $this->assertSame($position + 1, Inspiration::find($id)->sort_order);
        }
    }

    public function test_category_toggle_endpoint_works(): void
    {
        $category = Category::query()->first();
        if (! $category) {
            $this->markTestSkipped('No categories are seeded.');
        }

        $before = $category->is_active;

        $this->withSession(['admin.authenticated' => true])
            ->postJson(route('admin.categories.toggle', $category))
            ->assertOk()
            ->assertJson(['ok' => true, 'is_active' => ! $before]);

        $this->withSession(['admin.authenticated' => true])
            ->postJson(route('admin.categories.toggle', $category))
            ->assertOk()
            ->assertJson(['ok' => true, 'is_active' => $before]);
    }
}
