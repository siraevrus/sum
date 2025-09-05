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
                ->modalSubmitActionLabel('Отменить'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
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
