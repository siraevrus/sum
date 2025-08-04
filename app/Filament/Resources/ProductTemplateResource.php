<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductTemplateResource\Pages;
use App\Models\ProductTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductTemplateResource extends Resource
{
    protected static ?string $model = ProductTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Характеристики товара';

    protected static ?string $modelLabel = 'Шаблон товара';

    protected static ?string $pluralModelLabel = 'Шаблоны товаров';

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
                            ->relationship('attributes')
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
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                    ]),

                Forms\Components\Section::make('Формула расчета')
                    ->schema([
                        Forms\Components\Textarea::make('formula')
                            ->label('Формула')
                            ->rows(3)
                            ->helperText('Используйте переменные из характеристик. Пример: length * width * height')
                            ->placeholder('length * width * height'),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('test_formula')
                                ->label('Тестировать формулу')
                                ->icon('heroicon-o-play')
                                ->action(function (ProductTemplate $record, array $data) {
                                    // Здесь будет логика тестирования формулы
                                    return 'Формула протестирована';
                                })
                                ->requiresConfirmation()
                                ->modalHeading('Тестирование формулы')
                                ->modalDescription('Введите тестовые значения для проверки формулы')
                                ->form([
                                    Forms\Components\TextInput::make('test_value')
                                        ->label('Тестовое значение')
                                        ->required(),
                                ]),
                        ]),
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

                Tables\Columns\TextColumn::make('attributes_count')
                    ->label('Характеристики')
                    ->counts('attributes')
                    ->formatStateUsing(fn (int $state): string => "{$state} характеристик"),

                Tables\Columns\TextColumn::make('formula')
                    ->label('Формула')
                    ->limit(30),

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
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('test_formula')
                    ->label('Тест формулы')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(function (ProductTemplate $record) {
                        // Здесь будет логика тестирования
                        return 'Формула протестирована';
                    }),
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
            'index' => Pages\ListProductTemplates::route('/'),
            'create' => Pages\CreateProductTemplate::route('/create'),
            'view' => Pages\ViewProductTemplate::route('/{record}'),
            'edit' => Pages\EditProductTemplate::route('/{record}/edit'),
        ];
    }
}
