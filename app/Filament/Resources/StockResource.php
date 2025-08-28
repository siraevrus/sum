<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages;
use App\Models\Product;
use App\Models\StockGroup;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StockResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Остатки';

    protected static ?string $modelLabel = 'Остаток';

    protected static ?string $pluralModelLabel = 'Остатки';

    protected static ?int $navigationSort = 7;

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
                Forms\Components\Select::make('warehouse_id')
                    ->label('Склад')
                    ->options(fn () => Warehouse::optionsForCurrentUser())
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('name')
                    ->label('Наименование')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('quantity')
                    ->label('Количество')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('producer')
                    ->label('Производитель')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('actual_arrival_date')
                    ->label('Дата поступления')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Наименование')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function (StockGroup $record): string {
                        return $record->getFullName();
                    }),

                Tables\Columns\TextColumn::make('producer')
                    ->label('Производитель')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('Доступное количество')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(function (StockGroup $record): string {
                        return $record->getQuantityColor();
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Итого')
                    ),

                Tables\Columns\TextColumn::make('total_volume')
                    ->label('Доступный объем (м³)')
                    ->formatStateUsing(function ($state) {
                        return $state ? number_format($state, 3, '.', ' ') : '0.000';
                    })
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Итого (м³)')
                            ->formatStateUsing(function ($state) {
                                return number_format($state, 3, '.', ' ');
                            })
                    ),

                Tables\Columns\TextColumn::make('product_count')
                    ->label('Кол-во позиций')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('last_arrival_date')
                    ->label('Последнее поступление')
                    ->date()
                    ->sortable()
                    ->formatStateUsing(function (StockGroup $record): string {
                        return $record->getLastArrivalInfo();
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(fn () => Warehouse::optionsForCurrentUser()),
                Tables\Filters\SelectFilter::make('producer')
                    ->label('Производитель')
                    ->options(function () {
                        $producers = Product::getProducers();

                        return array_combine($producers, $producers);
                    }),
                Tables\Filters\Filter::make('in_stock')
                    ->label('В наличии')
                    ->query(function (Builder $query): Builder {
                        return $query->where('total_quantity', '>', 0);
                    }),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Низкий остаток')
                    ->query(function (Builder $query): Builder {
                        return $query->where('total_quantity', '<=', 10)
                                   ->where('total_quantity', '>', 0);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Детали группы товаров')
                    ->modalContent(function (StockGroup $record): string {
                        $attributesText = $record->getAttributesText();
                        $templateName = $record->template?->name ?? 'Неизвестный шаблон';
                        
                        return "
                            <div class='space-y-4'>
                                <div>
                                    <strong>Шаблон:</strong> {$templateName}
                                </div>
                                <div>
                                    <strong>Производитель:</strong> {$record->producer}
                                </div>
                                <div>
                                    <strong>Склад:</strong> {$record->warehouse?->name}
                                </div>
                                <div>
                                    <strong>Общее количество:</strong> {$record->total_quantity}
                                </div>
                                <div>
                                    <strong>Общий объем:</strong> " . number_format($record->total_volume ?? 0, 3, '.', ' ') . " м³
                                </div>
                                <div>
                                    <strong>Количество позиций:</strong> {$record->product_count}
                                </div>
                                <div>
                                    <strong>Характеристики:</strong> {$attributesText}
                                </div>
                                <div>
                                    <strong>Первое поступление:</strong> {$record->first_arrival_date?->format('d.m.Y')}
                                </div>
                                <div>
                                    <strong>Последнее поступление:</strong> {$record->last_arrival_date?->format('d.m.Y')}
                                </div>
                            </div>
                        ";
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Закрыть'),
            ])
            ->bulkActions([
                // Нет пакетных действий
            ])
            ->emptyStateHeading('Нет товаров на складе')
            ->emptyStateDescription('Товары появятся здесь после поступления на склад. Товары с одинаковыми характеристиками автоматически группируются.')
            ->defaultSort('total_quantity', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        if (! $user) {
            return Product::query()->whereRaw('1 = 0');
        }

        $baseQuery = Product::query()
            ->where('status', Product::STATUS_IN_STOCK)
            ->where('is_active', true);

        if ($user->role->value !== 'admin' && $user->warehouse_id) {
            $baseQuery->where('warehouse_id', $user->warehouse_id);
        }

        // Получаем сгруппированные данные
        $groupedData = Product::getGroupedStock($baseQuery);
        
        // Создаем коллекцию StockGroup моделей с уникальными ID
        $stockGroups = collect();
        foreach ($groupedData as $index => $group) {
            $stockGroup = new StockGroup();
            $stockGroup->id = $index + 1; // Уникальный ID для каждой группы
            $stockGroup->fill([
                'product_template_id' => $group->product_template_id,
                'warehouse_id' => $group->warehouse_id,
                'producer' => $group->producer,
                'attributes' => $group->attributes,
                'total_quantity' => $group->total_quantity,
                'total_volume' => $group->total_volume,
                'product_count' => $group->product_count,
                'name' => $group->name,
                'description' => $group->description,
                'first_arrival_date' => $group->first_arrival_date,
                'last_arrival_date' => $group->last_arrival_date,
            ]);
            $stockGroups->push($stockGroup);
        }
        
        // Возвращаем query builder с сгруппированными данными
        return StockGroup::query()->whereIn('id', $stockGroups->pluck('id'));
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
            'index' => Pages\ListStocks::route('/'),
        ];
    }

    /**
     * Для совместимости: уникальный ключ записи для сгруппированных данных
     */
    public static function getTableRecordKey($record): string
    {
        if (is_object($record) && method_exists($record, 'getGroupingKey')) {
            return $record->getGroupingKey();
        }

        if (is_object($record) && method_exists($record, 'getAttribute')) {
            return (string) ($record->getAttribute('id') ?? '0');
        }

        return '0';
    }
}
