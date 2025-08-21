<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceiptResource\Pages;
use App\Models\ProductInTransit;
use App\Models\ProductTemplate;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
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

                Section::make('Товар')
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
                                    }),

                                TextInput::make('name')
                                    ->label('Наименование')
                                    ->maxLength(255)
                                    ->disabled()
                                    ->helperText('Автоматически формируется из характеристик товара (нередактируемое)'),

                                TextInput::make('producer')
                                    ->label('Производитель')
                                    ->maxLength(255),

                                TextInput::make('quantity')
                                    ->label('Количество')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        self::calculateVolumeForItem($set, $get);
                                    }),

                                Textarea::make('description')
                                    ->label('Описание')
                                    ->rows(2)
                                    ->maxLength(1000)
                                    ->columnSpan(2),
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
                                                ->label($attribute->full_name)
                                                ->numeric()
                                                ->required($attribute->is_required)
                                                ->live()
                                                ->afterStateUpdated(function (Set $set, Get $get) {
                                                    self::calculateVolumeForItem($set, $get);
                                                });
                                            break;

                                        case 'text':
                                            $fields[] = TextInput::make($fieldName)
                                                ->label($attribute->full_name)
                                                ->required($attribute->is_required)
                                                ->live()
                                                ->afterStateUpdated(function (Set $set, Get $get) {
                                                    self::calculateVolumeForItem($set, $get);
                                                });
                                            break;

                                        case 'select':
                                            $options = $attribute->options_array;
                                            $fields[] = Select::make($fieldName)
                                                ->label($attribute->full_name)
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

                        // Поле для рассчитанного объема
                        Grid::make(2)
                            ->schema([
                                TextInput::make('calculated_volume')
                                    ->label('Рассчитанный объем')
                                    ->disabled()
                                    ->suffix(function (Get $get) {
                                        $templateId = $get('product_template_id');
                                        if ($templateId) {
                                            $template = ProductTemplate::find($templateId);

                                            return $template ? $template->unit : '';
                                        }

                                        return '';
                                    }),
                            ])
                            ->visible(fn (Get $get) => $get('product_template_id') !== null),
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
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ' ',
                    )
                    ->suffix(function (ProductInTransit $record): string {
                        return $record->template?->unit ?? '';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('expected_arrival_date')
                    ->label('Ожидаемая дата')
                    ->date()
                    ->sortable()
                    ->color(function (ProductInTransit $record): string {
                        return $record->isOverdue() ? 'danger' : 'success';
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
                        'success' => ProductInTransit::STATUS_ARRIVED,
                    ])
                    ->formatStateUsing(function (ProductInTransit $record): string {
                        return $record->getStatusLabel();
                    })
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
                        $locations = ProductInTransit::getShippingLocations();

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
        $query = parent::getEloquentQuery()
            ->where('status', ProductInTransit::STATUS_ARRIVED)
            ->where('is_active', true);

        // Админы видят все товары, обычные пользователи только по своей компании
        if ($user && $user->role->value !== 'admin' && $user->company_id) {
            $query->whereHas('warehouse', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        return $query;
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
