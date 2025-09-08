<?php

namespace App\Filament\Resources\RequestResource\Pages;

use App\Filament\Resources\RequestResource;
use App\UserRole;
use Filament\Actions;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewRequest extends ViewRecord
{
    protected static string $resource = RequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Изменить')
                ->visible(function ($livewire): bool {
                    $user = Auth::user();
                    if (! $user) {
                        return false;
                    }

                    // Скрыть для менеджера, если статус одобрен
                    if ($user->role === UserRole::SALES_MANAGER) {
                        $record = $livewire->getRecord();
                        if (method_exists($record, 'getStatusLabel') && $record->getStatusLabel() === 'Одобрен') {
                            return false;
                        }
                        if (property_exists($record, 'status') && in_array($record->status, ['approved', 'Одобрен'], true)) {
                            return false;
                        }
                    }

                    // Скрыть для работника склада, если статус одобрен
                    if ($user->role === UserRole::WAREHOUSE_WORKER) {
                        $record = $livewire->getRecord();
                        if (method_exists($record, 'getStatusLabel') && $record->getStatusLabel() === 'Одобрен') {
                            return false;
                        }
                        if (property_exists($record, 'status') && in_array($record->status, ['approved', 'Одобрен'], true)) {
                            return false;
                        }
                    }

                    return true;
                }),
            Actions\Action::make('approve')
                ->label('Одобрен')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(function (\App\Models\Request $record): bool {
                    $user = Auth::user();
                    if (! $user) {
                        return false;
                    }

                    if ($user->role === UserRole::SALES_MANAGER) {
                        return false;
                    }

                    return method_exists($record, 'canBeApproved') ? $record->canBeApproved() : false;
                })
                ->requiresConfirmation()
                ->action(function (\App\Models\Request $record): void {
                    $record->approve();
                })
                ->successNotificationTitle('Статус изменён'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Основная информация')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Заголовок'),

                        TextEntry::make('status')
                            ->label('Статус')
                            ->formatStateUsing(fn ($state, $record) => $record->getStatusLabel())
                            ->badge()
                            ->color(fn ($record) => $record->getStatusColor()),

                        TextEntry::make('priority')
                            ->label('Приоритет')
                            ->formatStateUsing(fn ($state, $record) => $record->getPriorityLabel())
                            ->badge()
                            ->color(function ($record) {
                                return method_exists($record, 'getPriorityColor') ? $record->getPriorityColor() : 'gray';
                            }),

                        TextEntry::make('warehouse.name')
                            ->label('Склад'),

                        TextEntry::make('user.name')
                            ->label('Создатель'),

                        TextEntry::make('productTemplate.name')
                            ->label('Шаблон товара')
                            ->placeholder('Не выбран'),

                        TextEntry::make('quantity')
                            ->label('Количество')
                            ->badge(),

                        TextEntry::make('calculated_volume')
                            ->label('Рассчитанный объём')
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 3, '.', ' ') : '0.000'),

                        TextEntry::make('created_at')
                            ->label('Создан')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make('Описание')
                    ->schema([
                        TextEntry::make('description')
                            ->label('Описание')
                            ->columnSpanFull(),
                    ]),

                Section::make('Характеристики товара')
                    ->schema([
                        TextEntry::make('quantity')
                            ->label('Количество')
                            ->badge(),

                        KeyValueEntry::make('attributes')
                            ->label('Характеристики')
                            ->keyLabel('')
                            ->valueLabel('')
                            ->state(function ($record) {
                                $state = $record->getAttribute('attributes');
                                if (is_string($state)) {
                                    $decoded = json_decode($state, true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                        $state = $decoded;
                                    }
                                } elseif ($state instanceof \stdClass) {
                                    $state = (array) $state;
                                } elseif ($state instanceof \Illuminate\Support\Collection) {
                                    $state = $state->toArray();
                                }

                                if (! is_array($state) || empty($state)) {
                                    return [];
                                }

                                $templateId = $record->product_template_id ?? null;
                                $labels = [];
                                if ($templateId) {
                                    $labels = \App\Models\ProductAttribute::query()
                                        ->where('product_template_id', $templateId)
                                        ->pluck('name', 'variable')
                                        ->toArray();
                                }

                                $mapped = [];
                                foreach ($state as $key => $value) {
                                    $label = trim((string) ($labels[$key] ?? $key));
                                    $mapped[$label] = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
                                }

                                return $mapped;
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

            ]);
    }
}
