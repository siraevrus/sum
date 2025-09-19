<?php

namespace App\Filament\Resources\ProducerResource\Pages;

use App\Filament\Resources\ProducerResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProducer extends EditRecord
{
    protected static string $resource = ProducerResource::class;

    protected static ?string $title = 'Редактировать производителя';

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

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Производитель обновлен')
            ->body('Данные производителя успешно сохранены.');
    }
}
