<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Компании';

    protected static ?string $modelLabel = 'Компания';

    protected static ?string $pluralModelLabel = 'Компании';

    protected static ?int $navigationSort = 1;

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
                            ->label('Название компании')
                            ->required()
                            ->maxLength(60),

                        Forms\Components\TextInput::make('general_director')
                            ->label('Генеральный директор')
                            ->maxLength(60),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone_fax')
                            ->label('Телефон/факс')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Адреса')
                    ->schema([
                        Forms\Components\Textarea::make('legal_address')
                            ->label('Юридический адрес')
                            ->rows(3),

                        Forms\Components\Textarea::make('postal_address')
                            ->label('Почтовый адрес')
                            ->rows(3),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Реквизиты')
                    ->schema([
                        Forms\Components\TextInput::make('inn')
                            ->label('ИНН')
                            ->maxLength(10),

                        Forms\Components\TextInput::make('kpp')
                            ->label('КПП')
                            ->maxLength(9),

                        Forms\Components\TextInput::make('ogrn')
                            ->label('ОГРН')
                            ->maxLength(13),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Банковские реквизиты')
                    ->schema([
                        Forms\Components\TextInput::make('bank')
                            ->label('БАНК')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('account_number')
                            ->label('Р/с')
                            ->maxLength(20),

                        Forms\Components\TextInput::make('correspondent_account')
                            ->label('К/с')
                            ->maxLength(20),

                        Forms\Components\TextInput::make('bik')
                            ->label('БИК')
                            ->maxLength(9),
                    ])
                    ->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название компании')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Количество сотрудников')
                    ->counts('employees')
                    ->sortable(),

                Tables\Columns\TextColumn::make('warehouses_count')
                    ->label('Количество складов')
                    ->counts('warehouses')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_archived')
                    ->label('Статус')
                    ->boolean()
                    ->trueIcon('heroicon-o-archive-box')
                    ->falseIcon('heroicon-o-building-office')
                    ->trueColor('danger')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('archived_at')
                    ->label('Дата архивации')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->emptyStateHeading('Нет компаний')
            ->emptyStateDescription('Создайте первую компанию, чтобы начать работу.')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_archived')
                    ->label('Архивированные')
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === true) {
                            return $query->where('is_archived', true);
                        }
                        if ($data['value'] === false) {
                            return $query->where('is_archived', false);
                        }

                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label(''),
                Tables\Actions\Action::make('archive')
                    ->label('')
                    ->icon('heroicon-o-archive-box')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Архивировать компанию?')
                    ->modalDescription('Вы хотите архивировать компанию? Вместе с ней скроются все внесенные данные связанные с этой компанией.')
                    ->modalSubmitActionLabel('Да, архивировать')
                    ->modalCancelActionLabel('Отмена')
                    ->visible(fn (Company $record): bool => ! $record->is_archived)
                    ->action(function (Company $record): void {
                        $record->archive();
                        \Filament\Notifications\Notification::make()
                            ->title('Компания архивирована')
                            ->body('Компания успешно архивирована и скрыта из списка.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('restore')
                    ->label('')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Company $record): bool => $record->is_archived)
                    ->action(function (Company $record): void {
                        $record->restore();
                        \Filament\Notifications\Notification::make()
                            ->title('Компания восстановлена')
                            ->body('Компания успешно восстановлена из архива.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('restore_deleted')
                    ->label('')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Восстановить удаленную компанию?')
                    ->modalDescription('Вы хотите восстановить удаленную компанию?')
                    ->modalSubmitActionLabel('Да, восстановить')
                    ->modalCancelActionLabel('Отмена')
                    ->visible(fn (Company $record): bool => $record->trashed())
                    ->action(function (Company $record): void {
                        $record->restore();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->requiresConfirmation()
                    ->modalHeading('Удалить компанию?')
                    ->modalDescription(function (Company $record) {
                        if ($record->warehouses()->exists() || $record->employees()->exists()) {
                            return 'У компании есть связанные склады или сотрудники. Сначала удалите/перенесите их или архивируйте компанию.';
                        }

                        return 'Вы уверены, что хотите удалить эту компанию? Это действие нельзя отменить.';
                    })
                    ->modalSubmitActionLabel('Да, удалить')
                    ->modalCancelActionLabel('Отмена')
                    ->action(function (Company $record) {
                        if ($record->warehouses()->exists() || $record->employees()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Нельзя удалить компанию')
                                ->body('Нельзя удалить компанию, у которой есть склады или сотрудники. Архивируйте компанию или удалите связанные записи.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->delete();

                        \Filament\Notifications\Notification::make()
                            ->title('Компания удалена')
                            ->body('Компания успешно удалена.')
                            ->success()
                            ->send();
                    })
                    ->visible(function (Company $record) {
                        // Скрываем кнопку удаления если есть связанные записи
                        return ! ($record->warehouses()->exists() || $record->employees()->exists());
                    }),
            ])
            ->bulkActions([
                // Bulk actions скрыты
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
}
