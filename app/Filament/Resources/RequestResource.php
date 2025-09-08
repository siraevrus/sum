<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestResource\Pages;
use App\Models\ProductTemplate;
use App\Models\Request;
use App\UserRole;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RequestResource extends Resource
{
    protected static ?string $model = Request::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Запросы';

    protected static ?string $modelLabel = 'Запрос';

    protected static ?string $pluralModelLabel = 'Запросы';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return in_array($user->role->value, [
            'admin',
            'warehouse_worker',
            'sales_manager',
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
                                TextInput::make('title')
                                    ->label('Заголовок')
                                    ->required()
                                    ->maxLength(255),

                                Select::make('warehouse_id')
                                    ->label('Склад')
                                    ->options(fn () => \App\Models\Warehouse::optionsForCurrentUser())
                                    ->required()
                                    ->searchable(),

                                Select::make('product_template_id')
                                    ->label('Шаблон товара')
                                    ->options(ProductTemplate::pluck('name', 'id'))
                                    ->searchable()
                                    ->placeholder('Выберите шаблон (необязательно)')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        // Очищаем старые характеристики при смене шаблона
                                        $set('calculated_volume', null);

                                        // Добавляем динамические поля характеристик
                                        $templateId = $get('product_template_id');
                                        if ($templateId) {
                                            $template = ProductTemplate::with('attributes')->find($templateId);
                                            if ($template) {
                                                // Очищаем старые поля характеристик
                                                foreach ($template->attributes as $attribute) {
                                                    $set("attribute_{$attribute->variable}", null);
                                                }
                                            }
                                        }
                                    }),

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

                                        // Собираем все значения характеристик
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
                                                $set('calculated_volume', $result);
                                            } else {
                                                // Если расчет не удался, показываем ошибку
                                                $set('calculated_volume', 'Заполните поля: '.($testResult['error'] ?? 'Неизвестная ошибка'));
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

                                Select::make('status')
                                    ->label('Статус')
                                    ->options([
                                        Request::STATUS_PENDING => 'Ожидает рассмотрения',
                                        Request::STATUS_APPROVED => 'Одобрен',
                                    ])
                                    ->default(Request::STATUS_PENDING)
                                    ->required()
                                    ->visible(fn () => (bool) (Auth::user()?->isAdmin() ?? false))
                                    ->dehydrated(fn () => (bool) (Auth::user()?->isAdmin() ?? false)),

                            ]),

                        Textarea::make('description')
                            ->label('Описание')
                            ->rows(4)
                            ->maxLength(2000),

                        Textarea::make('admin_notes')
                            ->label('Заметки администратора')
                            ->rows(3)
                            ->maxLength(1000)
                            ->visible(function () {
                                return Auth::user()->role === 'admin';
                            }),
                    ]),

                Section::make('Характеристики товара')
                    ->schema([
                        // Динамические поля характеристик будут добавляться здесь
                    ])
                    ->visible(fn (Get $get) => $get('product_template_id') !== null)
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                        // Автоматически рассчитываем объем при изменении характеристик
                        $templateId = $get('product_template_id');
                        if (! $templateId) {
                            return;
                        }

                        $template = ProductTemplate::with('attributes')->find($templateId);
                        if (! $template) {
                            return;
                        }

                        // Собираем все значения характеристик
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
                                $set('calculated_volume', $result);
                            } else {
                                // Если расчет не удался, показываем ошибку
                                $set('calculated_volume', 'Заполните поля: '.($testResult['error'] ?? 'Неизвестная ошибка'));
                            }
                        } else {
                            // Если недостаточно данных для расчета, показываем подсказку
                            if (empty($numericAttributes)) {
                                $set('calculated_volume', 'Заполните числовые характеристики');
                            } else {
                                $set('calculated_volume', 'Формула не задана');
                            }
                        }
                    })
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

                                            // Если есть заполненные числовые характеристики и формула, рассчитываем объем
                                            if (! empty($numericAttributes) && $template->formula) {
                                                $testResult = $template->testFormula($numericAttributes);
                                                if ($testResult['success']) {
                                                    $result = $testResult['result'];
                                                    $set('calculated_volume', $result);
                                                } else {
                                                    // Если расчет не удался, показываем ошибку
                                                    $set('calculated_volume', 'Заполните поля: '.($testResult['error'] ?? 'Неизвестная ошибка'));
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

                                case 'text':
                                    $fields[] = TextInput::make($fieldName)
                                        ->label($attribute->full_name)
                                        ->required($attribute->is_required)
                                        ->live();
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
                                                    $set('calculated_volume', $result);
                                                } else {
                                                    // Если расчет не удался, показываем ошибку
                                                    $set('calculated_volume', 'Заполните поля: '.($testResult['error'] ?? 'Неизвестная ошибка'));
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
                            }
                        }

                        return $fields;
                    }),

                Section::make('Расчет объема')
                    ->schema([
                        TextInput::make('calculated_volume')
                            ->label('Рассчитанный объем')
                            ->disabled()
                            ->dehydrated(true)
                            ->formatStateUsing(function ($state) {
                                return is_numeric($state) ? number_format($state, 3, '.', ' ') : '0.000';
                            })
                            ->dehydrateStateUsing(function ($state) {
                                // Преобразуем отформатированную строку обратно в число перед сохранением
                                if (is_string($state)) {
                                    $normalized = str_replace([' ', '\u{00A0}'], '', $state);
                                    $normalized = str_replace(',', '.', $normalized);

                                    return (float) $normalized;
                                }

                                return $state;
                            })
                            ->suffix(function (Get $get) {
                                $templateId = $get('product_template_id');
                                if ($templateId) {
                                    $template = ProductTemplate::find($templateId);

                                    return $template ? $template->unit : '';
                                }

                                return '';
                            })
                            ->helperText('Объем рассчитывается автоматически на основе числовых характеристик товара по формуле шаблона'),
                    ])
                    ->visible(function (Get $get) {
                        return $get('product_template_id') !== null;
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Создатель')
                    ->sortable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => Request::STATUS_PENDING,
                        'info' => Request::STATUS_APPROVED,
                    ])
                    ->formatStateUsing(function (Request $record): string {
                        return $record->getStatusLabel();
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),

            ])
            ->emptyStateHeading('Нет запросов')
            ->emptyStateDescription('Создайте первый запрос, чтобы начать работу.')
            ->recordUrl(fn (\App\Models\Request $record) => self::getUrl('view', ['record' => $record]))
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(fn () => \App\Models\Warehouse::optionsForCurrentUser())
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        Request::STATUS_PENDING => 'Ожидает рассмотрения',
                        Request::STATUS_APPROVED => 'Одобрен',
                    ]),

            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label(''),
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->visible(function (\App\Models\Request $record): bool {
                        $user = Auth::user();
                        if (! $user) {
                            return false;
                        }

                        // Hide edit button for sales managers on approved requests
                        if ($user->role === UserRole::SALES_MANAGER && $record->status === Request::STATUS_APPROVED) {
                            return false;
                        }

                        // Hide edit button for warehouse workers on approved requests
                        if ($user->role === UserRole::WAREHOUSE_WORKER && $record->status === Request::STATUS_APPROVED) {
                            return false;
                        }

                        return true;
                    }),
                Tables\Actions\DeleteAction::make()->label(''),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListRequests::route('/'),
            'create' => Pages\CreateRequest::route('/create'),
            'view' => Pages\ViewRequest::route('/{record}'),
            'edit' => Pages\EditRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        // Администратор видит все запросы
        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return parent::getEloquentQuery();
        }

        // Остальные пользователи видят только свои запросы
        return parent::getEloquentQuery()
            ->where('user_id', $user?->id);
    }
}
