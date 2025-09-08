<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewReceipt extends ViewRecord
{
    protected static string $resource = ReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Редактировать'),
            Action::make('receive')
                ->label('Принять товар')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(function (Product $record): bool {
                    return $record->status === Product::STATUS_FOR_RECEIPT;
                })
                ->action(function (Product $record): void {
                    $record->update([
                        'status' => Product::STATUS_IN_STOCK,
                        'actual_arrival_date' => now(),
                    ]);

                    Notification::make()
                        ->title('Товар успешно принят')
                        ->body('Товар добавлен в остатки на складе.')
                        ->success()
                        ->send();

                    $this->redirect(ReceiptResource::getUrl('index'));
                })
                ->requiresConfirmation()
                ->modalHeading('Принять товар')
                ->modalDescription('Товар будет перемещен в остатки на складе.')
                ->modalSubmitActionLabel('Принять'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Основная информация')
                    ->schema([
                        TextEntry::make('warehouse.name')->label('Склад'),
                        TextEntry::make('shipping_location')->label('Место отгрузки')->placeholder('—'),
                        TextEntry::make('transport_number')->label('Номер транспорта')->placeholder('—'),
                        TextEntry::make('shipping_date')->label('Дата отгрузки')->date()->placeholder('—'),
                        TextEntry::make('expected_arrival_date')->label('Ожидаемая дата')->date()->placeholder('—'),
                    ])
                    ->columns(2),

                InfoSection::make('Информация о товаре')
                    ->schema([
                        TextEntry::make('name')->label('Наименование'),
                        TextEntry::make('quantity')->label('Количество'),
                        TextEntry::make('calculated_volume')
                            ->label('Объем')
                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format($state, 3, '.', ' ') : '0.000'),
                        TextEntry::make('template.name')->label('Шаблон товара')->placeholder('—'),
                        TextEntry::make('producer.name')->label('Производитель')->placeholder('—'),
                        TextEntry::make('status')
                            ->label('Статус')
                            ->formatStateUsing(fn (Product $record): string => $record->isForReceipt() ? 'Для приемки' : 'На складе')
                            ->badge()
                            ->color(fn (Product $record) => $record->isForReceipt() ? 'warning' : 'success'),
                        TextEntry::make('creator.name')->label('Создатель')->placeholder('—'),
                    ])
                    ->columns(2),

                InfoSection::make('Документы')
                    ->schema([
                        TextEntry::make('document_path')
                            ->label('Файлы')
                            ->formatStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return implode("\n", $state);
                                }

                                return $state ?: '—';
                            })
                            ->extraAttributes(['class' => 'whitespace-pre-line'])
                            ->columnSpanFull(),
                    ]),

                InfoSection::make('Характеристики товара')
                    ->schema([
                        KeyValueEntry::make('attributes')
                            ->label('Характеристики')
                            ->keyLabel('')
                            ->valueLabel('')
                            ->state(function (Product $record) {
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

                                $templateId = $record->product_template_id ?? ($record->template->id ?? null);
                                $labels = [];
                                if ($templateId) {
                                    $labels = \App\Models\ProductAttribute::where('product_template_id', $templateId)
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
