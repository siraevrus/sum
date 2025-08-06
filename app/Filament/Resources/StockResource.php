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

    protected static ?int $navigationSort = 6;

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        return in_array($user->role->value, [
            'admin',
            'warehouse_worker',
            'sales_manager'
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('warehouse_id')
                    ->label('Склад')
                    ->options(Warehouse::pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('product_template_id')
                    ->label('Шаблон товара')
                    ->relationship('productTemplate', 'name')
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
                Forms\Components\DatePicker::make('arrival_date')
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
                    ->label('Количество')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('calculated_volume')
                    ->label('Объем (м³)')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ' ',
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('productTemplate.name')
                    ->label('Шаблон')
                    ->sortable(),
                Tables\Columns\TextColumn::make('arrival_date')
                    ->label('Дата поступления')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Статус')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(Warehouse::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('producer')
                    ->label('Производитель')
                    ->options(fn () => Product::distinct()->pluck('producer', 'producer')->filter()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Статус')
                    ->placeholder('Все')
                    ->trueLabel('Активные')
                    ->falseLabel('Неактивные'),
                Tables\Filters\Filter::make('in_stock')
                    ->label('В наличии')
                    ->query(fn (Builder $query): Builder => $query->where('quantity', '>', 0)),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Мало остатков')
                    ->query(fn (Builder $query): Builder => $query->where('quantity', '<=', 10)->where('quantity', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label(''),
                Tables\Actions\EditAction::make()->label(''),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = Product::query();

        // Фильтрация по компании пользователя
        if ($user->company_id) {
            $query->whereHas('warehouse', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        }

        return $query;
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
            'create' => Pages\CreateStock::route('/create'),
            'view' => Pages\ViewStock::route('/{record}'),
            'edit' => Pages\EditStock::route('/{record}/edit'),
        ];
    }
} 