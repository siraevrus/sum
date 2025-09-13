<?php

namespace App\Filament\Resources\RequestResource\Pages;

use App\Filament\Resources\RequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRequest extends EditRecord
{
    protected static string $resource = RequestResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Поле описание может быть не заполнено, в БД колонка not null
        if (! array_key_exists('description', $data) || $data['description'] === null) {
            $data['description'] = '';
        }

        // Устанавливаем пустые атрибуты, так как характеристики больше не используются
        $data['attributes'] = [];
        $data['calculated_volume'] = null;

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Упрощенная логика - характеристики больше не используются
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Удалить'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
