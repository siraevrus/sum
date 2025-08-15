<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTemplateApiTest extends TestCase
{
	use RefreshDatabase;

	private User $admin;
	private string $token;

	protected function setUp(): void
	{
		parent::setUp();

		$this->admin = User::factory()->create(['role' => 'admin']);
		$this->token = $this->admin->createToken('test-token')->plainTextToken;
	}

	public function test_can_list_templates()
	{
		ProductTemplate::factory()->count(2)->create();

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->getJson('/api/product-templates');

		$response->assertStatus(200)
				->assertJsonStructure(['success', 'data', 'pagination']);
	}

	public function test_can_show_template()
	{
		$template = ProductTemplate::factory()->create();

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->getJson('/api/product-templates/' . $template->id);

		$response->assertStatus(200)
				->assertJson([
					'success' => true,
					'data' => ['id' => $template->id],
				]);
	}

	public function test_can_create_template()
	{
		$data = [
			'name' => 'Boards',
			'unit' => 'шт',
			'description' => 'desc',
		];

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->postJson('/api/product-templates', $data);

		$response->assertStatus(201)
				->assertJson([
					'success' => true,
					'message' => 'Шаблон товара успешно создан',
				]);

		$this->assertDatabaseHas('product_templates', [
			'name' => 'Boards',
			'unit' => 'шт',
		]);
	}

	public function test_can_update_template()
	{
		$template = ProductTemplate::factory()->create(['is_active' => false]);

		$update = [
			'name' => 'Updated T',
			'is_active' => true,
		];

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->putJson('/api/product-templates/' . $template->id, $update);

		$response->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Шаблон товара успешно обновлен',
					'data' => [
						'name' => 'Updated T',
						'is_active' => true,
					],
				]);

		$this->assertDatabaseHas('product_templates', [
			'id' => $template->id,
			'name' => 'Updated T',
			'is_active' => true,
		]);
	}

	public function test_cannot_delete_template_with_products()
	{
		$template = ProductTemplate::factory()->create();
		$warehouse = Warehouse::factory()->create();
		Product::factory()->create(['product_template_id' => $template->id, 'warehouse_id' => $warehouse->id]);

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->deleteJson('/api/product-templates/' . $template->id);

		$response->assertStatus(400)
				->assertJson([
					'success' => false,
					'message' => 'Нельзя удалить шаблон, который используется в товарах',
				]);
	}

	public function test_can_delete_template_without_products()
	{
		$template = ProductTemplate::factory()->create();

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->deleteJson('/api/product-templates/' . $template->id);

		$response->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Шаблон товара успешно удален',
				]);

		$this->assertDatabaseMissing('product_templates', [
			'id' => $template->id,
		]);
	}

	public function test_can_activate_and_deactivate_template()
	{
		$template = ProductTemplate::factory()->create(['is_active' => false]);

		$activate = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->postJson('/api/product-templates/' . $template->id . '/activate');
		$activate->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Шаблон товара активирован',
				]);

		$deactivate = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->postJson('/api/product-templates/' . $template->id . '/deactivate');
		$deactivate->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Шаблон товара деактивирован',
				]);
	}

	public function test_can_manage_template_attributes()
	{
		$template = ProductTemplate::factory()->create();

		$create = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->postJson('/api/product-templates/' . $template->id . '/attributes', [
			'name' => 'Length',
			'variable' => 'length',
			'type' => 'number',
			'value' => '100',
			'unit' => 'mm',
			'is_required' => true,
			'is_in_formula' => true,
			'sort_order' => 1,
		]);
		$create->assertStatus(201)
				->assertJson([
					'success' => true,
					'message' => 'Характеристика успешно добавлена',
				]);

		$attributeId = $create->json('data.id');

		$update = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->putJson('/api/product-templates/' . $template->id . '/attributes/' . $attributeId, [
			'name' => 'Length2',
			'sort_order' => 2,
		]);
		$update->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Характеристика успешно обновлена',
					'data' => ['name' => 'Length2', 'sort_order' => 2],
				]);

		$list = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->getJson('/api/product-templates/' . $template->id . '/attributes');
		$list->assertStatus(200)
				->assertJsonStructure(['success', 'data']);

		$delete = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->deleteJson('/api/product-templates/' . $template->id . '/attributes/' . $attributeId);
		$delete->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Характеристика успешно удалена',
				]);
	}

	public function test_can_test_formula_and_list_products_for_template()
	{
		$template = ProductTemplate::factory()->create(['formula' => 'length * width * height']);

		$test = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->postJson('/api/product-templates/' . $template->id . '/test-formula', [
			'values' => ['length' => 1, 'width' => 2, 'height' => 3],
		]);
		$test->assertStatus(200)
				->assertJsonStructure(['success', 'data']);

		// список товаров по шаблону
		$listProducts = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->getJson('/api/product-templates/' . $template->id . '/products');
		$listProducts->assertStatus(200)
					->assertJsonStructure(['success', 'data', 'pagination']);
	}
}


