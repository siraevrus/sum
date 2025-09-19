<?php

namespace Tests\Feature\Feature\Filament;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_users_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->get('/admin/users');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_users_list(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $this->actingAs($operator);

        $response = $this->get('/admin/users');
        $response->assertStatus(403);
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'first_name' => 'Test',
                'last_name' => 'User',
                'name' => 'Test User',
                'email' => 'test@example.com',
                'username' => 'testuser',
                'role' => 'operator',
                'password' => 'secret1234',
                'password_confirmation' => 'secret1234',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_admin_can_edit_user_via_livewire(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm([
                'first_name' => 'New',
                'last_name' => 'Name',
                'name' => 'New Name',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'New',
            'last_name' => 'Name',
        ]);
    }
}
