<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

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
                Infolists\Components\Section::make('Основная информация')
                    ->schema(function () {
                        $components = [
                            Infolists\Components\TextEntry::make('name')
                                ->label('Наименование')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                ->weight('bold'),

                            Infolists\Components\TextEntry::make('transport_number')
                                ->label('Номер транспорта'),

                            Infolists\Components\TextEntry::make('arrival_date')
                                ->label('Дата поступления')
                                ->date('d.m.Y'),

                            Infolists\Components\TextEntry::make('status')
                                ->label('Статус')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'in_stock' => 'success',
                                    'in_transit' => 'warning',
                                    'for_receipt' => 'info',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'in_stock' => 'На складе',
                                    'in_transit' => 'В пути',
                                    'for_receipt' => 'На приемку',
                                    default => $state,
                                }),

                            Infolists\Components\TextEntry::make('creator.name')
                                ->label('Создатель'),
                        ];

                        // Добавляем место отгрузки если заполнено
                        if (! empty($this->record->shipping_location)) {
                            $components[] = Infolists\Components\TextEntry::make('shipping_location')
                                ->label('Место отгрузки')
                                ->state($this->record->shipping_location);
                        }

                        return $components;
                    })
                    ->columns(2),

                Infolists\Components\Section::make('Информация о товаре')
                    ->schema(function () {
                        $attributes = $this->record->attributes ?? [];
                        $components = [];

                        // Добавляем производителя в начало
                        $components[] = Infolists\Components\TextEntry::make('producer.name')
                            ->label('Производитель');

                        // Добавляем количество
                        $components[] = Infolists\Components\TextEntry::make('quantity')
                            ->label('Количество')
                            ->numeric();

                        // Добавляем объем
                        $components[] = Infolists\Components\TextEntry::make('calculated_volume')
                            ->label('Объем')
                            ->formatStateUsing(function ($state) {
                                if (is_numeric($state)) {
                                    return number_format($state, 3, '.', ' ').' '.($this->record->productTemplate->unit ?? '');
                                }

                                return $state ?: '0.000';
                            });

                        if (empty($attributes)) {
                            $components[] = Infolists\Components\TextEntry::make('no_attributes')
                                ->label('')
                                ->state('Характеристики не заданы')
                                ->color('gray');
                        } else {
                            // Построим карту переменная -> полное название из шаблона, чтобы показать названия характеристик
                            $template = $this->record->productTemplate;
                            $variableToName = [];
                            if ($template && method_exists($template, 'attributes')) {
                                foreach ($template->attributes as $templateAttribute) {
                                    $variableToName[$templateAttribute->variable] = $templateAttribute->full_name ?? $templateAttribute->variable;
                                }
                            }

                            foreach ($attributes as $key => $value) {
                                $label = $variableToName[$key] ?? ucfirst($key);
                                $components[] = Infolists\Components\TextEntry::make("attribute_{$key}")
                                    ->label($label)
                                    ->state($value);
                            }
                        }

                        return $components;
                    })
                    ->columns(2),

                Infolists\Components\Section::make('Дополнительная информация')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Заметки')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('description')
                            ->label('')
                            ->hiddenLabel()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Секция с информацией о корректировке
                Infolists\Components\Section::make('Информация о корректировке')
                    ->schema([
                        Infolists\Components\TextEntry::make('correction_info')
                            ->label('')
                            ->state(function () {
                                if (! $this->record->hasCorrection()) {
                                    return null;
                                }
                                $correctionText = $this->record->correction ?? 'Нет текста уточнения';
                                $updatedAt = $this->record->updated_at?->format('d.m.Y H:i') ?? 'Неизвестно';

                                return "⚠️ **У товара есть уточнение:** \"{$correctionText}\"\n\n*Дата внесения:* {$updatedAt}";
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (): bool => $this->record->hasCorrection())
                    ->icon('heroicon-o-exclamation-triangle'),

                // Секция с документами
                Infolists\Components\Section::make('Документы')
                    ->schema([
                        Infolists\Components\ViewEntry::make('documents')
                            ->view('filament.infolists.components.documents-list')
                            ->viewData(function (): array {
                                if (! $this->record->document_path || empty($this->record->document_path)) {
                                    return ['documents' => []];
                                }
                                $documents = is_array($this->record->document_path) ? $this->record->document_path : [];
                                if (empty($documents)) {
                                    return ['documents' => []];
                                }
                                $documentsList = [];
                                foreach ($documents as $index => $document) {
                                    $fileName = basename($document);
                                    $fileUrl = asset('storage/'.$document);
                                    $documentsList[] = [
                                        'index' => $index + 1,
                                        'name' => $fileName,
                                        'url' => $fileUrl,
                                    ];
                                }

                                return ['documents' => $documentsList];
                            })
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (): bool => $this->record->document_path &&
                        is_array($this->record->document_path) &&
                        ! empty($this->record->document_path)
                    )
                    ->icon('heroicon-o-document'),
            ]);
    }
}
