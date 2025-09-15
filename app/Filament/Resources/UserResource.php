<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\UserRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Сотрудники';

    protected static ?string $modelLabel = 'Сотрудник';

    protected static ?string $pluralModelLabel = 'Сотрудники';

    protected static ?int $navigationSort = 3;

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
                        Forms\Components\Select::make('role')
                            ->label('Роль')
                            ->options([
                                UserRole::ADMIN->value => UserRole::ADMIN->label(),
                                UserRole::OPERATOR->value => UserRole::OPERATOR->label(),
                                UserRole::WAREHOUSE_WORKER->value => UserRole::WAREHOUSE_WORKER->label(),
                                UserRole::SALES_MANAGER->value => UserRole::SALES_MANAGER->label(),
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === UserRole::ADMIN->value) {
                                    $set('company_id', null);
                                    $set('warehouse_id', null);
                                }
                            }),

                        Forms\Components\TextInput::make('username')
                            ->label('Логин')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('name')
                            ->label('Полное имя')
                            ->maxLength(255)
                            ->hidden()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                // Автоматически формируем полное имя из ФИО
                                $firstName = $get('first_name') ?? '';
                                $lastName = $get('last_name') ?? '';
                                $middleName = $get('middle_name') ?? '';

                                $fullName = trim(implode(' ', array_filter([$lastName, $firstName, $middleName])));
                                if (! empty($fullName)) {
                                    $set('name', $fullName);
                                }
                            }),

                        Forms\Components\TextInput::make('first_name')
                            ->label('Имя')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                // Обновляем полное имя при изменении ФИО
                                $firstName = $state ?? '';
                                $lastName = $get('last_name') ?? '';
                                $middleName = $get('middle_name') ?? '';

                                $fullName = trim(implode(' ', array_filter([$lastName, $firstName, $middleName])));
                                if (! empty($fullName)) {
                                    $set('name', $fullName);
                                }
                            }),

                        Forms\Components\TextInput::make('last_name')
                            ->label('Фамилия')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                // Обновляем полное имя при изменении ФИО
                                $firstName = $get('first_name') ?? '';
                                $lastName = $state ?? '';
                                $middleName = $get('middle_name') ?? '';

                                $fullName = trim(implode(' ', array_filter([$lastName, $firstName, $middleName])));
                                if (! empty($fullName)) {
                                    $set('name', $fullName);
                                }
                            }),

                        Forms\Components\TextInput::make('middle_name')
                            ->label('Отчество')
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                // Обновляем полное имя при изменении ФИО
                                $firstName = $get('first_name') ?? '';
                                $lastName = $get('last_name') ?? '';
                                $middleName = $state ?? '';

                                $fullName = trim(implode(' ', array_filter([$lastName, $firstName, $middleName])));
                                if (! empty($fullName)) {
                                    $set('name', $fullName);
                                }
                            }),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(20)
                            ->helperText('Введите номер телефона')
                            ->regex('/^[0-9\+\-\(\)\s]+$/')
                            ->validationMessages([
                                'regex' => 'Разрешены только цифры и символы: + - ( ) пробел'
                            ]),

                        Forms\Components\TextInput::make('password')
                            ->label('Пароль')
                            
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8),

                        Forms\Components\Toggle::make('is_blocked')
                            ->label('Заблокирован')
                            ->default(false),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Компания и склад')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Компания')
                            ->relationship('company', 'name', function (Builder $query) {
                                return $query->where('is_archived', false);
                            })
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('role') !== UserRole::ADMIN->value),

                        Forms\Components\Select::make('warehouse_id')
                            ->label('Склад')
                            ->relationship('warehouse', 'name', function (Builder $query, $get) {
                                $companyId = $get('company_id');
                                if ($companyId) {
                                    $query->where('company_id', $companyId);
                                }

                                return $query;
                            })
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('role') !== UserRole::ADMIN->value),
                    ])
                    ->columns(4)
                    ->visible(fn ($get) => $get('role') !== UserRole::ADMIN->value),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('username')
                    ->label('Логин')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('ФИО')
                    ->searchable(['first_name', 'last_name', 'middle_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Компания')
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Должность')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label()),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->sortable(),

                Tables\Columns\TextColumn::make('blocked_at')
                    ->label('Дата блокировки')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_blocked')
                    ->label('Статус')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->emptyStateHeading('Нет пользователей')
            ->emptyStateDescription('Создайте первого пользователя, чтобы начать работу.')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Роль')
                    ->options([
                        UserRole::ADMIN->value => UserRole::ADMIN->label(),
                        UserRole::OPERATOR->value => UserRole::OPERATOR->label(),
                        UserRole::WAREHOUSE_WORKER->value => UserRole::WAREHOUSE_WORKER->label(),
                        UserRole::SALES_MANAGER->value => UserRole::SALES_MANAGER->label(),
                    ]),

                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Компания')
                    ->relationship('company', 'name', function (Builder $query) {
                        return $query->where('is_archived', false);
                    }),

                Tables\Filters\Filter::make('phone')
                    ->label('Телефон')
                    ->form([
                        Forms\Components\TextInput::make('phone')
                            ->label('Номер телефона')
                            ->placeholder('Введите часть номера для поиска'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['phone'],
                            fn (Builder $query, $phone): Builder => $query->where('phone', 'like', "%{$phone}%")
                        );
                    }),

                Tables\Filters\TernaryFilter::make('is_blocked')
                    ->label('Заблокированные'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label(''),
                Tables\Actions\Action::make('block')
                    ->label('')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => ! $record->is_blocked)
                    ->action(function (User $record): void {
                        $record->update([
                            'is_blocked' => true,
                            'blocked_at' => now(),
                        ]);
                    }),

                Tables\Actions\Action::make('unblock')
                    ->label('')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->is_blocked)
                    ->action(function (User $record): void {
                        $record->update([
                            'is_blocked' => false,
                            'blocked_at' => null,
                        ]);
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Администратор видит всех пользователей
        if (\Illuminate\Support\Facades\Auth::user()->role === UserRole::ADMIN) {
            return $query;
        }

        // Остальные пользователи видят только пользователей своей компании
        return $query->where('company_id', \Illuminate\Support\Facades\Auth::user()->company_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
