<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

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
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Наименование')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),
                        
                        Infolists\Components\TextEntry::make('productTemplate.name')
                            ->label('Шаблон товара'),
                        
                        Infolists\Components\TextEntry::make('producer.name')
                            ->label('Производитель'),
                        
                        Infolists\Components\TextEntry::make('quantity')
                            ->label('Количество')
                            ->numeric(),
                        
                        Infolists\Components\TextEntry::make('calculated_volume')
                            ->label('Объем')
                            ->formatStateUsing(function ($state) {
                                if (is_numeric($state)) {
                                    return number_format($state, 3, '.', ' ') . ' ' . ($this->record->productTemplate->unit ?? '');
                                }
                                return $state ?: '0.000';
                            }),
                        
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
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Характеристики')
                    ->schema(function () {
                        $attributes = $this->record->attributes ?? [];
                        if (empty($attributes)) {
                            return [
                                Infolists\Components\TextEntry::make('no_attributes')
                                    ->label('')
                                    ->state('Характеристики не заданы')
                                    ->color('gray'),
                            ];
                        }

                        $components = [];
                        foreach ($attributes as $key => $value) {
                            $components[] = Infolists\Components\TextEntry::make("attribute_{$key}")
                                ->label(ucfirst($key))
                                ->state($value);
                        }
                        return $components;
                    })
                    ->columns(2),

                Infolists\Components\Section::make('Дополнительная информация')
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->label('Описание')
                            ->columnSpanFull(),
                        
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Дата создания')
                            ->dateTime('d.m.Y H:i'),
                        
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Дата обновления')
                            ->dateTime('d.m.Y H:i'),
                    ])
                    ->columns(2),

                // Секция с информацией о корректировке
                Infolists\Components\Section::make('Информация о корректировке')
                    ->schema([
                        Infolists\Components\TextEntry::make('correction_info')
                            ->label('')
                            ->state(function () {
                                if (!$this->record->hasCorrection()) {
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
                                if (!$this->record->document_path || empty($this->record->document_path)) {
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
                                        'url' => $fileUrl
                                    ];
                                }
                                return ['documents' => $documentsList];
                            })
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (): bool => $this->record->document_path &&
                        is_array($this->record->document_path) &&
                        !empty($this->record->document_path)
                    )
                    ->icon('heroicon-o-document'),
            ]);
    }
} 