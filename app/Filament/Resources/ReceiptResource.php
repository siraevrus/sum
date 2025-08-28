<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceiptResource\Pages;
use App\Models\ProductInTransit;
use App\Models\ProductTemplate;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
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

class ReceiptResource extends Resource
{
    protected static ?string $model = ProductInTransit::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Приемка';

    protected static ?string $modelLabel = 'Приемка';

    protected static ?string $pluralModelLabel = 'Приемка';

    protected static ?int $navigationSort = 7;

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // Приемка не доступна оператору и менеджеру по продажам
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

                                Select::make('warehouse_id')
                                    ->label('Склад назначения')
                                    ->options(fn () => Warehouse::optionsForCurrentUser())
                                    ->required()
                                    ->searchable(),

                                TextInput::make('shipping_location')
                                    ->label('Место отгрузки')
                                    ->maxLength(255)
                                    ->required(),

                                DatePicker::make('shipping_date')
                                    ->label('Дата отгрузки')
                                    ->required(),

                                TextInput::make('transport_number')
                                    ->label('Номер транспорта')
                                    ->maxLength(255),

                                DatePicker::make('expected_arrival_date')
                                    ->label('Ожидаемая дата прибытия'),

                                DatePicker::make('actual_arrival_date')
                                    ->label('Фактическая дата прибытия'),
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
                                            ->required()
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                // Очищаем характеристики при смене шаблона
                                                $set('attributes', []);
                                                $set('calculated_volume', null);
                                                $set('name', '');
                                            }),

                                        TextInput::make('name')
                                            ->label('Наименование товара')
                                            ->disabled()
                                            ->helperText('Автоматически формируется из характеристик товара'),

                                        TextInput::make('producer')
                                            ->label('Производитель')
                                            ->maxLength(255),

                                        TextInput::make('quantity')
                                            ->label('Количество')
                                            ->numeric()
                                            ->minValue(1)
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                self::calculateVolumeForItem($set, $get);
                                            }),

                                        TextInput::make('calculated_volume')
                                            ->label('Объем')
                                            ->disabled()
                                            ->formatStateUsing(function ($state) {
                                                return $state ? number_format($state, 3, '.', ' ') : '0.000';
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
                                    ]),

                                // Динамические поля характеристик
                                Grid::make(3)
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
                                                        ->label($attribute->name)
                                                        ->numeric()
                                                        ->required($attribute->is_required)
                                                        ->live()
                                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                                            self::calculateVolumeForItem($set, $get);
                                                        });
                                                    break;

                                                case 'text':
                                                    $fields[] = TextInput::make($fieldName)
                                                        ->label($attribute->name)
                                                        ->required($attribute->is_required)
                                                        ->live()
                                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                                            self::calculateVolumeForItem($set, $get);
                                                        });
                                                    break;

                                                case 'select':
                                                    $options = $attribute->options_array;
                                                    $fields[] = Select::make($fieldName)
                                                        ->label($attribute->name)
                                                        ->options($options)
                                                        ->required($attribute->is_required)
                                                        ->live()
                                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                                            self::calculateVolumeForItem($set, $get);
                                                        });
                                                    break;
                                            }
                                        }

                                        return $fields;
                                    })
                                    ->visible(fn (Get $get) => $get('product_template_id') !== null),

                                Textarea::make('description')
                                    ->label('Описание товара')
                                    ->rows(2)
                                    ->maxLength(1000)
                                    ->columnSpanFull(),
                            ])
                            ->addActionLabel('Добавить товар')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Товар')
                            ->defaultItems(1)
                            ->minItems(1)
                            ->maxItems(50),
                    ]),

                Section::make('Дополнительная информация')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Заметки')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Наименование')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('template.name')
                    ->label('Шаблон')
                    ->sortable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->sortable(),

                Tables\Columns\TextColumn::make('shipping_location')
                    ->label('Место отгрузки')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('shipping_date')
                    ->label('Дата отгрузки')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('producer')
                    ->label('Производитель')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Количество')
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('calculated_volume')
                    ->label('Объем')
                    ->formatStateUsing(function ($state) {
                        return $state ? number_format($state, 3, '.', ' ') : '0.000';
                    })
                    ->suffix(function (ProductInTransit $record): string {
                        return $record->template?->unit ?? '';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('expected_arrival_date')
                    ->label('Ожидаемая дата')
                    ->date()
                    ->sortable()
                    ->color(function (ProductInTransit $record): string {
                        $expected = $record->expected_arrival_date;
                        if (! $expected) {
                            return 'success';
                        }

                        return ($record->status === ProductInTransit::STATUS_IN_TRANSIT && $expected < now()) ? 'danger' : 'success';
                    }),

                Tables\Columns\TextColumn::make('actual_arrival_date')
                    ->label('Дата прибытия')
                    ->date()
                    ->sortable()
                    ->color('success'),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Создатель')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => [ProductInTransit::STATUS_ORDERED],
                        'info' => [ProductInTransit::STATUS_IN_TRANSIT],
                        'success' => [ProductInTransit::STATUS_ARRIVED, ProductInTransit::STATUS_RECEIVED],
                        'danger' => [ProductInTransit::STATUS_CANCELLED],
                    ])
                    ->formatStateUsing(fn (ProductInTransit $record): string => $record->getStatusLabel())
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(fn () => Warehouse::optionsForCurrentUser())
                    ->searchable(),

                SelectFilter::make('shipping_location')
                    ->label('Место отгрузки')
                    ->options(function () {
                        $locations = ProductInTransit::query()
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
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label(''),
                Tables\Actions\ViewAction::make()->label(''),
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
            'index' => Pages\ListReceipts::route('/'),
            'create' => Pages\CreateReceipt::route('/create'),
            'view' => Pages\ViewReceipt::route('/{record}'),
            'edit' => Pages\EditReceipt::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $base = parent::getEloquentQuery()
            ->whereIn('status', [
                ProductInTransit::STATUS_ORDERED,
                ProductInTransit::STATUS_IN_TRANSIT,
                ProductInTransit::STATUS_ARRIVED,
                ProductInTransit::STATUS_RECEIVED,
            ])
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
                // Добавляем название шаблона в начало
                $templateName = $template->name ?? 'Товар';
                $generatedName = $templateName.': '.implode(', ', $nameParts);
                $set('name', $generatedName);
            }

            // Рассчитываем объем
            $testResult = $template->testFormula($attributes);
            if ($testResult['success']) {
                $set('calculated_volume', $testResult['result']);
            }
        }
    }
}
