<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Sale;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make()
            //    ->label('Изменить'),
            Actions\Action::make('correction')
                ->label('Корректировка')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->visible(function (Sale $record): bool {
                    return $record->payment_status === Sale::PAYMENT_STATUS_PAID;
                })
                ->form([
                    Forms\Components\Textarea::make('reason_cancellation')
                        ->label('Причина отмены')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function (Sale $record, array $data): void {
                    $record->cancelSale($data['reason_cancellation']);
                })
                ->requiresConfirmation()
                ->modalHeading('Корректировка продажи')
                ->modalDescription('Вы отменяете продажу, товары вернутся на склад. Укажите причину отмены.')
                ->modalSubmitActionLabel('Отменить продажу'),
            Actions\Action::make('cancel_sale')
                ->label('Отменить продажу')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(function (Sale $record): bool {
                    return $record->payment_status !== Sale::PAYMENT_STATUS_CANCELLED;
                })
                ->form([
                    Forms\Components\Textarea::make('reason_cancellation')
                        ->label('Причины отмены')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function (Sale $record, array $data): void {
                    $record->cancelSale($data['reason_cancellation']);
                })
                ->requiresConfirmation()
                ->modalHeading('Отменить продажу')
                ->modalDescription('Товар будет возвращен на склад и продажа будет отменена.')
                ->modalSubmitActionLabel('ОК'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Основная информация')
                    ->schema([
                        TextEntry::make('sale_number')
                            ->label('Номер продажи'),
                        TextEntry::make('sale_date')
                            ->label('Дата продажи')
                            ->date(),
                        TextEntry::make('product.name')
                            ->label('Товар'),
                        TextEntry::make('quantity')
                            ->label('Количество')
                            ->badge(),
                        TextEntry::make('warehouse.name')
                            ->label('Склад'),
                        TextEntry::make('total_price')
                            ->label('Общая сумма')
                            ->formatStateUsing(fn ($state, $record) => number_format($state, 2, '.', ' ').' '.($record->currency ?? '')),
                        TextEntry::make('cash_amount')
                            ->label('Сумма (нал)')
                            ->formatStateUsing(fn ($state) => number_format($state, 2, '.', ' ')),
                        TextEntry::make('nocash_amount')
                            ->label('Сумма (безнал)')
                            ->formatStateUsing(fn ($state) => number_format($state, 2, '.', ' ')),
                        TextEntry::make('exchange_rate')
                            ->label('Курс валюты')
                            ->formatStateUsing(fn ($state) => number_format($state, 4, '.', ' ')),
                        TextEntry::make('user.name')
                            ->label('Продавец'),
                        TextEntry::make('payment_status')
                            ->label('Статус оплаты')
                            ->formatStateUsing(fn ($state, $record) => $record->getPaymentStatusLabel())
                            ->badge()
                            ->color(fn ($record) => $record->getPaymentStatusColor()),
                    ])
                    ->columns(3),

                Section::make('Информация о клиенте')
                    ->schema([
                        TextEntry::make('customer_name')
                            ->label('Имя клиента')
                            ->placeholder('Не указано'),
                        TextEntry::make('customer_phone')
                            ->label('Телефон клиента')
                            ->placeholder('Не указан'),
                        TextEntry::make('customer_email')
                            ->label('Email')
                            ->placeholder('Не указан'),
                        TextEntry::make('customer_address')
                            ->label('Адрес')
                            ->placeholder('Не указан')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Дополнительная информация')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Заметки')
                            ->placeholder('Нет заметок')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Причина отмены')
                    ->schema([
                        TextEntry::make('reason_cancellation')
                            ->label('Причина отмены')
                            ->visible(fn ($record) => $record->reason_cancellation !== null)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->reason_cancellation !== null)
                    ->collapsible(),
            ]);
    }
}
