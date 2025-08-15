<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
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

	public function test_can_list_users()
	{
		User::factory()->count(3)->create(['company_id' => $this->company->id]);

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->getJson('/api/users');

		$response->assertStatus(200)
				->assertJsonStructure([
					'success',
					'data',
					'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
				]);
	}

	public function test_can_show_user()
	{
		$user = User::factory()->create(['company_id' => $this->company->id]);

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->getJson('/api/users/' . $user->id);

		$response->assertStatus(200)
				->assertJson([
					'success' => true,
					'data' => ['id' => $user->id],
				]);
	}

	public function test_can_create_user()
	{
		$userData = [
			'name' => 'John Doe',
			'username' => 'john_doe',
			'email' => 'john@example.com',
			'password' => 'password123',
			'role' => 'operator',
			'company_id' => $this->company->id,
		];

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->postJson('/api/users', $userData);

		$response->assertStatus(201)
				->assertJson([
					'success' => true,
					'message' => 'Пользователь успешно создан',
				]);

		$this->assertDatabaseHas('users', [
			'email' => 'john@example.com',
			'role' => 'operator',
		]);
	}

	public function test_can_update_user()
	{
		$user = User::factory()->create(['company_id' => $this->company->id, 'role' => 'operator']);

		$update = [
			'name' => 'Updated Name',
			'phone' => '+7 999 000-00-00',
		];

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->putJson('/api/users/' . $user->id, $update);

		$response->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Пользователь успешно обновлен',
					'data' => [
						'name' => 'Updated Name',
						'phone' => '+7 999 000-00-00',
					],
				]);

		$this->assertDatabaseHas('users', [
			'id' => $user->id,
			'name' => 'Updated Name',
			'phone' => '+7 999 000-00-00',
		]);
	}

	public function test_cannot_delete_self()
	{
		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->deleteJson('/api/users/' . $this->admin->id);

		$response->assertStatus(400)
				->assertJson([
					'success' => false,
					'message' => 'Нельзя удалить свой аккаунт',
				]);
	}

	public function test_can_delete_user()
	{
		$user = User::factory()->create(['company_id' => $this->company->id]);

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->deleteJson('/api/users/' . $user->id);

		$response->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Пользователь успешно удален',
				]);

		$this->assertDatabaseMissing('users', [
			'id' => $user->id,
		]);
	}

	public function test_can_block_and_unblock_user()
	{
		$user = User::factory()->create(['company_id' => $this->company->id, 'is_blocked' => false]);

		$block = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->postJson('/api/users/' . $user->id . '/block');
		$block->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Пользователь заблокирован',
				]);

		$unblock = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->postJson('/api/users/' . $user->id . '/unblock');
		$unblock->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Пользователь разблокирован',
				]);
	}

	public function test_can_get_users_stats()
	{
		User::factory()->count(2)->create(['company_id' => $this->company->id, 'is_blocked' => false]);
		User::factory()->create(['company_id' => $this->company->id, 'is_blocked' => true]);

		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->getJson('/api/users/stats');

		$response->assertStatus(200)
				->assertJsonStructure([
					'success',
					'data' => ['total', 'active', 'blocked', 'by_role'],
				]);
	}

	public function test_can_get_and_update_current_user_profile_via_users_group()
	{
		$response = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->getJson('/api/users/profile');

		$response->assertStatus(200)
				->assertJson([
					'success' => true,
					'data' => ['id' => $this->admin->id],
				]);

		$update = $this->withHeaders([
			'Authorization' => 'Bearer ' . $this->token,
		])->putJson('/api/users/profile', [
			'name' => 'Admin Updated',
			'email' => 'admin-upd@example.com',
		]);

		$update->assertStatus(200)
				->assertJson([
					'success' => true,
					'message' => 'Профиль успешно обновлен',
					'data' => [
						'name' => 'Admin Updated',
						'email' => 'admin-upd@example.com',
					],
				]);
	}
}


