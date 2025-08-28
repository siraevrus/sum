<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages;
use App\Models\Product;
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('producer')
                    ->label('Производитель')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Доступное количество')
                    ->numeric()
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
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Итого')
                    ),

                Tables\Columns\TextColumn::make('calculated_volume')
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
                            default => 'Неизвестно',
                        };
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
                        return $query->where('quantity', '>', 0);
                    }),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Низкий остаток')
                    ->query(function (Builder $query): Builder {
                        return $query->where('quantity', '<=', 10)
                                   ->where('quantity', '>', 0);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Детали товара')
                    ->modalContent(function (Product $record): string {
                        $templateName = $record->template?->name ?? 'Неизвестный шаблон';
                        $attributesText = '';
                        
                        if ($record->attributes && is_array($record->attributes)) {
                            $attributesText = implode(', ', array_map(function($key, $value) {
                                return "{$key}: {$value}";
                            }, array_keys($record->attributes), $record->attributes));
                        }
                        
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
                                    <strong>Количество:</strong> {$record->quantity}
                                </div>
                                <div>
                                    <strong>Объем:</strong> " . number_format($record->calculated_volume ?? 0, 3, '.', ' ') . " м³
                                </div>
                                <div>
                                    <strong>Характеристики:</strong> {$attributesText}
                                </div>
                                <div>
                                    <strong>Дата поступления:</strong> {$record->arrival_date?->format('d.m.Y')}
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
            ->defaultSort('quantity', 'desc');
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

        // Возвращаем базовый query для продуктов
        return $baseQuery;
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


}
