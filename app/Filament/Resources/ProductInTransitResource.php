<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductInTransitResource\Pages;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ProductInTransitResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Товары';

    protected static ?string $modelLabel = 'Товар в пути';

    protected static ?string $pluralModelLabel = 'Товары в пути';

    protected static ?int $navigationSort = 6;

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return in_array($user->role->value, [
            'admin',
            'operator',
            'warehouse_worker',
            'sales_manager',
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
                                TextInput::make('shipping_location')
                                    ->label('Место отгрузки')
                                    ->maxLength(255)
                                    ->required(),

                                Select::make('warehouse_id')
                                    ->label('Склад назначения')
                                    ->options(fn () => Warehouse::optionsForCurrentUser())
                                    ->required()
                                    ->dehydrated()
                                    ->default(function () {
                                        $user = Auth::user();
                                        if (! $user) {
                                            return null;
                                        }
                                        return $user->isAdmin() ? null : $user->warehouse_id;
                                    })
                                    ->visible(function () {
                                        $user = Auth::user();
                                        if (! $user) {
                                            return false;
                                        }
                                        return $user->isAdmin();
                                    })
                                    ->searchable(),

                                DatePicker::make('shipping_date')
                                    ->label('Дата отгрузки')
                                    ->required()
                                    ->default(now()),

                                DatePicker::make('expected_arrival_date')
                                    ->label('Ожидаемая дата прибытия')
                                    ->default(null),

                                Select::make('status')
                                    ->label('Статус')
                                    ->options([
                                        Product::STATUS_IN_TRANSIT => 'В пути',
                                        Product::STATUS_FOR_RECEIPT => 'Для приемки',
                                        Product::STATUS_IN_STOCK => 'На складе',
                                    ])
                                    ->required()
                                    ->default(Product::STATUS_IN_TRANSIT),

                                TextInput::make('transport_number')
                                    ->label('Номер транспорта')
                                    ->maxLength(255),
                            ]),

                        Textarea::make('notes')
                            ->label('Заметки')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),

                Section::make('Товары')
                    ->schema([
                        Repeater::make('products')
                            ->label('Список товаров')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('product_template_id')
                                            ->label('Шаблон товара')
                                            ->options(function () {
                                                return ProductTemplate::pluck('name', 'id');
                                            })
                                            ->required()
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                // Очищаем характеристики при смене шаблона
                                                $set('attributes', []);
                                                $set('calculated_volume', null);
                                                $set('name', '');
                                            }),

                                        TextInput::make('name')
                                            ->label('Наименование')
                                            ->maxLength(255)
                                            ->disabled()
                                            ->hidden(fn() => true)
                                            ->helperText('Автоматически формируется из характеристик товара'),

                                        Select::make('producer_id')
                                            ->label('Производитель')
                                            ->options(function () {
                                                return \App\Models\Producer::pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->required(),

                                        TextInput::make('quantity')
                                            ->label('Количество')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->maxValue(99999)
                                            ->maxLength(5)
                                            ->required()
                                            ->helperText('Максимальное значение: 99999. Объем рассчитывается при сохранении товара.'),

                                        TextInput::make('calculated_volume')
                                            ->label('Рассчитанный объем')
                                            ->disabled()
                                            ->formatStateUsing(function ($state) {
                                                return $state ? number_format($state, 3, '.', ' ') : '0.000';
                                            })
                                            ->suffix(function (Get $get) {
                                                $templateId = $get('product_template_id');
                                                if ($templateId) {
                                                    $template = ProductTemplate::find($templateId);

                                                    return $template ? $template->unit : '';
                                                }

                                                return '';
                                            })
                                            ->helperText('Объем рассчитывается автоматически на основе числовых характеристик товара по формуле шаблона'),
                                    ]),

                                // Динамические поля характеристик
                                Grid::make(3)
                                    ->schema(function (Get $get) {
                                        $templateId = $get('product_template_id');
                                        if (! $templateId) {
                                            return [];
                                        }

                                        $template = ProductTemplate::with('attributes')->find($templateId);
                                        if (! $template) {
                                            return [];
                                        }

                                        $fields = [];
                                        foreach ($template->attributes as $attribute) {
                                            $fieldName = "attribute_{$attribute->variable}";

                                            switch ($attribute->type) {
                                                case 'number':
                                                    $fields[] = TextInput::make($fieldName)
                                                        ->label($attribute->name)
                                                        ->numeric()
                                                        ->maxValue(9999)
                                                        ->maxLength(4)
                                                        ->required($attribute->is_required)
                                                        ->helperText('Максимальное значение: 9999');
                                                    break;

                                                case 'text':
                                                    $fields[] = TextInput::make($fieldName)
                                                        ->label($attribute->name)
                                                        ->required($attribute->is_required);
                                                    break;

                                                case 'select':
                                                    $options = $attribute->options_array;
                                                    $fields[] = Select::make($fieldName)
                                                        ->label($attribute->name)
                                                        ->options($options)
                                                        ->required($attribute->is_required);
                                                    break;
                                            }
                                        }

                                        return $fields;
                                    })
                                    ->visible(fn (Get $get) => $get('product_template_id') !== null),
                            ])
                            ->addActionLabel('Добавить товар')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Товар')
                            ->defaultItems(1)
                            ->minItems(1),
                    ]),

                Section::make('Документы')
                    ->schema([
                        FileUpload::make('document_path')
                            ->label('Документы')
                            ->directory('documents')
                            ->multiple()
                            ->maxFiles(5)
                            ->maxSize(51200) // 50MB
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'])
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->imagePreviewHeight('250'),
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

                Tables\Columns\TextColumn::make('calculated_volume')
                    ->label('Объем')
                    ->formatStateUsing(function ($state) {
                        return $state ? number_format($state, 3) . ' м³' : '-';
                    })
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

                Tables\Columns\TextColumn::make('producer.name')
                    ->label('Производитель')
                    ->formatStateUsing(function (
                        $state
                    ) {
                        return $state ?: 'Не указан';
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'info' => Product::STATUS_IN_TRANSIT,
                        'warning' => Product::STATUS_FOR_RECEIPT,
                        'success' => Product::STATUS_IN_STOCK,
                    ])
                    ->formatStateUsing(function (Product $record): string {
                        if ($record->isInTransit()) {
                            return 'В пути';
                        }
                        if ($record->isForReceipt()) {
                            return 'Для приемки';
                        }

                        return 'На складе';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('expected_arrival_date')
                    ->label('Ожидаемая дата')
                    ->date()
                    ->sortable()
                    ->color(function (Product $record): string {
                        $expected = $record->expected_arrival_date;
                        if (! $expected) {
                            return 'success';
                        }

                        return (($record->status === Product::STATUS_IN_TRANSIT || $record->status === Product::STATUS_FOR_RECEIPT) && $expected < now()) ? 'danger' : 'success';
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
            ->actions([
                Tables\Actions\EditAction::make()->label(''),
                Tables\Actions\DeleteAction::make()->label(''),
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

    /**
     * Рассчитать объем для элемента товара
     */
    private static function calculateVolumeForItem(Set $set, Get $get): void
    {
        $templateId = $get('product_template_id');
        if (! $templateId) {
            return;
        }

        $template = ProductTemplate::find($templateId);
        if (! $template || ! $template->formula) {
            return;
        }

        // Собираем все значения характеристик
        $attributes = [];
        $formData = $get();

        foreach ($formData as $key => $value) {
            if (str_starts_with($key, 'attribute_') && $value !== null) {
                $attributeName = str_replace('attribute_', '', $key);
                $attributes[$attributeName] = $value;
            }
        }

        // Добавляем количество
        $quantity = $get('quantity') ?? 1;
        $attributes['quantity'] = $quantity;

        if (! empty($attributes)) {
            // Формируем наименование из характеристик
            $nameParts = [];
            foreach ($template->attributes as $templateAttribute) {
                $attributeKey = $templateAttribute->variable;
                if (isset($attributes[$attributeKey]) && $attributes[$attributeKey] !== null) {
                    $nameParts[] = $attributes[$attributeKey];
                }
            }

            if (! empty($nameParts)) {
                // Добавляем название шаблона в начало
                $templateName = $template->name ?? 'Товар';
                $generatedName = $templateName.': '.implode(', ', $nameParts);
                $set('name', $generatedName);
            }

            // Рассчитываем объем
            $testResult = $template->testFormula($attributes);
            if ($testResult['success']) {
                $set('calculated_volume', $testResult['result']);
            }
        }
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $base = parent::getEloquentQuery()->where('status', Product::STATUS_IN_TRANSIT);

        if (! $user) {
            return $base->whereRaw('1 = 0');
        }

        if ($user->role->value === 'admin') {
            return $base;
        }

        if ($user->warehouse_id) {
            return $base->where('warehouse_id', $user->warehouse_id);
        }

        return $base->whereRaw('1 = 0');
    }
}
