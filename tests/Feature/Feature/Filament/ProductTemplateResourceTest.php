<?php

namespace Tests\Feature\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\ProductTemplate;

class ProductTemplateResourceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_product_template()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $template = ProductTemplate::factory()->make();

        $response = $this->post(route('filament.admin.resources.product-templates.create'), [
            'name' => $template->name,
            'unit' => $template->unit,
            'description' => $template->description,
        ]);

        $response->assertStatus(302); // redirect after create
        $this->assertDatabaseHas('product_templates', [
            'name' => $template->name,
        ]);
    }

    public function test_admin_can_update_product_template()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $template = ProductTemplate::factory()->create();

        $response = $this->put(route('filament.admin.resources.product-templates.edit', $template), [
            'name' => 'Updated Name',
            'unit' => $template->unit,
            'description' => $template->description,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('product_templates', [
            'id' => $template->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_admin_can_delete_product_template()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $template = ProductTemplate::factory()->create();

        $response = $this->delete(route('filament.admin.resources.product-templates.delete', $template));
        $response->assertStatus(302);
        $this->assertDatabaseMissing('product_templates', [
            'id' => $template->id,
        ]);
    }

    public function test_can_add_attribute_to_template()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $template = ProductTemplate::factory()->create();

        $attributeData = [
            'name' => 'Length',
            'variable' => 'length',
            'type' => 'number',
            'unit' => 'мм',
            'is_required' => true,
            'is_in_formula' => true,
            'sort_order' => 1,
        ];

        $response = $this->post(route('filament.admin.resources.product-templates.attributes.store', $template), $attributeData);
        $response->assertStatus(302);
        $this->assertDatabaseHas('product_attributes', [
            'product_template_id' => $template->id,
            'name' => 'Length',
        ]);
    }

    public function test_attributes_are_sorted_by_sort_order()
    {
        $template = ProductTemplate::factory()->create();
        $template->attributes()->createMany([
            ['name' => 'A', 'variable' => 'a', 'type' => 'number', 'sort_order' => 2],
            ['name' => 'B', 'variable' => 'b', 'type' => 'number', 'sort_order' => 1],
        ]);
        $sorted = $template->attributes()->orderBy('sort_order')->pluck('name')->toArray();
        $this->assertEquals(['B', 'A'], $sorted);
    }
}
