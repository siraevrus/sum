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

                // Поле sort_order полностью убрано из формы
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
                Tables\Actions\CreateAction::make()
                    ->label('Добавить')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Полностью исключаем sort_order из данных формы
                        unset($data['sort_order']);
                        return $data;
                    })
                    ->using(function (array $data): \App\Models\ProductAttribute {
                        // Создаем атрибут вручную, чтобы контролировать sort_order
                        $template = $this->getOwnerRecord();
                        $maxSortOrder = $template->attributes()->max('sort_order') ?? -1;
                        
                        $attribute = new \App\Models\ProductAttribute;
                        $attribute->product_template_id = $template->id;
                        $attribute->name = $data['name'];
                        $attribute->variable = $data['variable'];
                        $attribute->type = $data['type'];
                        $attribute->options = $data['options'] ?? null;
                        $attribute->unit = $data['unit'] ?? null;
                        $attribute->is_required = $data['is_required'] ?? false;
                        $attribute->is_in_formula = $data['is_in_formula'] ?? false;
                        $attribute->sort_order = $maxSortOrder + 1; // Принудительно устанавливаем целое число
                        
                        $attribute->save();
                        
                        return $attribute;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Полностью исключаем sort_order из данных формы
                        unset($data['sort_order']);
                        return $data;
                    })
                    ->using(function (\App\Models\ProductAttribute $record, array $data): \App\Models\ProductAttribute {
                        // Обновляем атрибут вручную, чтобы контролировать sort_order
                        $record->name = $data['name'];
                        $record->variable = $data['variable'];
                        $record->type = $data['type'];
                        $record->options = $data['options'] ?? null;
                        $record->unit = $data['unit'] ?? null;
                        $record->is_required = $data['is_required'] ?? false;
                        $record->is_in_formula = $data['is_in_formula'] ?? false;
                        // sort_order не изменяем при редактировании
                        
                        $record->save();
                        
                        return $record;
                    }),
                Tables\Actions\DeleteAction::make()->label(''),
            ])
            ->defaultSort('sort_order');
    }
}
