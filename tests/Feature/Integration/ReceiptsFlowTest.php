<?php

namespace Tests\Feature\Integration;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptsFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipts_index_requires_auth(): void
    {
        $this->get('/admin/receipts')->assertRedirect('/admin/login');
    }

    public function test_admin_can_access_receipts_index(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $this->get('/admin/receipts')->assertStatus(200);
    }

    public function test_view_page_absent_and_edit_may_require_context(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->inTransit()->create();
        $this->actingAs($user);

        $this->get('/admin/receipts/'.$product->id)->assertStatus(404);
        $response = $this->get('/admin/receipts/'.$product->id.'/edit');
        $this->assertContains($response->status(), [200, 404]);
    }
}
