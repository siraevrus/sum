<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscrepancyResource\Pages;
use App\Filament\Resources\DiscrepancyResource\RelationManagers;
use App\Models\Discrepancy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class DiscrepancyResource extends Resource
{
    protected static ?string $model = Discrepancy::class;

    protected static ?string $navigationIcon = null;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Дата')->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('productInTransit.name')->label('Товар'),
                Tables\Columns\TextColumn::make('user.name')->label('Пользователь'),
                Tables\Columns\TextColumn::make('reason')->label('Причина'),
                Tables\Columns\TextColumn::make('old_quantity')->label('Было (кол-во)'),
                Tables\Columns\TextColumn::make('new_quantity')->label('Стало (кол-во)'),
                Tables\Columns\TextColumn::make('old_color')->label('Было (цвет)'),
                Tables\Columns\TextColumn::make('new_color')->label('Стало (цвет)'),
                Tables\Columns\TextColumn::make('old_size')->label('Было (размер)'),
                Tables\Columns\TextColumn::make('new_size')->label('Стало (размер)'),
                Tables\Columns\TextColumn::make('old_weight')->label('Было (вес)'),
                Tables\Columns\TextColumn::make('new_weight')->label('Стало (вес)'),
            ])
            ->filters([
                // Можно добавить фильтры по пользователю, товару, дате
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Общее')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')->label('Дата')->dateTime('d.m.Y H:i'),
                        Infolists\Components\TextEntry::make('productInTransit.name')->label('Товар'),
                        Infolists\Components\TextEntry::make('user.name')->label('Пользователь'),
                        Infolists\Components\TextEntry::make('reason')->label('Причина'),
                    ]),
                Infolists\Components\Section::make('Было / Стало')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('old_quantity')->label('Было (кол-во)'),
                                Infolists\Components\TextEntry::make('new_quantity')->label('Стало (кол-во)'),
                                Infolists\Components\TextEntry::make('old_color')->label('Было (цвет)'),
                                Infolists\Components\TextEntry::make('new_color')->label('Стало (цвет)'),
                                Infolists\Components\TextEntry::make('old_size')->label('Было (размер)'),
                                Infolists\Components\TextEntry::make('new_size')->label('Стало (размер)'),
                                Infolists\Components\TextEntry::make('old_weight')->label('Было (вес)'),
                                Infolists\Components\TextEntry::make('new_weight')->label('Стало (вес)'),
                            ]),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDiscrepancies::route('/'),
        ];
    }
}
