<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseApiTest extends TestCase
{
	use RefreshDatabase;

	private User $admin;
	private string $token;
	private Company $company;

	protected function setUp(): void
	{
		parent::setUp();

		$this->company = Company::factory()->create();
		$this->admin = User::factory()->create([
			'role' => 'admin',
			'company_id' => $this->company->id,
		]);
		$this->token = $this->admin->createToken('test-token')->plainTextToken;
	}

	public function test_can_list_warehouses()
	{
		Warehouse::factory()->count(2)->create(['company_id' => $this->company->id]);

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->getJson('/api/warehouses');

		$response->assertStatus(200)
				->assertJsonStructure([
					'success',
					'data',
					'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
				]);
	}

	public function test_can_show_warehouse()
	{
		$warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->getJson('/api/warehouses/' . $warehouse->id);

		$response->assertStatus(200)
				->assertJson([
					'success' => true,
					'data' => ['id' => $warehouse->id],
				]);
	}

	public function test_can_create_warehouse()
	{
		$data = [
			'name' => 'Main WH',
			'address' => 'Moscow, Lenina 1',
			'company_id' => $this->company->id,
		];

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->postJson('/api/warehouses', $data);

		$response->assertStatus(201)
				->assertJson([
					'success' => true,
					'message' => 'Склад успешно создан',
				]);

		$this->assertDatabaseHas('warehouses', [
			'name' => 'Main WH',
			'address' => 'Moscow, Lenina 1',
		]);
	}

	public function test_can_update_warehouse()
	{
		$warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);

		$update = [
			'name' => 'Updated WH',
			'is_active' => false,
		];

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->putJson('/api/warehouses/' . $warehouse->id, $update);

		$response->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Склад успешно обновлен',
					'data' => [
						'name' => 'Updated WH',
						'is_active' => false,
					],
				]);

		$this->assertDatabaseHas('warehouses', [
			'id' => $warehouse->id,
			'name' => 'Updated WH',
			'is_active' => false,
		]);
	}

	public function test_cannot_delete_warehouse_with_products_or_employees()
	{
		$warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);
		// Связанный товар
		Product::factory()->create(['warehouse_id' => $warehouse->id]);
		// Связанный сотрудник
		User::factory()->create(['warehouse_id' => $warehouse->id, 'company_id' => $this->company->id]);

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->deleteJson('/api/warehouses/' . $warehouse->id);

		$response->assertStatus(400)
				->assertJson([
					'success' => false,
					'message' => 'Нельзя удалить склад, на котором есть товары',
				]);
	}

	public function test_can_delete_empty_warehouse()
	{
		$warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->deleteJson('/api/warehouses/' . $warehouse->id);

		$response->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Склад успешно удален',
				]);

		$this->assertDatabaseMissing('warehouses', [
			'id' => $warehouse->id,
		]);
	}

	public function test_can_activate_and_deactivate_warehouse()
	{
		$warehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => false]);

		$activate = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->postJson('/api/warehouses/' . $warehouse->id . '/activate');
		$activate->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Склад активирован',
				]);

		$deactivate = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->postJson('/api/warehouses/' . $warehouse->id . '/deactivate');
		$deactivate->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Склад деактивирован',
				]);
	}

	public function test_can_get_warehouse_stats_products_and_employees()
	{
		$warehouse = Warehouse::factory()->create(['company_id' => $this->company->id]);

		$stats = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->getJson('/api/warehouses/' . $warehouse->id . '/stats');
		$stats->assertStatus(200)
			 ->assertJsonStructure(['success', 'data' => ['total_products', 'active_products', 'total_employees', 'total_volume', 'total_quantity']]);

		$products = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->getJson('/api/warehouses/' . $warehouse->id . '/products');
		$products->assertStatus(200)
				->assertJsonStructure(['success', 'data', 'pagination']);

		$employees = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->getJson('/api/warehouses/' . $warehouse->id . '/employees');
		$employees->assertStatus(200)
				 ->assertJsonStructure(['success', 'data', 'pagination']);
	}

	public function test_can_get_all_warehouses_stats()
	{
		Warehouse::factory()->count(2)->create(['company_id' => $this->company->id, 'is_active' => true]);
		Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => false]);

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->getJson('/api/warehouses/stats');

		$response->assertStatus(200)
				->assertJsonStructure(['success', 'data' => ['total', 'active', 'inactive']]);
	}
}


