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
use Filament\Forms\Components\Repeater;
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

    protected static ?int $navigationSort = 6;

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
                                    ->required()
                                    ->default(now()),

                                TextInput::make('transport_number')
                                    ->label('Номер транспорта')
                                    ->maxLength(255),

                                TextInput::make('tracking_number')
                                    ->label('Номер отслеживания')
                                    ->maxLength(255),

                                DatePicker::make('expected_arrival_date')
                                    ->label('Ожидаемая дата прибытия')
                                    ->default(now()->addDays(7)),

                                Select::make('status')
                                    ->label('Статус')
                                    ->options([
                                        ProductInTransit::STATUS_IN_TRANSIT => 'В пути',
                                        ProductInTransit::STATUS_ARRIVED => 'Прибыл',
                                    ])
                                    ->default(ProductInTransit::STATUS_IN_TRANSIT)
                                    ->required(),

                                Toggle::make('is_active')
                                    ->label('Активен')
                                    ->hidden()
                                    ->default(true),
                            ]),

                        Textarea::make('notes')
                            ->label('Заметки')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),

                Section::make('Товары')
                    ->schema([
                        Repeater::make('items')
                            ->label('Товары к добавлению')
                            ->minItems(1)
                            ->collapsed()
                            ->grid(1)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('product_template_id')
                                            ->label('Шаблон товара')
                                            ->options(ProductTemplate::pluck('name', 'id'))
                                            ->required()
                                            ->searchable()
                                            ->live(),

                                        TextInput::make('name')
                                            ->label('Наименование')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('producer')
                                            ->label('Производитель')
                                            ->maxLength(255),

                                        TextInput::make('quantity')
                                            ->label('Количество')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->required(),
                                    ]),

                                Section::make('Характеристики товара')
                                    ->schema(function (Get $get) {
                                        $templateId = $get('product_template_id');
                                        if (!$templateId) {
                                            return [];
                                        }

                                        $template = ProductTemplate::with('attributes')->find($templateId);
                                        if (!$template) {
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
                                                        ->live(debounce: 500)
                                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                            $templateId = $get('product_template_id');
                                                            if (!$templateId) {
                                                                $set('calculated_volume', null);
                                                                return;
                                                            }
                                                            $template = \App\Models\ProductTemplate::with('attributes')->find($templateId);
                                                            if (!$template || !$template->formula) {
                                                                $set('calculated_volume', null);
                                                                return;
                                                            }
                                                            $attrs = [];
                                                            foreach ($template->attributes as $a) {
                                                                $attrs[$a->variable] = $get('attribute_' . $a->variable);
                                                            }
                                                            $attrs['quantity'] = (int) ($get('quantity') ?? 0);
                                                            $r = $template->testFormula($attrs);
                                                            $set('calculated_volume', $r['success'] ? $r['result'] : null);
                                                        });
                                                    break;

                                                case 'text':
                                                    $fields[] = TextInput::make($fieldName)
                                                        ->label($attribute->full_name)
                                                        ->required($attribute->is_required)
                                                        ->live(debounce: 500)
                                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                            $templateId = $get('product_template_id');
                                                            if (!$templateId) {
                                                                $set('calculated_volume', null);
                                                                return;
                                                            }
                                                            $template = \App\Models\ProductTemplate::with('attributes')->find($templateId);
                                                            if (!$template || !$template->formula) {
                                                                $set('calculated_volume', null);
                                                                return;
                                                            }
                                                            $attrs = [];
                                                            foreach ($template->attributes as $a) {
                                                                $attrs[$a->variable] = $get('attribute_' . $a->variable);
                                                            }
                                                            $attrs['quantity'] = (int) ($get('quantity') ?? 0);
                                                            $r = $template->testFormula($attrs);
                                                            $set('calculated_volume', $r['success'] ? $r['result'] : null);
                                                        });
                                                    break;

                                                case 'select':
                                                    $options = $attribute->options_array;
                                                    $fields[] = Select::make($fieldName)
                                                        ->label($attribute->full_name)
                                                        ->options($options)
                                                        ->required($attribute->is_required)
                                                        ->live()
                                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                            $templateId = $get('product_template_id');
                                                            if (!$templateId) {
                                                                $set('calculated_volume', null);
                                                                return;
                                                            }
                                                            $template = \App\Models\ProductTemplate::with('attributes')->find($templateId);
                                                            if (!$template || !$template->formula) {
                                                                $set('calculated_volume', null);
                                                                return;
                                                            }
                                                            $attrs = [];
                                                            foreach ($template->attributes as $a) {
                                                                $attrs[$a->variable] = $get('attribute_' . $a->variable);
                                                            }
                                                            $attrs['quantity'] = (int) ($get('quantity') ?? 0);
                                                            $r = $template->testFormula($attrs);
                                                            $set('calculated_volume', $r['success'] ? $r['result'] : null);
                                                        });
                                                    break;
                                            }
                                        }

                                        return $fields;
                                    }),

                                Grid::make(1)
                                    ->schema([
                                        TextInput::make('calculated_volume')
                                            ->label('Рассчитанный объем')
                                            ->numeric()
                                            ->rule('numeric')
                                            ->disabled()
                                            ->suffix(function (Get $get) {
                                                $templateId = $get('product_template_id');
                                                if ($templateId) {  
                                                    $template = ProductTemplate::find($templateId);
                                                    return $template ? $template->unit : '';
                                                }
                                                return '';
                                            }),
                                    ]),
                            ]),
                    ]),

                Section::make('Документы')
                    ->schema([
                        FileUpload::make('document_path')
                            ->label('Документы')
                            ->directory('documents')
                            ->multiple()
                            ->maxFiles(5)
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
                        decimalPlaces: 0,
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

            ])
            ->emptyStateHeading('Нет товаров в пути')
            ->emptyStateDescription('Создайте первый товар в пути, чтобы начать работу.')
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(fn () => Warehouse::optionsForCurrentUser())
                    ->searchable(),

                SelectFilter::make('product_template_id')
                    ->label('Шаблон')
                    ->options(ProductTemplate::pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('shipping_location')
                    ->label('Место отгрузки')
                    ->options(function () {
                        $locations = ProductInTransit::getShippingLocations();
                        return array_combine($locations, $locations);
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
                Tables\Actions\EditAction::make()->label(''),
                Tables\Actions\DeleteAction::make()->label(''),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->groupRecordsTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Группировать по поставкам'),
            );
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
        return parent::getEloquentQuery();
    }
} 