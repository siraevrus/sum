<?php

namespace App\Filament\Resources\ProductInTransitResource\Pages;

use App\Filament\Resources\ProductInTransitResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewProductInTransit extends ViewRecord
{
    protected static string $resource = ProductInTransitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Изменить'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Основная информация')
                    ->schema([
                        TextEntry::make('name')->label('Наименование')->size(TextEntry\TextEntrySize::Large)->weight('bold'),
                        TextEntry::make('status')
                            ->label('Статус')
                            ->badge()
                            ->color(fn (Product $record) => $record->isInTransit() ? 'info' : ($record->isForReceipt() ? 'warning' : 'success'))
                            ->formatStateUsing(fn (Product $record): string => $record->isInTransit() ? 'В пути' : ($record->isForReceipt() ? 'Для приемки' : 'На складе')),
                        TextEntry::make('warehouse.name')->label('Склад назначения'),
                        TextEntry::make('shipping_location')->label('Место отгрузки')->placeholder('—'),
                        TextEntry::make('transport_number')->label('Номер транспорта')->placeholder('—'),
                        TextEntry::make('shipping_date')->label('Дата отгрузки')->date()->placeholder('—'),
                        TextEntry::make('expected_arrival_date')->label('Ожидаемая дата')->date()->placeholder('—'),
                        TextEntry::make('creator.name')->label('Создатель')->placeholder('—'),
                    ])
                    ->columns(2),

                InfoSection::make('Информация о товаре')
                    ->schema([
                        TextEntry::make('producer.name')->label('Производитель')->placeholder('—'),
                        TextEntry::make('quantity')->label('Количество'),
                        TextEntry::make('calculated_volume')
                            ->label('Объем')
                            ->formatStateUsing(function ($state, Product $record) {
                                $unit = $record->template?->unit ?? '';

                                return is_numeric($state) ? number_format($state, 3, '.', ' ').($unit ? ' '.$unit : '') : '0.000';
                            }),
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

                InfoSection::make('Дополнительная информация')
                    ->schema([
                        TextEntry::make('notes')->label('Заметки')->columnSpanFull(),
                    ])
                    ->columns(2),

            ]);
    }
}
