<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Request as RequestModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestApiTest extends TestCase
{
	use RefreshDatabase;

	private User $user;
	private string $token;
	private Warehouse $warehouse;
	private ProductTemplate $template;

	protected function setUp(): void
	{
		parent::setUp();

		$company = Company::factory()->create();
		$this->warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
		$this->template = ProductTemplate::factory()->create();
		$this->user = User::factory()->create(['role' => 'admin', 'company_id' => $company->id]);
		$this->token = $this->user->createToken('test-token')->plainTextToken;
	}

	public function test_can_list_requests()
	{
		RequestModel::factory()->count(2)->create([
			'warehouse_id' => $this->warehouse->id,
			'product_template_id' => $this->template->id,
			'user_id' => $this->user->id,
		]);

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->getJson('/api/requests');

		$response->assertStatus(200)
				->assertJsonStructure([
					'success',
					'data',
					'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
				]);
	}

	public function test_can_show_request()
	{
		$request = RequestModel::factory()->create([
			'warehouse_id' => $this->warehouse->id,
			'product_template_id' => $this->template->id,
			'user_id' => $this->user->id,
		]);

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->getJson('/api/requests/' . $request->id);

		$response->assertStatus(200)
				->assertJson([
					'success' => true,
					'data' => ['id' => $request->id],
				]);
	}

	public function test_can_create_request()
	{
		$data = [
			'warehouse_id' => $this->warehouse->id,
			'product_template_id' => $this->template->id,
			'title' => 'Need materials',
			'quantity' => 5,
			'priority' => 'high',
			'description' => 'Urgent',
		];

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->postJson('/api/requests', $data);

		$response->assertStatus(201)
				->assertJson([
					'success' => true,
					'message' => 'Запрос успешно создан',
				]);

		$this->assertDatabaseHas('requests', [
			'title' => 'Need materials',
			'priority' => 'high',
		]);
	}

	public function test_can_update_request()
	{
		$request = RequestModel::factory()->create([
			'warehouse_id' => $this->warehouse->id,
			'product_template_id' => $this->template->id,
			'user_id' => $this->user->id,
		]);

		$update = [
			'quantity' => 10,
			'priority' => 'urgent',
			'admin_notes' => 'Approved by admin',
		];

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->putJson('/api/requests/' . $request->id, $update);

		$response->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Запрос успешно обновлен',
					'data' => [
						'quantity' => 10,
						'priority' => 'urgent',
						'admin_notes' => 'Approved by admin',
					],
				]);
	}

	public function test_can_delete_request()
	{
		$request = RequestModel::factory()->create([
			'warehouse_id' => $this->warehouse->id,
			'product_template_id' => $this->template->id,
			'user_id' => $this->user->id,
		]);

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->deleteJson('/api/requests/' . $request->id);

		$response->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Запрос успешно удален',
				]);

		$this->assertDatabaseMissing('requests', [
			'id' => $request->id,
		]);
	}

	public function test_can_process_and_reject_request()
	{
		$request = RequestModel::factory()->create([
			'warehouse_id' => $this->warehouse->id,
			'product_template_id' => $this->template->id,
			'user_id' => $this->user->id,
			'status' => 'pending',
		]);

		$process = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->postJson('/api/requests/' . $request->id . '/process');
		$process->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Запрос обработан',
				]);

		$rejected = RequestModel::factory()->create([
			'warehouse_id' => $this->warehouse->id,
			'product_template_id' => $this->template->id,
			'user_id' => $this->user->id,
			'status' => 'pending',
		]);

		$reject = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->postJson('/api/requests/' . $rejected->id . '/reject');
		$reject->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Запрос отклонен',
				]);
	}

	public function test_can_get_requests_stats()
	{
		RequestModel::factory()->create([
			'warehouse_id' => $this->warehouse->id,
			'product_template_id' => $this->template->id,
			'user_id' => $this->user->id,
			'status' => 'pending',
		]);
		RequestModel::factory()->create([
			'warehouse_id' => $this->warehouse->id,
			'product_template_id' => $this->template->id,
			'user_id' => $this->user->id,
			'status' => 'rejected',
		]);

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->getJson('/api/requests/stats');

		$response->assertStatus(200)
				->assertJsonStructure([
					'success',
					'data' => ['total', 'pending', 'approved', 'completed', 'rejected', 'in_progress', 'cancelled'],
				]);
	}
}


