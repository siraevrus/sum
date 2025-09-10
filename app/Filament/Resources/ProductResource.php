<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    /**
     * Валидировать и ограничить значение calculated_volume
     */
    private static function validateCalculatedVolume(float $volume): ?float
    {
        $maxValue = 999999999.9999; // Максимум для decimal(15,4)
        
        if ($volume > $maxValue) {
            \Log::warning('ProductResource: Volume exceeds maximum value', [
                'calculated_volume' => $volume,
                'max_value' => $maxValue,
            ]);
            return null; // Возвращаем null вместо превышения
        }
        
        return $volume;
    }

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Товары';

    protected static ?string $modelLabel = 'Товар';

    protected static ?string $pluralModelLabel = 'Товары';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return in_array($user->role->value, [
            'admin',
            'operator',
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('warehouse_id')
                                    ->label('Склад')
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
                                    ->searchable(),

                                Select::make('producer_id')
                                    ->label('Производитель')
                                    ->options(\App\Models\Producer::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Выберите производителя')
                                    ->required(),

                                DatePicker::make('arrival_date')
                                    ->label('Дата поступления')
                                    ->required()
                                    ->default(now()),

                                Select::make('product_template_id')
                                    ->label('Шаблон товара')
                                    ->options(ProductTemplate::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->debounce(300)
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $set('calculated_volume', null);
                                        $set('name', '');
                                        $template = ProductTemplate::find($get('product_template_id'));
                                        if ($template) {
                                            foreach ($template->attributes as $attribute) {
                                                $set("attribute_{$attribute->variable}", null);
                                            }
                                            if ($template->formula) {
                                                $set('calculated_volume', 'Заполните характеристики для расчета объема');
                                            }
                                        }
                                    }),

                                TextInput::make('transport_number')
                                    ->label('Номер транспорта')
                                    ->maxLength(255),

                                TextInput::make('quantity')
                                    ->label('Количество')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->live()
                                    ->debounce(300)
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
                                        Log::info('Attributes for volume calculation (quantity)', [
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
                                                $validatedResult = self::validateCalculatedVolume($result);
                                                $set('calculated_volume', $validatedResult);

                                                // Логируем для отладки
                                                Log::info('Volume calculated from quantity change', [
                                                    'template' => $template->name,
                                                    'attributes' => $numericAttributes,
                                                    'result' => $result,
                                                ]);
                                            } else {
                                                // Если расчет не удался, показываем ошибку
                                                $set('calculated_volume', 'Заполните поля: '.($testResult['error'] ?? 'Неизвестная ошибка'));
                                                Log::warning('Volume calculation failed from quantity change', [
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
                                    }),

                                TextInput::make('name')
                                    ->label('Наименование')
                                    ->maxLength(255)
                                    ->disabled()
                                    ->helperText('Автоматически формируется из характеристик товара'),

                                Toggle::make('is_active')
                                    ->label('Активен')
                                    ->hidden()
                                    ->default(true),
                            ]),

                        // Компактная сетка для заметок
                        Grid::make(1)
                            ->schema([
                                Textarea::make('notes')
                                    ->label('Заметки')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Характеристики товара')
                    ->visible(fn (Get $get) => $get('product_template_id') !== null)
                    ->schema(function (Get $get) {
                        $templateId = $get('product_template_id');
                        if (! $templateId) {
                            return [];
                        }

                        $template = ProductTemplate::with('attributes')->find($templateId);
                        if (! $template) {
                            return [];
                        }

                        $fields = [];
                        foreach ($template->attributes as $attribute) {
                            $fieldName = "attribute_{$attribute->variable}";

                            switch ($attribute->type) {
                                case 'number':
                                    $fields[] = TextInput::make($fieldName)
                                        ->label($attribute->full_name)
                                        ->numeric()
                                        ->required($attribute->is_required)
                                        ->live()
                                        ->debounce(300)
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
                                            Log::info('Attributes for volume calculation (number)', [
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
                                                    $validatedResult = self::validateCalculatedVolume($result);
                                                    $set('calculated_volume', $validatedResult);

                                                    // Логируем для отладки
                                                    Log::info('Volume calculated', [
                                                        'template' => $template->name,
                                                        'attributes' => $numericAttributes,
                                                        'result' => $result,
                                                    ]);
                                                } else {
                                                    // Если расчет не удался, показываем ошибку
                                                    $set('calculated_volume', 'Заполните поля: '.($testResult['error'] ?? 'Неизвестная ошибка'));
                                                    Log::warning('Volume calculation failed', [
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
                                        ->label($attribute->full_name)
                                        ->required($attribute->is_required)
                                        ->live()
                                        ->debounce(300)
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

                                            // Если есть заполненные числовые характеристики и формула, рассчитываем объем
                                            if (! empty($numericAttributes) && $template->formula) {
                                                $testResult = $template->testFormula($numericAttributes);
                                                if ($testResult['success']) {
                                                    $result = $testResult['result'];
                                                    $validatedResult = self::validateCalculatedVolume($result);
                                                    $set('calculated_volume', $validatedResult);

                                                    // Логируем для отладки
                                                    Log::info('Volume calculated', [
                                                        'template' => $template->name,
                                                        'attributes' => $numericAttributes,
                                                        'result' => $result,
                                                    ]);
                                                } else {
                                                    // Если расчет не удался, показываем ошибку
                                                    $set('calculated_volume', 'Заполните поля: '.($testResult['error'] ?? 'Неизвестная ошибка'));
                                                    Log::warning('Volume calculation failed', [
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
                                        });
                                    break;

                                case 'select':
                                    $options = $attribute->options_array;
                                    $fields[] = Select::make($fieldName)
                                        ->label($attribute->full_name)
                                        ->options($options)
                                        ->required($attribute->is_required)
                                        ->live()
                                        ->debounce(300)
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

                                            // Если есть заполненные числовые характеристики и формула, рассчитываем объем
                                            if (! empty($numericAttributes) && $template->formula) {
                                                $testResult = $template->testFormula($numericAttributes);
                                                if ($testResult['success']) {
                                                    $result = $testResult['result'];
                                                    $validatedResult = self::validateCalculatedVolume($result);
                                                    $set('calculated_volume', $validatedResult);

                                                    // Логируем для отладки
                                                    Log::info('Volume calculated', [
                                                        'template' => $template->name,
                                                        'attributes' => $numericAttributes,
                                                        'result' => $result,
                                                    ]);
                                                } else {
                                                    // Если расчет не удался, показываем ошибку
                                                    $set('calculated_volume', 'Заполните поля: '.($testResult['error'] ?? 'Неизвестная ошибка'));
                                                    Log::warning('Volume calculation failed', [
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
                                        })
                                        ->dehydrateStateUsing(function ($state, $get) use ($options) {
                                            // Преобразуем индекс в значение для селектов
                                            if ($state !== null && is_numeric($state) && isset($options[$state])) {
                                                return $options[$state];
                                            }

                                            return $state;
                                        });
                                    break;
                            }
                        }

                        // Добавляем поле рассчитанного объема в конец
                        $fields[] = TextInput::make('calculated_volume')
                            ->label('Рассчитанный объем')
                            ->disabled()
                            ->live()
                            ->columnSpanFull()
                            ->formatStateUsing(function ($state) {
                                // Если это число - форматируем, если строка - показываем как есть
                                if (is_numeric($state)) {
                                    return number_format($state, 3, '.', ' ');
                                }

                                return $state ?: '0.000';
                            })
                            ->suffix(function (Get $get) {
                                $templateId = $get('product_template_id');
                                if ($templateId) {
                                    $template = ProductTemplate::find($templateId);

                                    return $template ? $template->unit : '';
                                }

                                return '';
                            })
                            ->helperText('Автоматически рассчитывается при заполнении характеристик или изменении количества');

                        // Обертываем поля в компактную сетку
                        return [
                            Grid::make(4) // 4 колонки для компактности
                                ->schema($fields),
                        ];
                    }),

                Section::make('Информация о корректировке')
                    ->schema([
                        Forms\Components\Placeholder::make('correction_info')
                            ->label('')
                            ->content(function (?Product $record): string {
                                if (! $record || ! $record->hasCorrection()) {
                                    return '';
                                }

                                $correctionText = $record->correction ?? 'Нет текста уточнения';
                                $updatedAt = $record->updated_at?->format('d.m.Y H:i') ?? 'Неизвестно';

                                return "⚠️ **У товара есть уточнение:** \"{$correctionText}\"\n\n".
                                       "*Дата внесения:* {$updatedAt}";
                            })
                            ->visible(fn (?Product $record): bool => $record && $record->hasCorrection())
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (?Product $record): bool => $record && $record->hasCorrection())
                    ->collapsible(false)
                    ->icon('heroicon-o-exclamation-triangle'),

                Section::make('Документы')
                    ->schema([
                        Forms\Components\View::make('documents-list')
                            ->view('filament.forms.components.documents-list')
                            ->viewData(function (?Product $record): array {
                                if (! $record || ! $record->document_path || empty($record->document_path)) {
                                    return ['documents' => []];
                                }

                                $documents = is_array($record->document_path) ? $record->document_path : [];
                                if (empty($documents)) {
                                    return ['documents' => []];
                                }

                                $documentsList = [];
                                foreach ($documents as $index => $document) {
                                    $fileName = basename($document);
                                    $fileUrl = asset('storage/'.$document);
                                    $documentsList[] = [
                                        'index' => $index + 1,
                                        'name' => $fileName,
                                        'url' => $fileUrl,
                                    ];
                                }

                                return ['documents' => $documentsList];
                            })
                            ->visible(fn (?Product $record): bool => $record && $record->document_path &&
                                is_array($record->document_path) &&
                                ! empty($record->document_path)
                            )
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (?Product $record): bool => $record && $record->document_path &&
                        is_array($record->document_path) &&
                        ! empty($record->document_path)
                    )
                    ->collapsible(false)
                    ->icon('heroicon-o-document'),

            ]);
    }

    /**
     * Рассчитать и установить объем товара
     */
    public static function calculateAndSetVolume(Set $set, Get $get, $template): void
    {
        // Собираем все значения характеристик
        $attributes = [];
        $formData = $get();

        foreach ($formData as $key => $value) {
            if (str_starts_with($key, 'attribute_') && $value !== null) {
                $attributeName = str_replace('attribute_', '', $key);
                $attributes[$attributeName] = $value;
            }
        }

        // Добавляем количество (но не в формулу, только для отображения)
        $quantity = $get('quantity') ?? 1;

        // Формируем наименование из характеристик, исключая текстовые атрибуты
        if (! empty($attributes)) {
            $nameParts = [];
            foreach ($template->attributes as $templateAttribute) {
                $attributeKey = $templateAttribute->variable;
                if ($templateAttribute->type !== 'text' && isset($attributes[$attributeKey]) && $attributes[$attributeKey] !== null) {
                    $nameParts[] = $attributes[$attributeKey];
                }
            }

            if (! empty($nameParts)) {
                // Добавляем название шаблона в начало
                $templateName = $template->name ?? 'Товар';
                $generatedName = $templateName.': '.implode(', ', $nameParts);
                $set('name', $generatedName);
            } else {
                $set('name', $template->name ?? 'Товар');
            }

            // Рассчитываем объем только для числовых характеристик
            $numericAttributes = [];
            foreach ($attributes as $key => $value) {
                if (is_numeric($value)) {
                    $numericAttributes[$key] = $value;
                }
            }

            if (! empty($numericAttributes)) {
                $testResult = $template->testFormula($numericAttributes);
                if ($testResult['success']) {
                    $result = $testResult['result'];
                    $validatedResult = self::validateCalculatedVolume($result);
                    $set('calculated_volume', $validatedResult);
                }
            }
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Наименование')
                    ->searchable()
                    ->sortable()
                    ->color(function (?Product $record): ?string {
                        return $record && $record->hasCorrection() ? 'danger' : null;
                    })
                    ->formatStateUsing(function (string $state, ?Product $record): string {
                        if ($record && $record->hasCorrection()) {
                            return '⚠️ ' . $state;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->sortable(),

                Tables\Columns\TextColumn::make('producer.name')
                    ->label('Производитель')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Поступило')
                    ->sortable()
                    ->badge()
                    ->color(function (string $state): string {
                        if ($state > 10) {
                            return 'success';
                        }
                        if ($state > 0) {
                            return 'warning';
                        }

                        return 'danger';
                    }),

                Tables\Columns\TextColumn::make('sold_quantity')
                    ->label('Продано')
                    ->sortable()
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('calculated_volume')
                    ->label('Объем')
                    ->formatStateUsing(function ($state) {
                        return $state ? number_format($state, 3, '.', ' ') : '0.000';
                    })
                    ->suffix(function (?Product $record): string {
                        return $record?->template?->unit ?? '';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('arrival_date')
                    ->label('Дата поступления')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            Product::STATUS_IN_STOCK => 'success',
                            Product::STATUS_IN_TRANSIT => 'warning',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            Product::STATUS_IN_STOCK => 'На складе',
                            Product::STATUS_IN_TRANSIT => 'В пути',
                            default => $state,
                        };
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->hidden()
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Создатель')
                    ->sortable(),

            ])
            ->emptyStateHeading('Нет товаров')
            ->emptyStateDescription('Создайте первый товар, чтобы начать работу. Используйте фильтр по статусу для просмотра товаров в пути. Товары со статусом "В пути" автоматически появляются в разделе "Приемка".')
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        Product::STATUS_IN_STOCK => 'На складе',
                        Product::STATUS_IN_TRANSIT => 'В пути',
                    ])
                    ->default(Product::STATUS_IN_STOCK),

                SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(fn () => Warehouse::optionsForCurrentUser())
                    ->searchable(),

                SelectFilter::make('producer_id')
                    ->label('Производитель')
                    ->options(function () {
                        $producers = \App\Models\Producer::whereHas('products')->get();
                        $options = [];
                        foreach ($producers as $producer) {
                            $productCount = $producer->products()->count();
                            $options[$producer->id] = "{$producer->name} ({$productCount})";
                        }

                        return $options;
                    })
                    ->searchable(),

                Filter::make('arrival_date_range')
                    ->label('Период поступления')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')->label('С даты'),
                        Forms\Components\DatePicker::make('date_to')->label('По дату'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->where('arrival_date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->where('arrival_date', '<=', $date),
                            );
                    }),

                Filter::make('has_correction')
                    ->label('Корректировка')
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['has_correction'],
                            fn (Builder $query): Builder => $query->where('correction_status', 'correction')
                        );
                    })
                    ->form([
                        Forms\Components\Checkbox::make('has_correction')
                            ->label('Показать только товары с уточнениями'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label(''),
                Tables\Actions\EditAction::make()->label(''),
                Tables\Actions\Action::make('mark_in_transit')
                    ->label('')
                    ->icon('heroicon-o-truck')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (\App\Models\Product $record): bool => $record->status === Product::STATUS_IN_STOCK)
                    ->action(function (\App\Models\Product $record): void {
                        $record->markInTransit();
                        \Filament\Notifications\Notification::make()
                            ->title('Товар переведен в статус "В пути"')
                            ->body('Товар теперь отображается в разделе "Приемка"')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Перевести товар в статус "В пути"')
                    ->modalDescription('Товар будет перемещен в раздел товаров в пути и появится в разделе "Приемка".')
                    ->modalSubmitActionLabel('Перевести'),

                Tables\Actions\Action::make('mark_in_stock')
                    ->label('')
                    ->icon('heroicon-o-home')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (\App\Models\Product $record): bool => $record->status === Product::STATUS_IN_TRANSIT)
                    ->action(function (\App\Models\Product $record): void {
                        $record->markInStock();
                        \Filament\Notifications\Notification::make()
                            ->title('Товар переведен в статус "На складе"')
                            ->body('Товар убран из раздела "Приемка"')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Перевести товар в статус "На складе"')
                    ->modalDescription('Товар будет перемещен в раздел товаров на складе и убран из раздела "Приемка".')
                    ->modalSubmitActionLabel('Перевести'),

                Tables\Actions\DeleteAction::make()->label(''),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_in_transit')
                        ->label('Перевести в путь')
                        ->icon('heroicon-o-truck')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each(fn (\App\Models\Product $record) => $record->markInTransit());
                            \Filament\Notifications\Notification::make()
                                ->title("{$records->count()} товаров переведено в статус \"В пути\"")
                                ->body('Товары теперь отображаются в разделе "Приемка"')
                                ->success()
                                ->send();
                        })
                        ->modalHeading('Перевести товары в статус "В пути"')
                        ->modalDescription('Выбранные товары будут перемещены в раздел товаров в пути.')
                        ->modalSubmitActionLabel('Перевести'),

                    Tables\Actions\BulkAction::make('mark_in_stock')
                        ->label('Перевести на склад')
                        ->icon('heroicon-o-home')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each(fn (\App\Models\Product $record) => $record->markInStock());
                            \Filament\Notifications\Notification::make()
                                ->title("{$records->count()} товаров переведено в статус \"На складе\"")
                                ->body('Товары убраны из раздела "Приемка"')
                                ->success()
                                ->send();
                        })
                        ->modalHeading('Перевести товары в статус "На складе"')
                        ->modalDescription('Выбранные товары будут перемещены в раздел товаров на складе.')
                        ->modalSubmitActionLabel('Перевести'),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                // Tables\Actions\Action::make('export')
                //     ->label('Экспорт')
                //     ->icon('heroicon-o-arrow-down-tray')
                //     ->url(route('products.export'))
                //     ->openUrlInNewTab(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        $base = parent::getEloquentQuery();

        if (! $user) {
            return $base->whereRaw('1 = 0');
        }

        if ($user->role->value === 'admin') {
            return $base;
        }

        // Не админ — только свой склад
        if ($user->warehouse_id) {
            return $base->where('warehouse_id', $user->warehouse_id);
        }

        return $base->whereRaw('1 = 0');
    }
}
