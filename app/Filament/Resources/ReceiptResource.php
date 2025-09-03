<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceiptResource\Pages;
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
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\GridEntry;
use Filament\Infolists\Components\KeyValueEntry;

class ReceiptResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationGroup = 'Приемка';

    protected static ?string $modelLabel = 'Приемка товара';

    protected static ?string $pluralModelLabel = 'Приемка товаров';

    protected static ?int $navigationSort = 7;

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // Приемка доступна только админу и работнику склада
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
                                    ->disabled(fn() => request()->route()->getName() === 'filament.admin.resources.receipts.edit')
                                    ->searchable(),

                                TextInput::make('shipping_location')
                                    ->label('Место отгрузки')
                                    ->maxLength(255)
                                    ->required()
                                    ->disabled(fn() => request()->route()->getName() === 'filament.admin.resources.receipts.edit'),

                                DatePicker::make('shipping_date')
                                    ->label('Дата отгрузки')
                                    ->required()
                                    ->default(now())
                                    ->disabled(fn() => request()->route()->getName() === 'filament.admin.resources.receipts.edit'),

                                TextInput::make('transport_number')
                                    ->label('Номер транспорта')
                                    ->maxLength(255)
                                    ->disabled(fn() => request()->route()->getName() === 'filament.admin.resources.receipts.edit'),

                                DatePicker::make('expected_arrival_date')
                                    ->label('Ожидаемая дата прибытия')
                                    ->disabled(fn() => request()->route()->getName() === 'filament.admin.resources.receipts.edit'),

                                Textarea::make('notes')
                                    ->label('Заметки')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->disabled(fn() => request()->route()->getName() === 'filament.admin.resources.receipts.edit'),

                                // Удалено поле actual_arrival_date
                            ]),
                    ]),

                Section::make('Информация о товаре')
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
                                            ->searchable()
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function (Set $set) {
                                                $set('name', '');
                                                $set('calculated_volume', null);
                                            })
                                            ->disabled(fn() => request()->route()->getName() === 'filament.admin.resources.receipts.edit'),

                                        TextInput::make('name')
                                            ->label('Наименование')
                                            ->maxLength(255)
                                            ->required()
                                            ->disabled()
                                            ->hidden(fn() => true),

                                        TextInput::make('quantity')
                                            ->label('Количество')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                static::calculateVolumeForItem($set, $get);
                                            }),

                                        TextInput::make('calculated_volume')
                                            ->label('Объем')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated(false),

                                        Select::make('producer_id')
                                            ->label('Производитель')
                                            ->options(\App\Models\Producer::pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Выберите производителя')
                                            ->required()
                                            ->disabled(fn() => request()->route()->getName() === 'filament.admin.resources.receipts.edit'),

                                        // Удалено поле tracking_number
                                    ]),

                                // Динамические поля для характеристик
                                Grid::make(2)
                                    ->schema(function (Get $get) {
                                        $templateId = $get('product_template_id');
                                        if (! $templateId) {
                                            return [];
                                        }

                                        $template = ProductTemplate::find($templateId);
                                        if (! $template || ! $template->attributes) {
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
                                                        ->required($attribute->is_required);
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

                                // Удалено поле description
                            ])
                            ->addActionLabel('Добавить товар')
                            ->deletable(false)
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Детальная информация о товаре')
                    ->schema([
                        TextEntry::make('name')->label('Наименование'),
                        TextEntry::make('producer.name')->label('Производитель'),
                        TextEntry::make('quantity')->label('Количество'),
                        TextEntry::make('calculated_volume')->label('Объем'),
                        TextEntry::make('transport_number')->label('Номер транспорта'),
                        TextEntry::make('shipping_location')->label('Место отгрузки'),
                        TextEntry::make('shipping_date')->label('Дата отгрузки'),
                        TextEntry::make('expected_arrival_date')->label('Ожидаемая дата прибытия'),
                        TextEntry::make('arrival_date')->label('Дата поступления'),
                        TextEntry::make('document_path')->label('Документы')->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : $state),
                        KeyValueEntry::make('attributes')->label('Характеристики')->visible(fn($state) => is_array($state) && count($state) > 0),
                    ])
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

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->sortable(),

                // Скрыты: 'Место отгрузки', 'Дата отгрузки', 'Производитель', 'Ожидаемая дата'
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Количество')
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('calculated_volume')
                    ->label('Объем')
                    ->formatStateUsing(function ($state) {
                        return $state ? number_format($state, 3, '.', ' ') : '0.000';
                    })
                    ->suffix(function (Product $record): string {
                        return $record->template?->unit ?? '';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => Product::STATUS_FOR_RECEIPT,
                        'success' => Product::STATUS_IN_STOCK,
                    ])
                    ->formatStateUsing(function (Product $record): string {
                        return $record->isForReceipt() ? 'Для приемки' : 'На складе';
                    })
                    ->sortable(),

                // Ожидаемая дата скрыта
                Tables\Columns\TextColumn::make('actual_arrival_date')
                    ->label('Фактическая дата')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Создатель')
                    ->sortable(),
            ])
            ->emptyStateHeading('Нет товаров для приемки')
            ->emptyStateDescription('Все товары уже приняты или нет товаров со статусом "Для приемки".')
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->options(fn () => Warehouse::optionsForCurrentUser())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('shipping_location')
                    ->label('Место отгрузки')
                    ->options(function () {
                        $locations = Product::query()
                            ->whereNotNull('shipping_location')
                            ->distinct()
                            ->pluck('shipping_location')
                            ->filter()
                            ->sort()
                            ->values()
                            ->toArray();
                        return array_combine($locations, $locations);
                    })
                    ->searchable(),
                // Убраны фильтры 'ready_for_receipt' и 'status'
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make()->label(''),
                Tables\Actions\Action::make('confirm_receipt')
                    ->label('Подтвердить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Product $record): bool => $record->status === Product::STATUS_FOR_RECEIPT)
                    ->requiresConfirmation()
                    ->modalHeading('Подтверждение приемки товара')
                    ->modalDescription('Вы уверены, что хотите подтвердить приемку этого товара? Товар будет переведен в статус "На складе".')
                    ->action(function (Product $record): void {
                        $record->update([
                            'status' => Product::STATUS_IN_STOCK,
                            'actual_arrival_date' => now(),
                        ]);
                    })
                    ->after(function () {
                        \Filament\Notifications\Notification::make()
                            ->title('Товар успешно принят')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('confirm_multiple_receipts')
                        ->label('Подтвердить приемку выбранных')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Подтверждение приемки товаров')
                        ->modalDescription('Вы уверены, что хотите подтвердить приемку выбранных товаров?')
                        ->action(function (\Illuminate\Support\Collection $records): void {
                            foreach ($records as $record) {
                                if ($record->status === Product::STATUS_FOR_RECEIPT) {
                                    $record->update([
                                        'status' => Product::STATUS_IN_STOCK,
                                        'actual_arrival_date' => now(),
                                    ]);
                                }
                            }
                        })
                        ->after(function () {
                            \Filament\Notifications\Notification::make()
                                ->title('Товары успешно приняты')
                                ->success()
                                ->send();
                        }),
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
            'view' => Pages\ViewReceipt::route('/{record}'),
            'edit' => Pages\EditReceipt::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $base = parent::getEloquentQuery()
            ->where('status', Product::STATUS_FOR_RECEIPT)
            ->active();

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
                $templateName = $template->name ?? 'Товар';
                $generatedName = $templateName.': '.implode(', ', $nameParts);
                $set('name', $generatedName);
            }

            // Рассчитываем объем по формуле
            try {
                $formula = $template->formula;
                $expression = $formula;

                // Получаем переменные из формулы
                preg_match_all('/[a-zA-Z_][a-zA-Z0-9_]*/', $formula, $matches);
                $variables = $matches[0] ?? [];

                foreach ($variables as $var) {
                    if (! isset($attributes[$var]) || ! is_numeric($attributes[$var])) {
                        $set('calculated_volume', null);

                        return;
                    }
                    $expression = str_replace($var, $attributes[$var], $expression);
                }

                $result = eval("return $expression;");
                $set('calculated_volume', $result);
            } catch (\Throwable $e) {
                $set('calculated_volume', null);
            }
        }
    }
}
