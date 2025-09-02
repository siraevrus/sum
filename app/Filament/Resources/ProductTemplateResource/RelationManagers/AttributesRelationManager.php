<?php

namespace App\Filament\Resources\ProductTemplateResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AttributesRelationManager extends RelationManager
{
    protected static string $relationship = 'attributes';

    protected static ?string $title = 'Характеристики';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('variable')
                    ->label('Переменная')
                    ->required()
                    ->maxLength(50)
                    ->helperText('Только английские буквы, цифры и подчеркивание')
                    ->rules(['regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/'])
                    ->unique(ignorable: fn ($record) => $record, modifyRuleUsing: function ($rule, $get) {
                        $parentId = $this->getOwnerRecord()->id;
                        return $rule->where('product_template_id', $parentId);
                    })
                    ->validationMessages([
                        'unique' => 'Переменная должна быть уникальной для данного шаблона.'
                    ]),

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
                    ->label('Единица')
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
                    ->label('Обязательно'),

                Forms\Components\Checkbox::make('is_in_formula')
                    ->label('В формуле')
                    ->visible(fn ($get) => $get('type') === 'number'),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Порядок')
                    ->numeric()
                    ->default(0),
            ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Название')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('variable')->label('Переменная')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('type')->label('Тип')->badge()->sortable(),
                Tables\Columns\TextColumn::make('unit')->label('Ед.')->badge()->sortable(),
                Tables\Columns\IconColumn::make('is_required')->label('Обяз.')->boolean(),
                Tables\Columns\IconColumn::make('is_in_formula')->label('Форм.')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->label('Порядок')->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Добавить'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label(''),
                Tables\Actions\DeleteAction::make()->label(''),
            ])
            ->defaultSort('sort_order');
    }
}
