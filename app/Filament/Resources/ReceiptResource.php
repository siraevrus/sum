<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceiptResource\Pages;
use App\Models\ProductInTransit;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
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
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Наименование товара')
                                    ->disabled()
                                    ->columnSpan(2),

                                TextInput::make('template.name')
                                    ->label('Шаблон товара')
                                    ->disabled(),

                                TextInput::make('producer')
                                    ->label('Производитель')
                                    ->maxLength(255),

                                TextInput::make('quantity')
                                    ->label('Количество')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required(),

                                TextInput::make('calculated_volume')
                                    ->label('Объем')
                                    ->disabled()
                                    ->suffix(function ($record) {
                                        return $record?->template?->unit ?? '';
                                    }),

                                Textarea::make('description')
                                    ->label('Описание товара')
                                    ->rows(2)
                                    ->maxLength(1000)
                                    ->columnSpan(2),
                            ]),
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
}
