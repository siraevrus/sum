<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseResource\Pages;
use App\Filament\Resources\WarehouseResource\RelationManagers;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationLabel = 'Склады';

    protected static ?string $modelLabel = 'Склад';

    protected static ?string $pluralModelLabel = 'Склады';

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        return $user->role->value === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о складе')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Компания')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->label('Название склада')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('address')
                            ->label('Адрес склада')
                            ->required()
                            ->rows(3),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Активный')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Компания')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Название склада')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Адрес')
                    ->limit(50),

                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Сотрудники')
                    ->counts('employees')
                    ->formatStateUsing(fn (int $state): string => "{$state} сотрудников"),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Статус')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->emptyStateHeading('Нет складов')
            ->emptyStateDescription('Создайте первый склад, чтобы начать работу.')
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Компания')
                    ->relationship('company', 'name'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label(''),
                Tables\Actions\DeleteAction::make()->label(''),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'edit' => Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
}
