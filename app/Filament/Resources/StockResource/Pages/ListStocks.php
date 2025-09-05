<?php

namespace App\Filament\Resources\StockResource\Pages;

use App\Filament\Resources\StockResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStocks extends ListRecords
{
    protected static string $resource = StockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Убрана кнопка "Создать" для страницы остатков
        ];
    }

    /**
     * Получить ключ записи для таблицы
     */
    public function getTableRecordKey($record): string
    {
        // Используем сгенерированный ID из запроса
        if (is_object($record) && isset($record->id)) {
            return (string) $record->id;
        }

        if (is_array($record) && isset($record['id'])) {
            return (string) $record['id'];
        }

        // Fallback - всегда возвращаем строку
        return md5(serialize($record) ?: 'empty_record');
    }

} 