<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('archive')
                ->label('Архивировать')
                ->icon('heroicon-o-archive-box')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Архивировать компанию?')
                ->modalDescription('Вы хотите архивировать компанию? Вместе с ней скроются все внесенные данные связанные с этой компанией.')
                ->modalSubmitActionLabel('Да, архивировать')
                ->modalCancelActionLabel('Отмена')
                ->visible(fn (): bool => ! $this->record->is_archived)
                ->action(function (): void {
                    $this->record->archive();
                    \Filament\Notifications\Notification::make()
                        ->title('Компания архивирована')
                        ->body('Компания успешно архивирована и скрыта из списка.')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Actions\Action::make('restore')
                ->label('Восстановить')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Восстановить компанию?')
                ->modalDescription('Вы хотите восстановить компанию из архива?')
                ->modalSubmitActionLabel('Да, восстановить')
                ->modalCancelActionLabel('Отмена')
                ->visible(fn (): bool => $this->record->is_archived)
                ->action(function (): void {
                    $this->record->restore();
                    \Filament\Notifications\Notification::make()
                        ->title('Компания восстановлена')
                        ->body('Компания успешно восстановлена из архива.')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Actions\DeleteAction::make()
                ->label('Удалить')
                ->requiresConfirmation()
                ->modalHeading('Удалить компанию?')
                ->modalDescription(function () {
                    if ($this->record->warehouses()->exists() || $this->record->employees()->exists()) {
                        return 'У компании есть связанные склады или сотрудники. Сначала удалите/перенесите их или архивируйте компанию.';
                    }

                    return 'Вы уверены, что хотите удалить эту компанию? Это действие нельзя отменить.';
                })
                ->modalSubmitActionLabel('Да, удалить')
                ->modalCancelActionLabel('Отмена')
                ->action(function () {
                    if ($this->record->warehouses()->exists() || $this->record->employees()->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->title('Нельзя удалить компанию')
                            ->body('Нельзя удалить компанию, у которой есть склады или сотрудники. Архивируйте компанию или удалите связанные записи.')
                            ->danger()
                            ->send();

                        return false;
                    }

                    return true;
                })
                ->visible(function () {
                    // Скрываем кнопку удаления если есть связанные записи
                    return ! ($this->record->warehouses()->exists() || $this->record->employees()->exists());
                }),
        ];
    }
}
