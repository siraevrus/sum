<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceiptResource\Pages;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ReceiptResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationGroup = 'Приемка';

    protected static ?string $modelLabel = 'Приемка товара';

    protected static ?string $pluralModelLabel = 'Приемка товаров';

    protected static ?int $navigationSort = 7;

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // Приемка доступна только админу и работнику склада
        return in_array($user->role->value, [
            'admin',
            'warehouse_worker',
        ]);
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // Редактирование доступно только админу и работнику склада
        return in_array($user->role->value, [
            'admin',
            'warehouse_worker',
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                // Первый столбец
                                TextInput::make('name')
                                    ->label('Наименование')
                                    ->disabled()
                                    ->columnSpan(1)
                                    ->columnStart(1),

                                Select::make('warehouse_id')
                                    ->label('Склад назначения')
                                    ->options(fn () => Warehouse::optionsForCurrentUser())
                                    ->required()
                                    ->dehydrated()
                                    ->default(function () {
                                        $user = Auth::user();
                                        if (! $user) {
                                            return null;
                                        }

                                        return $user->isAdmin() ? null : $user->warehouse_id;
                                    })
                                    ->visible(function () {
                                        $user = Auth::user();
                                        if (! $user) {
                                            return false;
                                        }

                                        return $user->isAdmin();
                                    })
                                    ->disabled(fn () => request()->route()->getName() === 'filament.admin.resources.receipts.edit')
                                    ->searchable()
                                    ->columnSpan(1)
                                    ->columnStart(1),

                                TextInput::make('transport_number')
                                    ->label('Номер транспорта')
                                    ->maxLength(255)
                                    ->disabled(fn () => request()->route()->getName() === 'filament.admin.resources.receipts.edit')
                                    ->columnSpan(1)
                                    ->columnStart(1),

                                DatePicker::make('expected_arrival_date')
                                    ->label('Ожидаемая дата прибытия')
                                    ->disabled(fn () => request()->route()->getName() === 'filament.admin.resources.receipts.edit')
                                    ->columnSpan(1)
                                    ->columnStart(1),

                                // Второй столбец
                                TextInput::make('shipping_location')
                                    ->label('Место отгрузки')
                                    ->maxLength(255)
                                    ->required()
                                    ->disabled(fn () => request()->route()->getName() === 'filament.admin.resources.receipts.edit')
                                    ->columnSpan(1)
                                    ->columnStart(2),

                                DatePicker::make('shipping_date')
                                    ->label('Дата отгрузки')
                                    ->required()
                                    ->default(now())
                                    ->disabled(fn () => request()->route()->getName() === 'filament.admin.resources.receipts.edit')
                                    ->columnSpan(1)
                                    ->columnStart(2),

                                \Filament\Forms\Components\Placeholder::make('creator_name')
                                    ->label('Создатель')
                                    ->content(fn (?Product $record) => $record?->creator?->name ?? '—')
                                    ->columnSpan(1)
                                    ->columnStart(2),

                                Textarea::make('notes')
                                    ->label('Заметки')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->disabled(fn () => request()->route()->getName() === 'filament.admin.resources.receipts.edit')
                                    ->columnSpanFull(),

                                // Удалено поле actual_arrival_date
                            ]),
                    ]),

                Section::make('Информация о товаре')
                    ->schema([
                        Repeater::make('products')
                            ->label('Список товаров')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('product_template_id')
                                            ->label('Шаблон товара')
                                            ->options(function () {
                                                return ProductTemplate::pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function (Set $set) {
                                                $set('name', '');
                                                $set('calculated_volume', null);
                                            })
                                            ->disabled(fn () => request()->route()->getName() === 'filament.admin.resources.receipts.edit'),

                                        TextInput::make('name')
                                            ->label('Наименование')
                                            ->maxLength(255)
                                            ->required()
                                            ->disabled()
                                            ->hidden(fn () => true),

                                        TextInput::make('quantity')
                                            ->label('Количество')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->required()
                                            ->live()
                                            ->debounce(50)
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                // Пересчитываем объем при изменении количества
                                                $templateId = $get('product_template_id');
                                                if (! $templateId) {
                                                    return;
                                                }

                                                $template = ProductTemplate::with('attributes')->find($templateId);
                                                if (! $template) {
                                                    return;
                                                }

                                                $attributes = [];
                                                $formData = $get();

                                                foreach ($formData as $key => $value) {
                                                    if (str_starts_with($key, 'attribute_') && $value !== null && $value !== '') {
                                                        $attributeName = str_replace('attribute_', '', $key);
                                                        $attributes[$attributeName] = $value;
                                                    }
                                                }

                                                // Рассчитываем объем для заполненных числовых характеристик
                                                $numericAttributes = [];
                                                foreach ($attributes as $key => $value) {
                                                    if (is_numeric($value) && $value > 0) {
                                                        $numericAttributes[$key] = $value;
                                                    }
                                                }

                                                // Добавляем количество в атрибуты для формулы
                                                $quantity = $get('quantity') ?? 1;
                                                if (is_numeric($quantity) && $quantity > 0) {
                                                    $numericAttributes['quantity'] = $quantity;
                                                }

                                                // Логируем атрибуты для отладки
                                                \Log::info('Attributes for volume calculation (quantity - ReceiptResource)', [
                                                    'template' => $template->name,
                                                    'all_attributes' => $attributes,
                                                    'numeric_attributes' => $numericAttributes,
                                                    'quantity' => $quantity,
                                                    'formula' => $template->formula,
                                                ]);

                                                // Если есть заполненные числовые характеристики и формула, рассчитываем объем
                                                if (! empty($numericAttributes) && $template->formula) {
                                                    $testResult = $template->testFormula($numericAttributes);
                                                    if ($testResult['success']) {
                                                        $result = $testResult['result'];
                                                        $set('calculated_volume', $result);

                                                        // Логируем для отладки
                                                        \Log::info('Volume calculated from quantity change (ReceiptResource)', [
                                                            'template' => $template->name,
                                                            'attributes' => $numericAttributes,
                                                            'result' => $result,
                                                        ]);
                                                    } else {
                                                        // Если расчет не удался, показываем ошибку
                                                        $set('calculated_volume', 'Заполните поля: '.($testResult['error'] ?? 'Неизвестная ошибка'));
                                                        \Log::warning('Volume calculation failed from quantity change (ReceiptResource)', [
                                                            'template' => $template->name,
                                                            'attributes' => $numericAttributes,
                                                            'error' => $testResult['error'],
                                                        ]);
                                                    }
                                                } else {
                                                    // Если недостаточно данных для расчета, показываем подсказку
                                                    if (empty($numericAttributes)) {
                                                        $set('calculated_volume', 'Заполните числовые характеристики');
                                                    } else {
                                                        $set('calculated_volume', 'Формула не задана');
                                                    }
                                                }

                                                // Формируем наименование из заполненных характеристик, исключая текстовые атрибуты
                                                $nameParts = [];
                                                foreach ($template->attributes as $templateAttribute) {
                                                    $attributeKey = $templateAttribute->variable;
                                                    if ($templateAttribute->type !== 'text' && isset($attributes[$attributeKey]) && $attributes[$attributeKey] !== null && $attributes[$attributeKey] !== '') {
                                                        $nameParts[] = $attributes[$attributeKey];
                                                    }
                                                }

                                                if (! empty($nameParts)) {
                                                    $templateName = $template->name ?? 'Товар';
                                                    $generatedName = $templateName.': '.implode(', ', $nameParts);
                                                    $set('name', $generatedName);
                                                } else {
                                                    $set('name', $template->name ?? 'Товар');
                                                }
                                            }),

                                        TextInput::make('calculated_volume')
                                            ->label('Объем')
                                            ->numeric()
                                            ->disabled()
                                            ->live()
                                            ->formatStateUsing(function ($state) {
                                                // Если это число - форматируем, если строка - показываем как есть
                                                if (is_numeric($state)) {
                                                    return number_format($state, 3, '.', ' ');
                                                }

                                                return e($state ?: '0.000');
                                            })
                                            ->dehydrateStateUsing(function ($state) {
                                                // Преобразуем отформатированное значение обратно в число
                                                if (is_string($state)) {
                                                    // Убираем пробелы и преобразуем в число
                                                    $cleanState = str_replace(' ', '', $state);

                                                    return is_numeric($cleanState) ? (float) $cleanState : null;
                                                }

                                                return is_numeric($state) ? (float) $state : null;
                                            })
                                            ->suffix(function (Get $get) {
                                                $templateId = $get('product_template_id');
                                                if ($templateId) {
                                                    $template = ProductTemplate::find($templateId);

                                                    return $template ? $template->unit : '';
                                                }

                                                return '';
                                            })
                                            ->helperText('Автоматически рассчитывается при заполнении характеристик или изменении количества'),

                                        Select::make('producer_id')
                                            ->label('Производитель')
                                            ->options(\App\Models\Producer::pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Выберите производителя')
                                            ->required()
                                            ->disabled(fn () => request()->route()->getName() === 'filament.admin.resources.receipts.edit'),

                                        // Удалено поле tracking_number
                                    ]),

                                // Динамические поля для характеристик
                                Grid::make(2)
                                    ->schema(function (Get $get) {
                                        $templateId = $get('product_template_id');
                                        if (! $templateId) {
                                            return [];
                                        }

                                        $template = ProductTemplate::find($templateId);
                                        if (! $template || ! $template->attributes) {
                                            return [];
                                        }

                                        $fields = [];
                                        foreach ($template->attributes as $attribute) {
                                            $fieldName = "attribute_{$attribute->variable}";

                                            switch ($attribute->type) {
                                                case 'number':
                                                    $fields[] = TextInput::make($fieldName)
                                                        ->label($attribute->name)
                                                        ->numeric()
                                                        ->required($attribute->is_required)
                                                        ->live()
                                                        ->debounce(30)
                                                        ->readOnly() // Характеристики только для чтения на странице приемки
                                                        ->dehydrated(false) // Не отправляем значения на сервер
                                                        ->formatStateUsing(function ($state, $record) use ($attribute) {
                                                            // Принудительно загружаем значение из записи, если пустое
                                                            if (empty($state) && $record && $record->attributes) {
                                                                $attributes = is_array($record->attributes) ? $record->attributes : json_decode($record->attributes, true) ?? [];
                                                                return $attributes[$attribute->variable] ?? $state;
                                                            }
                                                            return $state;
                                                        })
                                                        ->afterStateUpdated(function (Set $set, Get $get) use ($template) {
                                                            // Рассчитываем объем при изменении характеристики
                                                            $attributes = [];
                                                            $formData = $get();

                                                            foreach ($formData as $key => $value) {
                                                                if (str_starts_with($key, 'attribute_') && $value !== null && $value !== '') {
                                                                    $attributeName = str_replace('attribute_', '', $key);
                                                                    $attributes[$attributeName] = $value;
                                                                }
                                                            }

                                                            // Рассчитываем объем для заполненных числовых характеристик
                                                            $numericAttributes = [];
                                                            foreach ($attributes as $key => $value) {
                                                                if (is_numeric($value) && $value > 0) {
                                                                    $numericAttributes[$key] = $value;
                                                                }
                                                            }

                                                            // Добавляем количество в атрибуты для формулы
                                                            $quantity = $get('quantity') ?? 1;
                                                            if (is_numeric($quantity) && $quantity > 0) {
                                                                $numericAttributes['quantity'] = $quantity;
                                                            }

                                                            // Логируем атрибуты для отладки
                                                            \Log::info('Attributes for volume calculation (number - ReceiptResource)', [
                                                                'template' => $template->name,
                                                                'all_attributes' => $attributes,
                                                                'numeric_attributes' => $numericAttributes,
                                                                'quantity' => $quantity,
                                                                'formula' => $template->formula,
                                                            ]);

                                                            // Если есть заполненные числовые характеристики и формула, рассчитываем объем
                                                            if (! empty($numericAttributes) && $template->formula) {
                                                                $testResult = $template->testFormula($numericAttributes);
                                                                if ($testResult['success']) {
                                                                    $result = $testResult['result'];
                                                                    $set('calculated_volume', $result);

                                                                    // Логируем для отладки
                                                                    \Log::info('Volume calculated (ReceiptResource)', [
                                                                        'template' => $template->name,
                                                                        'attributes' => $numericAttributes,
                                                                        'result' => $result,
                                                                    ]);
                                                                } else {
                                                                    // Если расчет не удался, показываем ошибку
                                                                    $set('calculated_volume', 'Заполните поля: '.($testResult['error'] ?? 'Неизвестная ошибка'));
                                                                    \Log::warning('Volume calculation failed (ReceiptResource)', [
                                                                        'template' => $template->name,
                                                                        'attributes' => $numericAttributes,
                                                                        'error' => $testResult['error'],
                                                                    ]);
                                                                }
                                                            } else {
                                                                // Если недостаточно данных для расчета, показываем подсказку
                                                                if (empty($numericAttributes)) {
                                                                    $set('calculated_volume', 'Заполните числовые характеристики');
                                                                } else {
                                                                    $set('calculated_volume', 'Формула не задана');
                                                                }
                                                            }

                                                            // Формируем наименование из заполненных характеристик, исключая текстовые атрибуты
                                                            $nameParts = [];
                                                            foreach ($template->attributes as $templateAttribute) {
                                                                $attributeKey = $templateAttribute->variable;
                                                                if ($templateAttribute->type !== 'text' && isset($attributes[$attributeKey]) && $attributes[$attributeKey] !== null && $attributes[$attributeKey] !== '') {
                                                                    $nameParts[] = $attributes[$attributeKey];
                                                                }
                                                            }

                                                            if (! empty($nameParts)) {
                                                                $templateName = $template->name ?? 'Товар';
                                                                $generatedName = $templateName.': '.implode(', ', $nameParts);
                                                                $set('name', $generatedName);
                                                            } else {
                                                                $set('name', $template->name ?? 'Товар');
                                                            }
                                                        });
                                                    break;

                                                case 'text':
                                                    $fields[] = TextInput::make($fieldName)
                                                        ->label($attribute->name)
                                                        ->required($attribute->is_required)
                                                        ->live()
                                                        ->debounce(30)
                                                        ->readOnly() // Характеристики только для чтения на странице приемки
                                                        ->dehydrated(false) // Не отправляем значения на сервер
                                                        ->formatStateUsing(function ($state, $record) use ($attribute) {
                                                            // Принудительно загружаем значение из записи, если пустое
                                                            if (empty($state) && $record && $record->attributes) {
                                                                $attributes = is_array($record->attributes) ? $record->attributes : json_decode($record->attributes, true) ?? [];
                                                                return $attributes[$attribute->variable] ?? $state;
                                                            }
                                                            return $state;
                                                        })
                                                        ->afterStateUpdated(function (Set $set, Get $get) use ($template) {
                                                            // Рассчитываем объем при изменении характеристики
                                                            $attributes = [];
                                                            $formData = $get();

                                                            foreach ($formData as $key => $value) {
                                                                if (str_starts_with($key, 'attribute_') && $value !== null && $value !== '') {
                                                                    $attributeName = str_replace('attribute_', '', $key);
                                                                    $attributes[$attributeName] = $value;
                                                                }
                                                            }

                                                            // Рассчитываем объем для заполненных числовых характеристик
                                                            $numericAttributes = [];
                                                            foreach ($attributes as $key => $value) {
                                                                if (is_numeric($value) && $value > 0) {
                                                                    $numericAttributes[$key] = $value;
                                                                }
                                                            }

                                                            // Добавляем количество в атрибуты для формулы
                                                            $quantity = $get('quantity') ?? 1;
                                                            if (is_numeric($quantity) && $quantity > 0) {
                                                                $numericAttributes['quantity'] = $quantity;
                                                            }

                                                            // Логируем атрибуты для отладки
                                                            \Log::info('Attributes for volume calculation (text - ReceiptResource)', [
                                                                'template' => $template->name,
                                                                'all_attributes' => $attributes,
                                                                'numeric_attributes' => $numericAttributes,
                                                                'quantity' => $quantity,
                                                                'formula' => $template->formula,
                                                            ]);

                                                            // Если есть заполненные числовые характеристики и формула, рассчитываем объем
                                                            if (! empty($numericAttributes) && $template->formula) {
                                                                $testResult = $template->testFormula($numericAttributes);
                                                                if ($testResult['success']) {
                                                                    $result = $testResult['result'];
                                                                    $set('calculated_volume', $result);

                                                                    // Логируем для отладки
                                                                    \Log::info('Volume calculated (ReceiptResource)', [
                                                                        'template' => $template->name,
                                                                        'attributes' => $numericAttributes,
                                                                        'result' => $result,
                                                                    ]);
                                                                } else {
                                                                    // Если расчет не удался, показываем ошибку
                                                                    $set('calculated_volume', 'Заполните поля: '.($testResult['error'] ?? 'Неизвестная ошибка'));
                                                                    \Log::warning('Volume calculation failed (ReceiptResource)', [
                                                                        'template' => $template->name,
                                                                        'attributes' => $numericAttributes,
                                                                        'error' => $testResult['error'],
                                                                    ]);
                                                                }
                                                            } else {
                                                                // Если недостаточно данных для расчета, показываем подсказку
                                                                if (empty($numericAttributes)) {
                                                                    $set('calculated_volume', 'Заполните числовые характеристики');
                                                                } else {
                                                                    $set('calculated_volume', 'Формула не задана');
                                                                }
                                                            }

                                                            // Формируем наименование из заполненных характеристик, исключая текстовые атрибуты
                                                            $nameParts = [];
                                                            foreach ($template->attributes as $templateAttribute) {
                                                                $attributeKey = $templateAttribute->variable;
                                                                if ($templateAttribute->type !== 'text' && isset($attributes[$attributeKey]) && $attributes[$attributeKey] !== null && $attributes[$attributeKey] !== '') {
                                                                    $nameParts[] = $attributes[$attributeKey];
                                                                }
                                                            }

                                                            if (! empty($nameParts)) {
                                                                $templateName = $template->name ?? 'Товар';
                                                                $generatedName = $templateName.': '.implode(', ', $nameParts);
                                                                $set('name', $generatedName);
                                                            } else {
                                                                $set('name', $template->name ?? 'Товар');
                                                            }
                                                        });
                                                    break;

                                                case 'select':
                                                    $options = $attribute->options_array;
                                                    $fields[] = Select::make($fieldName)
                                                        ->label($attribute->name)
                                                        ->options($options)
                                                        ->required($attribute->is_required)
                                                        ->live()
                                                        ->debounce(30)
                                                        ->disabled() // Select не поддерживает readOnly, используем disabled
                                                        ->dehydrated(false) // Не отправляем значения на сервер
                                                        ->formatStateUsing(function ($state, $record) use ($attribute) {
                                                            // Принудительно загружаем значение из записи, если пустое
                                                            if (empty($state) && $record && $record->attributes) {
                                                                $attributes = is_array($record->attributes) ? $record->attributes : json_decode($record->attributes, true) ?? [];
                                                                return $attributes[$attribute->variable] ?? $state;
                                                            }
                                                            return $state;
                                                        })
                                                        ->afterStateUpdated(function (Set $set, Get $get) use ($template) {
                                                            // Рассчитываем объем при изменении характеристики
                                                            $attributes = [];
                                                            $formData = $get();

                                                            foreach ($formData as $key => $value) {
                                                                if (str_starts_with($key, 'attribute_') && $value !== null && $value !== '') {
                                                                    $attributeName = str_replace('attribute_', '', $key);
                                                                    $attributes[$attributeName] = $value;
                                                                }
                                                            }

                                                            // Рассчитываем объем для заполненных числовых характеристик
                                                            $numericAttributes = [];
                                                            foreach ($attributes as $key => $value) {
                                                                if (is_numeric($value) && $value > 0) {
                                                                    $numericAttributes[$key] = $value;
                                                                }
                                                            }

                                                            // Добавляем количество в атрибуты для формулы
                                                            $quantity = $get('quantity') ?? 1;
                                                            if (is_numeric($quantity) && $quantity > 0) {
                                                                $numericAttributes['quantity'] = $quantity;
                                                            }

                                                            // Логируем атрибуты для отладки
                                                            \Log::info('Attributes for volume calculation (select - ReceiptResource)', [
                                                                'template' => $template->name,
                                                                'all_attributes' => $attributes,
                                                                'numeric_attributes' => $numericAttributes,
                                                                'quantity' => $quantity,
                                                                'formula' => $template->formula,
                                                            ]);

                                                            // Если есть заполненные числовые характеристики и формула, рассчитываем объем
                                                            if (! empty($numericAttributes) && $template->formula) {
                                                                $testResult = $template->testFormula($numericAttributes);
                                                                if ($testResult['success']) {
                                                                    $result = $testResult['result'];
                                                                    $set('calculated_volume', $result);

                                                                    // Логируем для отладки
                                                                    \Log::info('Volume calculated (ReceiptResource)', [
                                                                        'template' => $template->name,
                                                                        'attributes' => $numericAttributes,
                                                                        'result' => $result,
                                                                    ]);
                                                                } else {
                                                                    // Если расчет не удался, показываем ошибку
                                                                    $set('calculated_volume', 'Заполните поля: '.($testResult['error'] ?? 'Неизвестная ошибка'));
                                                                    \Log::warning('Volume calculation failed (ReceiptResource)', [
                                                                        'template' => $template->name,
                                                                        'attributes' => $numericAttributes,
                                                                        'error' => $testResult['error'],
                                                                    ]);
                                                                }
                                                            } else {
                                                                // Если недостаточно данных для расчета, показываем подсказку
                                                                if (empty($numericAttributes)) {
                                                                    $set('calculated_volume', 'Заполните числовые характеристики');
                                                                } else {
                                                                    $set('calculated_volume', 'Формула не задана');
                                                                }
                                                            }

                                                            // Формируем наименование из заполненных характеристик, исключая текстовые атрибуты
                                                            $nameParts = [];
                                                            foreach ($template->attributes as $templateAttribute) {
                                                                $attributeKey = $templateAttribute->variable;
                                                                if ($templateAttribute->type !== 'text' && isset($attributes[$attributeKey]) && $attributes[$attributeKey] !== null && $attributes[$attributeKey] !== '') {
                                                                    $nameParts[] = $attributes[$attributeKey];
                                                                }
                                                            }

                                                            if (! empty($nameParts)) {
                                                                $templateName = $template->name ?? 'Товар';
                                                                $generatedName = $templateName.': '.implode(', ', $nameParts);
                                                                $set('name', $generatedName);
                                                            } else {
                                                                $set('name', $template->name ?? 'Товар');
                                                            }
                                                        });
                                                    break;
                                            }
                                        }

                                        return $fields;
                                    })
                                    ->visible(fn (Get $get) => $get('product_template_id') !== null),

                                // Удалено поле description
                            ])
                            ->addActionLabel('Добавить товар')
                            ->addable(fn () => request()->route()->getName() !== 'filament.admin.resources.receipts.edit')
                            ->deletable(false)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Товар')
                            ->defaultItems(1)
                            ->minItems(1),
                    ]),

                Section::make('Документы')
                    ->schema([
                        FileUpload::make('document_path')
                            ->label('Документы')
                            ->directory('documents')
                            ->multiple()
                            ->maxFiles(5)
                            ->maxSize(51200) // 50MB
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'])
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->imagePreviewHeight('250'),
                    ])
                    ->visible(fn () => request()->route()->getName() !== 'filament.admin.resources.receipts.edit'),
            ]);
    }

    // Убран функционал превью карточки
    // public static function infolist(Infolist $infolist): Infolist
    // {
    //     return $infolist
    //         ->schema([
    //             InfoSection::make('Детальная информация о товаре')
    //                 ->schema([
    //                     TextEntry::make('name')->label('Наименование'),
    //                     TextEntry::make('producer.name')->label('Производитель'),
    //                     TextEntry::make('quantity')->label('Количество'),
    //                     TextEntry::make('calculated_volume')->label('Объем'),
    //                     TextEntry::make('transport_number')->label('Номер транспорта'),
    //                     TextEntry::make('shipping_location')->label('Место отгрузки'),
    //                     TextEntry::make('shipping_date')->label('Дата отгрузки'),
    //                     TextEntry::make('expected_arrival_date')->label('Ожидаемая дата прибытия'),
    //                     TextEntry::make('arrival_date')->label('Дата поступления'),
    //                     TextEntry::make('document_path')->label('Документы')->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : $state),
    //                     KeyValueEntry::make('attributes')->label('Характеристики')->visible(fn($state) => is_array($state) && count($state) > 0),
    //                 ])
    //         ]);
    // }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Наименование')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->sortable(),

                // Скрыты: 'Место отгрузки', 'Дата отгрузки', 'Производитель', 'Ожидаемая дата'
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Количество')
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('calculated_volume')
                    ->label('Объем')
                    ->formatStateUsing(function ($state) {
                        return $state ? number_format($state, 3, '.', ' ') : '0.000';
                    })
                    ->suffix(function (Product $record): string {
                        return $record->template?->unit ?? '';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => Product::STATUS_FOR_RECEIPT,
                        'success' => Product::STATUS_IN_STOCK,
                    ])
                    ->formatStateUsing(function (Product $record): string {
                        return $record->isForReceipt() ? 'Для приемки' : 'На складе';
                    })
                    ->sortable(),

                // Ожидаемая дата скрыта
                Tables\Columns\TextColumn::make('actual_arrival_date')
                    ->label('Фактическая дата')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Создатель')
                    ->sortable(),
            ])
            ->emptyStateHeading('Нет товаров для приемки')
            ->emptyStateDescription('Все товары уже приняты или нет товаров со статусом "Для приемки".')
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(fn () => Warehouse::optionsForCurrentUser())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('shipping_location')
                    ->label('Место отгрузки')
                    ->options(function () {
                        $locations = Product::query()
                            ->whereNotNull('shipping_location')
                            ->distinct()
                            ->pluck('shipping_location')
                            ->filter()
                            ->sort()
                            ->values()
                            ->toArray();

                        return array_combine($locations, $locations);
                    })
                    ->searchable(),
                // Убраны фильтры 'ready_for_receipt' и 'status'
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->url(fn (Product $record) => Pages\ViewReceipt::getUrl(['record' => $record])),
                Tables\Actions\EditAction::make()
                    ->label(''),
                Tables\Actions\Action::make('confirm_receipt')
                    ->label('Подтвердить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Product $record): bool => $record->status === Product::STATUS_FOR_RECEIPT)
                    ->requiresConfirmation()
                    ->modalHeading('Подтверждение приемки товара')
                    ->modalDescription('Вы уверены, что хотите подтвердить приемку этого товара? Товар будет переведен в статус "На складе".')
                    ->action(function (Product $record): void {
                        $record->update([
                            'status' => Product::STATUS_IN_STOCK,
                            'actual_arrival_date' => now(),
                        ]);
                    })
                    ->after(function () {
                        \Filament\Notifications\Notification::make()
                            ->title('Товар успешно принят')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('confirm_multiple_receipts')
                        ->label('Подтвердить приемку выбранных')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Подтверждение приемки товаров')
                        ->modalDescription('Вы уверены, что хотите подтвердить приемку выбранных товаров?')
                        ->action(function (\Illuminate\Support\Collection $records): void {
                            foreach ($records as $record) {
                                if ($record->status === Product::STATUS_FOR_RECEIPT) {
                                    $record->update([
                                        'status' => Product::STATUS_IN_STOCK,
                                        'actual_arrival_date' => now(),
                                    ]);
                                }
                            }
                        })
                        ->after(function () {
                            \Filament\Notifications\Notification::make()
                                ->title('Товары успешно приняты')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReceipts::route('/'),
            'view' => Pages\ViewReceipt::route('/{record}'),
            'edit' => Pages\EditReceipt::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $base = parent::getEloquentQuery()
            ->where('status', Product::STATUS_FOR_RECEIPT)
            ->active();

        if (! $user) {
            return $base->whereRaw('1 = 0');
        }

        if ($user->role->value === 'admin') {
            return $base;
        }

        if ($user->warehouse_id) {
            return $base->where('warehouse_id', $user->warehouse_id);
        }

        return $base->whereRaw('1 = 0');
    }

    /**
     * Рассчитать объем для элемента товара
     */
    private static function calculateVolumeForItem(Set $set, Get $get): void
    {
        $templateId = $get('product_template_id');
        if (! $templateId) {
            return;
        }

        $template = ProductTemplate::find($templateId);
        if (! $template || ! $template->formula) {
            return;
        }

        // Собираем все значения характеристик
        $attributes = [];
        $formData = $get();

        foreach ($formData as $key => $value) {
            if (str_starts_with($key, 'attribute_') && $value !== null) {
                $attributeName = str_replace('attribute_', '', $key);
                $attributes[$attributeName] = $value;
            }
        }

        // Добавляем количество
        $quantity = $get('quantity') ?? 1;
        $attributes['quantity'] = $quantity;

        if (! empty($attributes)) {
            // Формируем наименование из характеристик
            $nameParts = [];
            foreach ($template->attributes as $templateAttribute) {
                $attributeKey = $templateAttribute->variable;
                if (isset($attributes[$attributeKey]) && $attributes[$attributeKey] !== null) {
                    $nameParts[] = $attributes[$attributeKey];
                }
            }

            if (! empty($nameParts)) {
                $templateName = $template->name ?? 'Товар';
                $generatedName = $templateName.': '.implode(', ', $nameParts);
                $set('name', $generatedName);
            }

            // Рассчитываем объем по формуле
            try {
                $formula = $template->formula;
                $expression = $formula;

                // Получаем переменные из формулы
                preg_match_all('/[a-zA-Z_][a-zA-Z0-9_]*/', $formula, $matches);
                $variables = $matches[0] ?? [];

                foreach ($variables as $var) {
                    if (! isset($attributes[$var]) || ! is_numeric($attributes[$var])) {
                        $set('calculated_volume', null);

                        return;
                    }
                    // Используем регулярное выражение для точной замены переменных
                    $pattern = '/\b'.preg_quote($var, '/').'\b/';
                    $expression = preg_replace($pattern, $attributes[$var], $expression);
                }

                $result = eval("return $expression;");
                $set('calculated_volume', $result);
            } catch (\Throwable $e) {
                $set('calculated_volume', null);
            }
        }
    }

    /**
     * Обработать данные формы перед сохранением
     */
    public static function mutateFormDataBeforeSave(array $data): array
    {
        // Логируем данные для отладки
        \Log::info('ReceiptResource: Data before save', $data);

        // Обрабатываем характеристики из repeater, если они есть
        if (isset($data['products']) && is_array($data['products']) && ! empty($data['products'])) {
            $firstProduct = $data['products'][0];

            // Собираем характеристики
            $attributes = [];
            foreach ($firstProduct as $key => $value) {
                if (str_starts_with($key, 'attribute_') && $value !== null && $value !== '') {
                    $attributeName = str_replace('attribute_', '', $key);
                    $attributes[$attributeName] = $value;
                }
            }

            // Обновляем основные поля
            $data['attributes'] = $attributes;
            $data['product_template_id'] = $firstProduct['product_template_id'] ?? null;
            $data['producer_id'] = $firstProduct['producer_id'] ?? null;
            $data['name'] = $firstProduct['name'] ?? null;
            $data['quantity'] = $firstProduct['quantity'] ?? 1;
            $data['calculated_volume'] = $firstProduct['calculated_volume'] ?? null;

            // Рассчитываем объем, если есть шаблон и характеристики
            if (! empty($data['product_template_id']) && ! empty($attributes)) {
                $template = \App\Models\ProductTemplate::find($data['product_template_id']);
                if ($template && $template->formula) {
                    // Создаем копию атрибутов для формулы, включая quantity
                    $formulaAttributes = $attributes;
                    if (isset($data['quantity']) && is_numeric($data['quantity']) && $data['quantity'] > 0) {
                        $formulaAttributes['quantity'] = $data['quantity'];
                    }

                    // Логируем атрибуты для отладки
                    \Log::info('ReceiptResource: Attributes for formula', [
                        'template' => $template->name,
                        'attributes' => $attributes,
                        'formula_attributes' => $formulaAttributes,
                        'quantity' => $data['quantity'] ?? 'not set',
                        'formula' => $template->formula,
                    ]);

                    $testResult = $template->testFormula($formulaAttributes);
                    \Log::info('ReceiptResource: Formula result', $testResult);

                    if ($testResult['success']) {
                        $result = $testResult['result'];
                        $data['calculated_volume'] = $result;
                        \Log::info('ReceiptResource: Volume calculated and saved', [
                            'calculated_volume' => $result,
                        ]);
                    } else {
                        \Log::warning('ReceiptResource: Volume calculation failed', [
                            'error' => $testResult['error'],
                            'attributes' => $formulaAttributes,
                        ]);
                    }
                }

                // Формируем наименование из характеристик
                if (! empty($attributes)) {
                    $nameParts = [];
                    foreach ($template->attributes as $templateAttribute) {
                        $attributeKey = $templateAttribute->variable;
                        if ($templateAttribute->type !== 'text' && isset($attributes[$attributeKey]) && $attributes[$attributeKey] !== null && $attributes[$attributeKey] !== '') {
                            $nameParts[] = $attributes[$attributeKey];
                        }
                    }

                    if (! empty($nameParts)) {
                        $templateName = $template->name ?? 'Товар';
                        $data['name'] = $templateName.': '.implode(', ', $nameParts);
                        \Log::info('ReceiptResource: Name generated', ['name' => $data['name']]);
                    } else {
                        // Если не удалось сформировать имя из характеристик, используем название шаблона
                        $data['name'] = $template->name ?? 'Товар';
                        \Log::info('ReceiptResource: Name generated from template', ['name' => $data['name']]);
                    }
                }
            }

            // Удаляем поле products, так как оно не нужно в основной модели
            unset($data['products']);
        }

        \Log::info('ReceiptResource: Data after processing', $data);

        return $data;
    }
}
