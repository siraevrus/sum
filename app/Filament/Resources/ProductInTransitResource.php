<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductInTransitResource\Pages;
use App\Models\ProductInTransit;
use App\Models\ProductTemplate;
use App\Models\Warehouse;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ProductInTransitResource extends Resource
{
    protected static ?string $model = ProductInTransit::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Товары';

    protected static ?string $modelLabel = 'Товар в пути';

    protected static ?string $pluralModelLabel = 'Товары в пути';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        return in_array($user->role->value, [
            'admin',
            'operator',
            'warehouse_worker',
            'sales_manager'
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
                                Select::make('product_template_id')
                                    ->label('Шаблон товара')
                                    ->options(ProductTemplate::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('attributes', []);
                                    }),

                                Select::make('warehouse_id')
                                    ->label('Склад назначения')
                                    ->options(Warehouse::pluck('name', 'id'))
                                    ->required()
                                    ->searchable(),

                                TextInput::make('name')
                                    ->label('Наименование')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('producer')
                                    ->label('Производитель')
                                    ->maxLength(255),

                                TextInput::make('supplier')
                                    ->label('Поставщик')
                                    ->maxLength(255)
                                    ->required(),

                                TextInput::make('quantity')
                                    ->label('Количество')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),

                                TextInput::make('transport_number')
                                    ->label('Номер транспорта')
                                    ->maxLength(255),

                                TextInput::make('tracking_number')
                                    ->label('Номер отслеживания')
                                    ->maxLength(255),

                                DatePicker::make('expected_arrival_date')
                                    ->label('Ожидаемая дата прибытия')
                                    ->required()
                                    ->default(now()->addDays(7)),

                                Select::make('status')
                                    ->label('Статус')
                                    ->options([
                                        ProductInTransit::STATUS_ORDERED => 'Заказан',
                                        ProductInTransit::STATUS_IN_TRANSIT => 'В пути',
                                        ProductInTransit::STATUS_ARRIVED => 'Прибыл',
                                        ProductInTransit::STATUS_RECEIVED => 'Принят',
                                        ProductInTransit::STATUS_CANCELLED => 'Отменен',
                                    ])
                                    ->default(ProductInTransit::STATUS_ORDERED)
                                    ->required(),

                                Toggle::make('is_active')
                                    ->label('Активен')
                                    ->default(true),
                            ]),

                        Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->maxLength(1000),

                        Textarea::make('notes')
                            ->label('Заметки')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),

                Section::make('Характеристики товара')
                    ->schema([
                        KeyValue::make('attributes')
                            ->label('Характеристики')
                            ->keyLabel('Название')
                            ->valueLabel('Значение')
                            ->columnSpanFull()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                // Автоматически рассчитываем объем при изменении характеристик
                                $templateId = $get('product_template_id');
                                if ($templateId && $state) {
                                    $template = ProductTemplate::find($templateId);
                                    if ($template && $template->formula) {
                                        $testResult = $template->testFormula($state);
                                        if ($testResult['success']) {
                                            $set('calculated_volume', $testResult['result']);
                                        }
                                    }
                                }
                            }),
                    ])
                    ->visible(function (Get $get) {
                        return $get('product_template_id') !== null;
                    }),

                Section::make('Расчет объема')
                    ->schema([
                        TextInput::make('calculated_volume')
                            ->label('Рассчитанный объем')
                            ->numeric()
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
                    ->visible(function (Get $get) {
                        return $get('product_template_id') !== null;
                    }),

                Section::make('Документы')
                    ->schema([
                        FileUpload::make('document_path')
                            ->label('Документы')
                            ->directory('documents')
                            ->multiple()
                            ->maxFiles(5)
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(10240), // 10MB
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

                Tables\Columns\TextColumn::make('supplier')
                    ->label('Поставщик')
                    ->searchable()
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
                        decimalPlaces: 4,
                        decimalSeparator: '.',
                        thousandsSeparator: ' ',
                    )
                    ->suffix(function (ProductInTransit $record): string {
                        return $record->template?->unit ?? '';
                    })
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => ProductInTransit::STATUS_ORDERED,
                        'info' => ProductInTransit::STATUS_IN_TRANSIT,
                        'success' => ProductInTransit::STATUS_ARRIVED,
                        'success' => ProductInTransit::STATUS_RECEIVED,
                        'danger' => ProductInTransit::STATUS_CANCELLED,
                    ])
                    ->formatStateUsing(function (ProductInTransit $record): string {
                        return $record->getStatusLabel();
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
                    ->label('Фактическая дата')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Создатель')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->emptyStateHeading('Нет товаров в пути')
            ->emptyStateDescription('Создайте первый товар в пути, чтобы начать работу.')
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(Warehouse::pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('product_template_id')
                    ->label('Шаблон')
                    ->options(ProductTemplate::pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('supplier')
                    ->label('Поставщик')
                    ->options(function () {
                        $suppliers = ProductInTransit::getSuppliers();
                        return array_combine($suppliers, $suppliers);
                    })
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        ProductInTransit::STATUS_ORDERED => 'Заказан',
                        ProductInTransit::STATUS_IN_TRANSIT => 'В пути',
                        ProductInTransit::STATUS_ARRIVED => 'Прибыл',
                        ProductInTransit::STATUS_RECEIVED => 'Принят',
                        ProductInTransit::STATUS_CANCELLED => 'Отменен',
                    ]),

                Filter::make('overdue')
                    ->label('Просроченные')
                    ->query(function (Builder $query): Builder {
                        return $query->where('expected_arrival_date', '<', now())
                                   ->whereNotIn('status', [ProductInTransit::STATUS_RECEIVED, ProductInTransit::STATUS_CANCELLED]);
                    }),

                Filter::make('active')
                    ->label('Только активные')
                    ->query(function (Builder $query): Builder {
                        return $query->where('is_active', true);
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('receive')
                    ->label('Принять')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(function (ProductInTransit $record): bool {
                        return $record->canBeReceived();
                    })
                    ->action(function (ProductInTransit $record): void {
                        $record->receive();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Принять товар')
                    ->modalDescription('Товар будет перемещен в остатки на складе.')
                    ->modalSubmitActionLabel('Принять'),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListProductInTransit::route('/'),
            'create' => Pages\CreateProductInTransit::route('/create'),
            'view' => Pages\ViewProductInTransit::route('/{record}'),
            'edit' => Pages\EditProductInTransit::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        // Администратор видит все товары в пути
        if ($user->role === 'admin') {
            return parent::getEloquentQuery();
        }
        
        // Остальные пользователи видят только товары в пути на своих складах
        return parent::getEloquentQuery()
            ->whereHas('warehouse', function (Builder $query) use ($user) {
                $query->where('company_id', $user->company_id);
            });
    }
} 