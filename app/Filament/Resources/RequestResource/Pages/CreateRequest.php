<?php

namespace App\Filament\Resources\RequestResource\Pages;

use App\Filament\Resources\RequestResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateRequest extends CreateRecord
{
    protected static string $resource = RequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['status'] = 'pending'; // Все новые запросы начинаются со статуса "ожидает рассмотрения"
        // Поле описание может быть не заполнено, БД ожидает ненулевое значение
        if (! array_key_exists('description', $data) || $data['description'] === null) {
            $data['description'] = '';
        }

        // Устанавливаем пустые атрибуты, так как характеристики больше не используются
        $data['attributes'] = [];
        $data['calculated_volume'] = null;

        // Для не-админов проставляем склад пользователя независимо от скрытого поля
        $user = Auth::user();
        if ($user && (! method_exists($user, 'isAdmin') || ! $user->isAdmin())) {
            $data['warehouse_id'] = $user->warehouse_id;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
