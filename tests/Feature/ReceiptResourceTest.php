<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ProductInTransit;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Models\Warehouse;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем тестовые данные
        $company = Company::factory()->create();
        $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $template = ProductTemplate::factory()->create();

        // Создаем пользователя с ролью warehouse_worker
        $this->user = User::factory()->create([
            'role' => UserRole::WAREHOUSE_WORKER,
            'company_id' => $company->id,
        ]);
    }

    public function test_warehouse_worker_can_access_receipt_list()
    {
        $this->actingAs($this->user)
            ->get('/admin/receipts')
            ->assertStatus(200);
    }

    public function test_warehouse_worker_can_access_receipt_create_page()
    {
        $this->actingAs($this->user)
            ->get('/admin/receipts/create')
            ->assertStatus(200);
    }

    public function test_warehouse_worker_can_access_receipt_edit_page()
    {
        $receipt = ProductInTransit::factory()->create([
            'warehouse_id' => $this->user->company->warehouses->first()->id,
            'status' => ProductInTransit::STATUS_ARRIVED,
        ]);

        $this->actingAs($this->user)
            ->get("/admin/receipts/{$receipt->id}/edit")
            ->assertStatus(200);
    }

    public function test_receipt_form_contains_product_section()
    {
        $this->actingAs($this->user)
            ->get('/admin/receipts/create')
            ->assertSee('Товар')
            ->assertSee('Шаблон товара')
            ->assertSee('Наименование')
            ->assertSee('Производитель')
            ->assertSee('Количество');
    }

    public function test_receipt_form_contains_warehouse_selection()
    {
        $this->actingAs($this->user)
            ->get('/admin/receipts/create')
            ->assertSee('Склад назначения')
            ->assertSee('Место отгрузки')
            ->assertSee('Дата отгрузки');
    }
}
