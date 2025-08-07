<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
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
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Товары';

    protected static ?string $modelLabel = 'Товар';

    protected static ?string $pluralModelLabel = 'Товары';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        return in_array($user->role->value, [
            'admin',
            'operator'
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
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        // Очищаем старые характеристики при смене шаблона
                                        $set('calculated_volume', null);
                                        
                                        // Добавляем динамические поля характеристик
                                        $templateId = $get('product_template_id');
                                        if ($templateId) {
                                            $template = ProductTemplate::with('attributes')->find($templateId);
                                            if ($template) {
                                                // Очищаем старые поля характеристик
                                                foreach ($template->attributes as $attribute) {
                                                    $set("attribute_{$attribute->variable}", null);
                                                }
                                            }
                                        }
                                    }),

                                Select::make('warehouse_id')
                                    ->label('Склад')
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

                                TextInput::make('quantity')
                                    ->label('Количество')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),

                                TextInput::make('transport_number')
                                    ->label('Номер транспортного средства')
                                    ->maxLength(255),

                                DatePicker::make('arrival_date')
                                    ->label('Дата поступления')
                                    ->required()
                                    ->default(now()),

                                Toggle::make('is_active')
                                    ->label('Активен')
                                    ->hidden()
                                    ->default(true),
                            ]),

                        Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),

                Section::make('Характеристики товара')
                    ->schema([
                        // Динамические поля характеристик будут добавляться здесь
                    ])
                    ->visible(fn (Get $get) => $get('product_template_id') !== null)
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                        // Автоматически рассчитываем объем при изменении характеристик
                        $templateId = $get('product_template_id');
                        if ($templateId) {
                            $template = ProductTemplate::find($templateId);
                            if ($template && $template->formula) {
                                // Собираем все значения характеристик
                                $attributes = [];
                                foreach ($state as $key => $value) {
                                    if (str_starts_with($key, 'attribute_')) {
                                        $attributeName = str_replace('attribute_', '', $key);
                                        $attributes[$attributeName] = $value;
                                    }
                                }
                                
                                if (!empty($attributes)) {
                                    $testResult = $template->testFormula($attributes);
                                    if ($testResult['success']) {
                                        $set('calculated_volume', $testResult['result']);
                                    }
                                }
                            }
                        }
                    })
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
                                        ->live();
                                    break;
                                    
                                case 'text':
                                    $fields[] = TextInput::make($fieldName)
                                        ->label($attribute->full_name)
                                        ->required($attribute->is_required)
                                        ->live();
                                    break;
                                    
                                case 'select':
                                    $options = $attribute->options_array;
                                    $fields[] = Select::make($fieldName)
                                        ->label($attribute->full_name)
                                        ->options($options)
                                        ->required($attribute->is_required)
                                        ->live();
                                    break;
                            }
                        }

                        return $fields;
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

                Tables\Columns\TextColumn::make('producer')
                    ->label('Производитель')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Количество')
                    ->sortable()
                    ->badge()
                    ->color(function (string $state): string {
                        if ($state > 10) return 'success';
                        if ($state > 0) return 'warning';
                        return 'danger';
                    }),

                Tables\Columns\TextColumn::make('calculated_volume')
                    ->label('Объем')
                    ->numeric(
                        decimalPlaces: 4,
                        decimalSeparator: '.',
                        thousandsSeparator: ' ',
                    )
                    ->suffix(function (Product $record): string {
                        return $record->template?->unit ?? '';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('arrival_date')
                    ->label('Дата поступления')
                    ->date()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Статус')
                    ->boolean()
                    ->hidden()
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Создатель')
                    ->sortable(),

            ])
            ->emptyStateHeading('Нет товаров')
            ->emptyStateDescription('Создайте первый товар, чтобы начать работу.')
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(Warehouse::pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('product_template_id')
                    ->label('Шаблон')
                    ->options(ProductTemplate::pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('producer')
                    ->label('Производитель')
                    ->options(function () {
                        $producers = Product::getProducers();
                        return array_combine($producers, $producers);
                    })
                    ->searchable(),

                Filter::make('in_stock')
                    ->label('Только с остатками')
                    ->query(function (Builder $query): Builder {
                        return $query->where('quantity', '>', 0);
                    }),

                Filter::make('low_stock')
                    ->label('Заканчивается (≤10)')
                    ->query(function (Builder $query): Builder {
                        return $query->where('quantity', '<=', 10)->where('quantity', '>', 0);
                    }),

                Filter::make('out_of_stock')
                    ->label('Нет в наличии')
                    ->query(function (Builder $query): Builder {
                        return $query->where('quantity', '<=', 0);
                    }),

                Filter::make('active')
                    ->label('Только активные')
                    ->query(function (Builder $query): Builder {
                        return $query->where('is_active', true);
                    }),

                Filter::make('recent_arrivals')
                    ->label('Недавно поступившие (30 дней)')
                    ->query(function (Builder $query): Builder {
                        return $query->where('arrival_date', '>=', now()->subDays(30));
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
            ->headerActions([
                // Tables\Actions\Action::make('export')
                //     ->label('Экспорт')
                //     ->icon('heroicon-o-arrow-down-tray')
                //     ->url(route('products.export'))
                //     ->openUrlInNewTab(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        // Администратор видит все товары
        if ($user->role->value === 'admin') {
            return parent::getEloquentQuery();
        }
        
        // Остальные пользователи видят только товары на своих складах
        return parent::getEloquentQuery()
            ->whereHas('warehouse', function (Builder $query) use ($user) {
                $query->where('company_id', $user->company_id);
            });
    }
} 