<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Components\KeyValueEntry;
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
            
            Actions\Action::make('clear_correction')
                ->label('Скорректировано')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->hasCorrection())
                ->action(function (): void {
                    $this->record->clearCorrectionStatus();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Статус коррекции сброшен')
                        ->body('Товар возвращен к обычному статусу')
                        ->success()
                        ->send();
                        
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                })
                ->modalHeading('Подтверждение о внесении изменения')
                ->modalDescription('Информация о поступившем заказке будет скорректирована и был внесен актуальный остаток. Это действие нельзя отменить.')
                ->modalSubmitActionLabel('Скорректировано'),
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
                                ->weight('bold')
                                ->badge()
                                ->color('primary'),

                            Infolists\Components\TextEntry::make('creator.name')
                                ->label('Сотрудник')
                                ->badge()
                                ->color('info'),

                            Infolists\Components\TextEntry::make('warehouse.name')
                                ->label('Склад назначения')
                                ->badge()
                                ->color('warning'),

                            Infolists\Components\TextEntry::make('arrival_date')
                                ->label('Дата поступления')
                                ->date('d.m.Y')
                                ->badge()
                                ->color('success'),

                            Infolists\Components\TextEntry::make('transport_number')
                                ->label('Номер транспорта')
                                ->badge()
                                ->color('gray'),

                            Infolists\Components\TextEntry::make('status')
                                ->label('Статус')
                                ->badge()
                                ->color(function (): string {
                                    if ($this->record->hasCorrection()) {
                                        return 'danger';
                                    }
                                    
                                    return match ($this->record->status) {
                                        'in_stock' => 'success',
                                        'in_transit' => 'warning',
                                        'for_receipt' => 'info',
                                        default => 'gray',
                                    };
                                })
                                ->formatStateUsing(function (): string {
                                    if ($this->record->hasCorrection()) {
                                        return 'Коррекция';
                                    }
                                    
                                    return match ($this->record->status) {
                                        'in_stock' => 'На складе',
                                        'in_transit' => 'В пути',
                                        'for_receipt' => 'На приемку',
                                        default => $this->record->status,
                                    };
                                }),
                        ];

                        // Добавляем место отгрузки если заполнено
                        if (! empty($this->record->shipping_location)) {
                            $components[] = Infolists\Components\TextEntry::make('shipping_location')
                                ->label('Место отгрузки')
                                ->state($this->record->shipping_location)
                                ->badge()
                                ->color('secondary');
                        }

                        return $components;
                    })
                    ->columns(3),

                Infolists\Components\Section::make('Информация о товаре')
                    ->schema(function () {
                        $attributes = $this->record->attributes ?? [];
                        $components = [];

                        // Добавляем производителя в начало
                        $components[] = Infolists\Components\TextEntry::make('producer.name')
                            ->label('Производитель')
                            ->badge()
                            ->color('info');

                        // Добавляем количество
                        $components[] = Infolists\Components\TextEntry::make('quantity')
                            ->label('Количество')
                            ->numeric()
                            ->badge()
                            ->color('success');

                        // Добавляем объем
                        $components[] = Infolists\Components\TextEntry::make('calculated_volume')
                            ->label('Объем')
                            ->badge()
                            ->color('warning')
                            ->formatStateUsing(function ($state) {
                                if (is_numeric($state)) {
                                    return number_format($state, 3, '.', ' ').' '.($this->record->productTemplate->unit ?? '');
                                }

                                return e($state ?: '0.000');
                            });

                        // Добавляем характеристики в табличном виде
                        $components[] = KeyValueEntry::make('attributes')
                            ->label('Характеристики')
                            ->keyLabel('')
                            ->valueLabel('')
                            ->state(function () {
                                $attributes = $this->record->attributes ?? [];
                                if (empty($attributes)) {
                                    return [];
                                }

                                // Построим карту переменная -> полное название из шаблона
                                $template = $this->record->productTemplate;
                                $variableToName = [];
                                if ($template && method_exists($template, 'attributes')) {
                                    foreach ($template->attributes as $templateAttribute) {
                                        $variableToName[$templateAttribute->variable] = $templateAttribute->full_name ?? $templateAttribute->variable;
                                    }
                                }

                                $mapped = [];
                                foreach ($attributes as $key => $value) {
                                    $label = $variableToName[$key] ?? ucfirst($key);
                                    $mapped[$label] = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
                                }

                                return $mapped;
                            })
                            ->columnSpanFull();

                        return $components;
                    })
                    ->columns(3),

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
