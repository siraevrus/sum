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
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProductTemplateResource extends Resource
{
    protected static ?string $model = ProductTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Характеристики товара';

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

                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3),

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

                        Forms\Components\Toggle::make('is_active')
                            ->label('Активный')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Характеристики')
                    ->schema([
                        Forms\Components\Repeater::make('attributes')
                            ->label('Характеристики')
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
                                    ->rules(['regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/']),

                                Forms\Components\Select::make('type')
                                    ->label('Тип данных')
                                    ->options([
                                        'number' => 'Число',
                                        'text' => 'Текст',
                                        'select' => 'Выпадающий список',
                                    ])
                                    ->default('number')
                                    ->reactive(),

                                Forms\Components\Textarea::make('options')
                                    ->label('Варианты (через запятую)')
                                    ->visible(fn ($get) => $get('type') === 'select')
                                    ->helperText('Введите варианты через запятую'),

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

                                Forms\Components\Checkbox::make('is_required')
                                    ->label('Обязательно к заполнению'),

                                Forms\Components\Checkbox::make('is_in_formula')
                                    ->label('Учитывать в формуле')
                                    ->visible(fn ($get) => $get('type') === 'number'),
                            ])
                            ->columns(3)
                            ->orderColumn('sort_order')
                            ->defaultItems(0)
                            ->default([])
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->saveRelationshipsUsing(function ($operation, $state, ProductTemplate $record) {
                                // Сохраняем атрибуты вручную, чтобы поддержать create/edit
                                $record->attributes()->delete();
                                $sort = 0;
                                foreach ($state as $row) {
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
                                        'sort_order' => $sort++,
                                    ]);
                                }
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
                Tables\Actions\ViewAction::make()->label(''),
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
                        RepeatableEntry::make('attributes')
                            ->relationship('attributes')
                            ->label('')
                            ->schema([
                                TextEntry::make('name')->label('Название'),
                                TextEntry::make('variable')->label('Переменная'),
                                TextEntry::make('type')->label('Тип')->badge(),
                                TextEntry::make('unit')->label('Единица')->badge(),
                                TextEntry::make('options')
                                    ->label('Варианты')
                                    ->formatStateUsing(function ($state) {
                                        if (is_array($state)) {
                                            return implode(', ', $state);
                                        }

                                        return (string) $state;
                                    })
                                    ->visible(fn ($record) => ($record->type ?? null) === 'select'),
                                IconEntry::make('is_required')->label('Обязательно')->boolean(),
                                IconEntry::make('is_in_formula')->label('В формуле')->boolean(),
                                TextEntry::make('sort_order')->label('Порядок'),
                            ])->columns(3),
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
