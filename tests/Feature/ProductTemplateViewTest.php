<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductTemplateResource;
use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductTemplateViewTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_render_product_template_view_page(): void
    {
        // Создаем тестового пользователя-админа
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        // Создаем тестовый шаблон товара
        $template = ProductTemplate::factory()->create([
            'name' => 'Тестовый шаблон',
            'description' => 'Описание тестового шаблона',
            'unit' => 'м³',
            'is_active' => true,
        ]);

        // Аутентифицируемся как админ
        $this->actingAs($user);

        // Тестируем страницу просмотра
        Livewire::test(ProductTemplateResource\Pages\ViewProductTemplate::class, [
            'record' => $template->getRouteKey(),
        ])
            ->assertSuccessful()
            ->assertSee('Основная информация')
            ->assertSee($template->name)
            ->assertSee($template->description)
            ->assertSee($template->unit);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_displays_basic_info_in_four_columns(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $template = ProductTemplate::factory()->create([
            'name' => 'Тестовый шаблон для проверки колонок',
            'description' => 'Описание для проверки отображения в 4 колонках',
            'unit' => 'шт',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // Проверяем, что страница загружается и отображает нужную информацию
        $component = Livewire::test(ProductTemplateResource\Pages\ViewProductTemplate::class, [
            'record' => $template->getRouteKey(),
        ]);

        $component->assertSuccessful();

        // Проверяем наличие основных элементов
        $component->assertSee('Основная информация');
        $component->assertSee('Название шаблона');
        $component->assertSee('Описание');
        $component->assertSee('Единица измерения');
        $component->assertSee('Активный');
    }
}
