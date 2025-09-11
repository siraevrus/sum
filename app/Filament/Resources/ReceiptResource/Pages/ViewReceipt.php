<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Components\ViewEntry;
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
                ->label('Изменить'),
            Action::make('correction')
                ->label(fn (Product $record): string => $record->hasCorrection() ? 'Изменить уточнение' : 'Уточнение')
                ->icon(fn (Product $record): string => $record->hasCorrection() ? 'heroicon-o-pencil-square' : 'heroicon-o-chat-bubble-left-right')
                ->color(fn (Product $record): string => $record->hasCorrection() ? 'gray' : 'warning')
                ->form([
                    \Filament\Forms\Components\Textarea::make('correction')
                        ->label('Уточнение')
                        ->placeholder('Введите уточнение...')
                        ->required()
                        ->minLength(10)
                        ->maxLength(1000)
                        ->rows(4)
                        ->default(fn (Product $record): ?string => $record->correction),
                ])
                ->action(function (Product $record, array $data): void {
                    $record->addCorrection($data['correction']);

                    Notification::make()
                        ->title('Уточнение сохранено')
                        ->body('Уточнение успешно добавлено к товару.')
                        ->success()
                        ->send();
                })
                ->modalHeading('Внести уточнение')
                ->modalDescription('Укажите дополнительную информацию или уточнения по данному товару')
                ->modalSubmitActionLabel('Сохранить уточнение')
                ->modalCancelActionLabel('Отмена'),
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
                        TextEntry::make('name')->label('Наименование')->size(TextEntrySize::Large)->weight('bold'),
                        TextEntry::make('warehouse.name')->label('Склад назначения'),
                        TextEntry::make('shipping_location')->label('Место отгрузки')->placeholder('—'),
                        TextEntry::make('transport_number')->label('Номер транспорта')->placeholder('—'),
                        TextEntry::make('shipping_date')->label('Дата отгрузки')->date()->placeholder('—'),
                        TextEntry::make('expected_arrival_date')->label('Ожидаемая дата')->date()->placeholder('—'),
                        TextEntry::make('creator.name')->label('Создатель')->placeholder('—'),
                        TextEntry::make('status')
                            ->label('Статус')
                            ->formatStateUsing(fn (Product $record): string => $record->isForReceipt() ? 'Для приемки' : 'На складе')
                            ->badge()
                            ->color(fn (Product $record) => $record->isForReceipt() ? 'warning' : 'success'),
                    ])
                    ->columns(2),

                InfoSection::make('Информация о товаре')
                    ->schema([
                        TextEntry::make('quantity')->label('Количество'),
                        TextEntry::make('calculated_volume')
                            ->label('Объем')
                            ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format($state, 3, '.', ' ') : '0.000'),
                        TextEntry::make('producer.name')->label('Производитель')->placeholder('—'),
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

                InfoSection::make('Документы')
                    ->schema([
                        ViewEntry::make('document_path')
                            ->label('Файлы')
                            ->view('filament.infolists.components.document-links')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Product $record): bool => ! empty($record->document_path))
                    ->icon('heroicon-o-document'),

                InfoSection::make('Уточнения')
                    ->schema([
                        TextEntry::make('correction')
                            ->label('Текст уточнения')
                            ->formatStateUsing(function ($state, Product $record) {
                                if (! $record->hasCorrection()) {
                                    return 'Уточнения не внесены';
                                }

                                return $state;
                            })
                            ->badge()
                            ->color(fn (Product $record) => $record->hasCorrection() ? 'warning' : 'gray')
                            ->columnSpanFull(),
                        TextEntry::make('updated_at')
                            ->label('Дата последнего изменения')
                            ->formatStateUsing(function ($state, Product $record) {
                                if (! $record->hasCorrection()) {
                                    return '—';
                                }

                                return $record->updated_at?->format('d.m.Y H:i');
                            })
                            ->visible(fn (Product $record) => $record->hasCorrection()),
                    ])
                    ->visible(fn (Product $record) => $record->hasCorrection())
                    ->columns(2),
            ]);
    }
}
