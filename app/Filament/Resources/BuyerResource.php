<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BuyerResource\Pages;
use App\Models\Buyer;
use App\Models\Sale;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BuyerResource extends Resource
{
    protected static ?string $model = Buyer::class;

    protected static ?string $navigationLabel = 'Покупатели';

    protected static ?string $modelLabel = 'Покупатель';

    protected static ?string $pluralModelLabel = 'Покупатели';

    protected static ?string $navigationGroup = 'Реализация';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return Sale::query()
            ->select('id', 'customer_name', 'customer_phone', 'customer_email', 'customer_address', 'sale_date')
            ->orderBy('customer_name');
    }

    public static function getTableRecordKey($record): string
    {
        return (string) $record->id;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer_name')->label('Имя клиента')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer_phone')->label('Телефон клиента')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer_email')->label('Email')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer_address')->label('Адрес')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('sale_date')->label('Дата')->date('d.m.Y')->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()->label(''),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
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
            'index' => Pages\ListBuyers::route('/'),
            // 'create' => Pages\CreateBuyer::route('/create'), // убираем создание
            // 'edit' => Pages\EditBuyer::route('/{record}/edit'), // убираем редактирование
            'purchases' => Pages\PurchasesByBuyer::route('/purchases'),
        ];
    }
}
