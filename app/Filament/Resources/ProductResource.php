<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductResource extends Resource
{
    use \App\Traits\SafeFilamentFormatting;

    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Товары';

    protected static ?string $modelLabel = 'Товар';

    protected static ?string $pluralModelLabel = 'Поступления товаров';

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
                        Grid::make(4)
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

                                TextInput::make('transport_number')
                                    ->label('Номер транспорта')
                                    ->maxLength(255),

                                Toggle::make('is_active')
                                    ->label('Активен')
                                    ->hidden()
                                    ->default(true),
                            ]),

                    ]),

                Section::make('Товары')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Select::make('product_template_id')
                                    ->label('Шаблон товара')
                                    ->options(ProductTemplate::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->live(onBlur: true)
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

                                TextInput::make('name')
                                    ->label('Наименование')
                                    ->maxLength(255)
                                    ->disabled()
                                    ->helperText('Автоматически формируется из характеристик товара'),

                                TextInput::make('quantity')
                                    ->label('Количество')
                                    ->inputMode('decimal')
                                    ->default(1)
                                    ->maxLength(10)
                                    ->required()
                                    ->rules(['regex:/^\d*([.,]\d*)?$/'])
                                    ->validationMessages([
                                        'regex' => 'Поле должно содержать только цифры и одну запятую или точку',
                                    ])
                                    ->live(onBlur: true)
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
                                                // Нормализуем числовые значения: заменяем запятую на точку
                                                $normalizedValue = is_string($value) ? str_replace(',', '.', $value) : $value;
                                                $attributes[$attributeName] = $normalizedValue;
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
                                        // Нормализуем количество: заменяем запятую на точку
                                        $normalizedQuantity = is_string($quantity) ? str_replace(',', '.', $quantity) : $quantity;
                                        if (is_numeric($normalizedQuantity) && $normalizedQuantity > 0) {
                                            $numericAttributes['quantity'] = $normalizedQuantity;
                                        }

                                        // Если есть заполненные числовые характеристики и формула, рассчитываем объем
                                        if (! empty($numericAttributes) && $template->formula) {
                                            $testResult = $template->testFormula($numericAttributes);
                                            if ($testResult['success']) {
                                                $result = $testResult['result'];

                                                // Проверяем на превышение лимита
                                                $maxValue = 999999999.9999; // Максимум для decimal(15,4)
                                                if ($result > $maxValue) {
                                                    $set('calculated_volume', 'Объем превышает максимальное значение');
                                                    Log::warning('Volume exceeds maximum value from quantity change', [
                                                        'template' => $template->name,
                                                        'attributes' => $numericAttributes,
                                                        'result' => $result,
                                                        'max_value' => $maxValue,
                                                    ]);
                                                } else {
                                                    $set('calculated_volume', $result);

                                                    // Логируем для отладки
                                                    Log::info('Volume calculated from quantity change', [
                                                        'template' => $template->name,
                                                        'attributes' => $numericAttributes,
                                                        'result' => $result,
                                                    ]);
                                                }
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
                            ]),

                        // Шаблоны товара
                        Grid::make(3)
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
                                                ->inputMode('decimal')
                                                ->maxLength(10)
                                                ->required($attribute->is_required)
                                                ->rules(['regex:/^\d*([.,]\d*)?$/'])
                                                ->validationMessages([
                                                    'regex' => 'Поле должно содержать только цифры и одну запятую или точку',
                                                ])
                                                ->key("number_attr_{$attribute->id}_{$attribute->variable}")
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Set $set, Get $get) use ($template) {
                                                    // Рассчитываем объем при изменении характеристики
                                                    $attributes = [];
                                                    $formData = $get();

                                                    foreach ($formData as $key => $value) {
                                                        if (str_starts_with($key, 'attribute_') && $value !== null && $value !== '') {
                                                            $attributeName = str_replace('attribute_', '', $key);
                                                            // Нормализуем числовые значения: заменяем запятую на точку
                                                            $normalizedValue = is_string($value) ? str_replace(',', '.', $value) : $value;
                                                            $attributes[$attributeName] = $normalizedValue;
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
                                                            // Проверяем на превышение лимита
                                                            $maxValue = 999999999.9999; // Максимум для decimal(15,4)
                                                            if ($result > $maxValue) {
                                                                $set('calculated_volume', 'Объем превышает максимальное значение');
                                                                Log::warning('Volume exceeds maximum value', [
                                                                    'template' => $template->name,
                                                                    'attributes' => $numericAttributes,
                                                                    'result' => $result,
                                                                    'max_value' => $maxValue,
                                                                ]);
                                                            } else {
                                                                $set('calculated_volume', $result);

                                                                // Логируем для отладки
                                                                Log::info('Volume calculated', [
                                                                    'template' => $template->name,
                                                                    'attributes' => $numericAttributes,
                                                                    'result' => $result,
                                                                ]);
                                                            }
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
                                                ->maxLength(255)
                                                ->required($attribute->is_required)
                                                ->key("text_attr_{$attribute->id}_{$attribute->variable}")
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Set $set, Get $get) use ($template) {
                                                    // Рассчитываем объем при изменении характеристики
                                                    $attributes = [];
                                                    $formData = $get();

                                                    foreach ($formData as $key => $value) {
                                                        if (str_starts_with($key, 'attribute_') && $value !== null && $value !== '') {
                                                            $attributeName = str_replace('attribute_', '', $key);
                                                            // Нормализуем числовые значения: заменяем запятую на точку
                                                            $normalizedValue = is_string($value) ? str_replace(',', '.', $value) : $value;
                                                            $attributes[$attributeName] = $normalizedValue;
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
                                                            // Проверяем на превышение лимита
                                                            $maxValue = 999999999.9999; // Максимум для decimal(15,4)
                                                            if ($result > $maxValue) {
                                                                $set('calculated_volume', 'Объем превышает максимальное значение');
                                                                Log::warning('Volume exceeds maximum value', [
                                                                    'template' => $template->name,
                                                                    'attributes' => $numericAttributes,
                                                                    'result' => $result,
                                                                    'max_value' => $maxValue,
                                                                ]);
                                                            } else {
                                                                $set('calculated_volume', $result);

                                                                // Логируем для отладки
                                                                Log::info('Volume calculated', [
                                                                    'template' => $template->name,
                                                                    'attributes' => $numericAttributes,
                                                                    'result' => $result,
                                                                ]);
                                                            }
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
                                                ->key("select_attr_{$attribute->id}_{$attribute->variable}")
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Set $set, Get $get) use ($template) {
                                                    // Рассчитываем объем при изменении характеристики
                                                    $attributes = [];
                                                    $formData = $get();

                                                    foreach ($formData as $key => $value) {
                                                        if (str_starts_with($key, 'attribute_') && $value !== null && $value !== '') {
                                                            $attributeName = str_replace('attribute_', '', $key);
                                                            // Нормализуем числовые значения: заменяем запятую на точку
                                                            $normalizedValue = is_string($value) ? str_replace(',', '.', $value) : $value;
                                                            $attributes[$attributeName] = $normalizedValue;
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
                                                            // Проверяем на превышение лимита
                                                            $maxValue = 999999999.9999; // Максимум для decimal(15,4)
                                                            if ($result > $maxValue) {
                                                                $set('calculated_volume', 'Объем превышает максимальное значение');
                                                                Log::warning('Volume exceeds maximum value', [
                                                                    'template' => $template->name,
                                                                    'attributes' => $numericAttributes,
                                                                    'result' => $result,
                                                                    'max_value' => $maxValue,
                                                                ]);
                                                            } else {
                                                                $set('calculated_volume', $result);

                                                                // Логируем для отладки
                                                                Log::info('Volume calculated', [
                                                                    'template' => $template->name,
                                                                    'attributes' => $numericAttributes,
                                                                    'result' => $result,
                                                                ]);
                                                            }
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

                                                    return e($state);
                                                });
                                            break;
                                    }
                                }

                                // Добавляем поле рассчитанного объема в конец
                                $fields[] = TextInput::make('calculated_volume')
                                    ->label('Рассчитанный объем')
                                    ->disabled()
                                    ->key(fn (Get $get) => 'calculated_volume_'.($get('product_template_id') ?? 'none'))
                                    ->columnSpanFull()
                                    ->formatStateUsing(function ($state) {
                                        // Если это число - форматируем, если строка - показываем как есть
                                        if (is_numeric($state)) {
                                            return number_format($state, 3, '.', ' ');
                                        }

                                        return e($state ?: '0.000');
                                    })
                                    ->suffix(function (Get $get) {
                                        $templateId = $get('product_template_id');
                                        if ($templateId) {
                                            $template = ProductTemplate::find($templateId);

                                            return $template ? $template->unit : '';
                                        }

                                        return '';
                                    })
                                    ->helperText(function (Get $get) {
                                        $templateId = $get('product_template_id');
                                        if ($templateId) {
                                            $template = ProductTemplate::find($templateId);
                                            if ($template && $template->formula) {
                                                return 'Автоматически рассчитывается при заполнении характеристик или изменении количества. Если объем не отображается, возможно, результат превышает максимальное значение.';
                                            }
                                        }

                                        return 'Автоматически рассчитывается при заполнении характеристик или изменении количества.';
                                    });

                                // Обертываем поля в компактную сетку
                                return [
                                    Grid::make(4) // 4 колонки для компактности
                                        ->schema($fields),
                                ];
                            }),
                    ]),

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
                        FileUpload::make('document_path')
                            ->label('Документы')
                            ->directory('documents')
                            ->multiple()
                            ->maxFiles(5)
                            ->maxSize(51200) // 50MB
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/*',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'text/plain',
                            ])
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->imagePreviewHeight('250')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(false)
                    ->icon('heroicon-o-document'),

                Section::make('Дополнительная информация')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Textarea::make('notes')
                                    ->label('Заметки')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->collapsible(false)
                    ->icon('heroicon-o-information-circle'),

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
        // Нормализуем количество: заменяем запятую на точку
        $normalizedQuantity = is_string($quantity) ? str_replace(',', '.', $quantity) : $quantity;

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

                    // Проверяем на превышение лимита
                    $maxValue = 999999999.9999; // Максимум для decimal(15,4)
                    if ($result > $maxValue) {
                        $set('calculated_volume', 'Объем превышает максимальное значение');
                        Log::warning('Volume exceeds maximum value in calculateVolumeForItem', [
                            'template' => $template->name,
                            'attributes' => $numericAttributes,
                            'result' => $result,
                            'max_value' => $maxValue,
                        ]);
                    } else {
                        $set('calculated_volume', $result);
                    }
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
                            return '⚠️ '.e($state);
                        }

                        return e($state);
                    }),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Склад')
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

                Tables\Columns\ViewColumn::make('document_path')
                    ->label('Документы')
                    ->view('tables.columns.documents')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('transport_number')
                    ->label('Номер транспорта')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('producer.name')
                    ->label('Производитель')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('sold_quantity')
                    ->label('Продано')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'in_transit' => 'warning',
                        'for_receipt' => 'info',
                        'correction' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in_stock' => 'На складе',
                        'in_transit' => 'В пути',
                        'for_receipt' => 'На приемку',
                        'correction' => 'Коррекция',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('shipping_location')
                    ->label('Место отгрузки')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('shipping_date')
                    ->label('Дата отгрузки')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expected_arrival_date')
                    ->label('Ожидаемая дата прибытия')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('actual_arrival_date')
                    ->label('Фактическая дата прибытия')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Заметки')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),

                Tables\Columns\TextColumn::make('correction')
                    ->label('Коррекция')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),

                Tables\Columns\TextColumn::make('correction_status')
                    ->label('Статус коррекции')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'correction' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'correction' => 'Коррекция',
                        default => 'Нет',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Дата обновления')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Сотрудник')
                    ->sortable(),

            ])
            ->emptyStateHeading('Нет товаров')
            ->emptyStateDescription('Создайте первый товар, чтобы начать работу. Используйте фильтр по статусу для просмотра товаров в пути. Товары со статусом "В пути" автоматически появляются в разделе "Приемка".')
            ->filters([
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

                SelectFilter::make('product_template_id')
                    ->label('Тип товара')
                    ->multiple()
                    ->options(function () {
                        $templates = ProductTemplate::whereHas('products')->get();
                        $options = [];
                        foreach ($templates as $template) {
                            $productCount = $template->products()->count();
                            $options[$template->id] = "{$template->name} ({$productCount})";
                        }

                        return $options;
                    })
                    ->searchable()
                    ->placeholder('Все типы товаров'),

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

                Tables\Actions\DeleteAction::make()->label(''),
            ])
            ->bulkActions([])
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

        $base = parent::getEloquentQuery()->where('status', '!=', 'for_receipt');

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
