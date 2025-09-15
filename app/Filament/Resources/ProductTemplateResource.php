<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductTemplateResource\Pages;
use App\Models\ProductTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProductTemplateResource extends Resource
{
    protected static ?string $model = ProductTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Шаблоны товаров';

    protected static ?string $modelLabel = 'Шаблон товара';

    protected static ?string $pluralModelLabel = 'Шаблоны товаров';

    protected static ?int $navigationSort = 4;
    

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->role->value === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название шаблона')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('unit')
                            ->label('Единица измерения')
                            ->options([
                                'шт' => 'шт',
                                'мм' => 'мм',
                                'см' => 'см',
                                'метр' => 'метр',
                                'радиус' => 'радиус',
                                'м³' => 'м³',
                                'м²' => 'м²',
                                'кг' => 'кг',
                                'грамм' => 'грамм',
                            ])
                            ->default('м³'),

                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Активный')
                            ->default(true),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Характеристики')
                    ->schema([
                        Forms\Components\Repeater::make('attributes')
                            ->label('Характеристики')
                            ->schema([
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Название характеристики')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('variable')
                                            ->label('Переменная')
                                            ->required()
                                            ->maxLength(50)
                                            ->helperText('Только английские буквы, цифры и подчеркивание')
                                            ->rules(['regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/'])
                                            ->distinct()
                                            ->validationMessages([
                                                'distinct' => 'дайте уникальные имена переменным',
                                            ])
                                            ->afterStateHydrated(function ($component, $state, $set, $get) {
                                                $variables = collect($get('../../attributes'))->pluck('variable');
                                                $duplicates = $variables->duplicates();
                                                if ($duplicates->contains($state)) {
                                                    $component->extraAttributes(['style' => 'border-color: #dc2626;']); // красная рамка
                                                }
                                            }),

                                        Forms\Components\Select::make('type')
                                            ->label('Тип данных')
                                            ->options([
                                                'number' => 'Число',
                                                'text' => 'Текст',
                                                'select' => 'Выпадающий список',
                                            ])
                                            ->default('number')
                                            ->reactive(),

                                        Forms\Components\Select::make('unit')
                                            ->label('Единица измерения')
                                            ->options([
                                                'шт' => 'шт',
                                                'мм' => 'мм',
                                                'см' => 'см',
                                                'метр' => 'метр',
                                                'радиус' => 'радиус',
                                                'м³' => 'м³',
                                                'м²' => 'м²',
                                                'кг' => 'кг',
                                                'грамм' => 'грамм',
                                            ])
                                            ->visible(fn ($get) => $get('type') === 'number'),
                                    ]),

                                Forms\Components\Textarea::make('options')
                                    ->label('Варианты (через запятую)')
                                    ->visible(fn ($get) => $get('type') === 'select')
                                    ->helperText('Введите варианты через запятую')
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Checkbox::make('is_required')
                                            ->label('Обязательно к заполнению'),

                                        Forms\Components\Checkbox::make('is_in_formula')
                                            ->label('Учитывать в формуле')
                                            ->visible(fn ($get) => $get('type') === 'number'),
                                    ]),
                            ])
                            ->defaultItems(0)
                            ->default([])
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->required()
                            ->reorderable(false)
                            ->addActionLabel('Добавить характеристику')
                            ->saveRelationshipsUsing(function ($operation, $state, ProductTemplate $record) {
                                // Сохраняем атрибуты вручную, чтобы поддержать create/edit
                                $record->attributes()->delete();

                                // Сохраняем атрибуты в правильном порядке
                                foreach ($state as $index => $row) {
                                    $options = $row['options'] ?? null;
                                    if (is_string($options)) {
                                        $options = array_values(array_filter(array_map('trim', explode(',', $options))));
                                    }

                                    $record->attributes()->create([
                                        'name' => $row['name'] ?? '',
                                        'variable' => $row['variable'] ?? '',
                                        'type' => $row['type'] ?? 'number',
                                        'options' => $options,
                                        'unit' => $row['unit'] ?? null,
                                        'is_required' => (bool) ($row['is_required'] ?? false),
                                        'is_in_formula' => (bool) ($row['is_in_formula'] ?? false),
                                        'sort_order' => $index, // Порядок в форме = sort_order
                                    ]);
                                }
                            })
                            ->loadStateFromRelationshipsUsing(function (ProductTemplate $record): array {
                                // Загружаем атрибуты в правильном порядке (по sort_order)
                                return $record->attributes()
                                    ->orderBy('sort_order')
                                    ->orderBy('id')
                                    ->get()
                                    ->map(function ($attribute) {
                                        return [
                                            'name' => $attribute->name,
                                            'variable' => $attribute->variable,
                                            'type' => $attribute->type,
                                            'options' => $attribute->options,
                                            'unit' => $attribute->unit,
                                            'is_required' => $attribute->is_required,
                                            'is_in_formula' => $attribute->is_in_formula,
                                        ];
                                    })
                                    ->toArray();
                            }),
                    ]),

                Forms\Components\Section::make('Формула расчета')
                    ->schema([
                        Forms\Components\Textarea::make('formula')
                            ->label('Формула')
                            ->rows(3)
                            ->helperText('Используйте переменные из характеристик. Пример: length * width * height, добавьте умножение * quantity если будете считать количество')
                            ->placeholder('length * width * height'),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(50),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Единица измерения')
                    ->badge(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Статус')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->emptyStateHeading('Нет шаблонов товаров')
            ->emptyStateDescription('Создайте первый шаблон товара, чтобы начать работу.')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->color('info'),
                Tables\Actions\EditAction::make()->label(''),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->visible(fn () => Auth::user() && Auth::user()->role->value === 'admin'),
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
                InfoSection::make('Основная информация')
                    ->schema([
                        TextEntry::make('name')->label('Название шаблона'),
                        TextEntry::make('description')->label('Описание'),
                        TextEntry::make('unit')->label('Единица измерения')->badge(),
                        IconEntry::make('is_active')->label('Активный')->boolean(),
                    ])->columns(2),

                InfoSection::make('Характеристики')
                    ->schema([
                        ViewEntry::make('characteristics_table')
                            ->view('filament.infolists.characteristics-table')
                            ->viewData(fn ($record) => ['record' => $record])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Инлайновое отображение характеристик реализовано через infolist RepeatableEntry
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductTemplates::route('/'),
            'create' => Pages\CreateProductTemplate::route('/create'),
            'view' => Pages\ViewProductTemplate::route('/{record}'),
            'edit' => Pages\EditProductTemplate::route('/{record}/edit'),
        ];
    }
}
