<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Гарантируем заполнение поля name (не nullable в БД)
        $fullName = trim(implode(' ', array_filter([
            $data['last_name'] ?? null,
            $data['first_name'] ?? null,
            $data['middle_name'] ?? null,
        ])));

        if (empty($data['name'])) {
            $data['name'] = $fullName !== ''
                ? $fullName
                : ($data['username'] ?? ($data['email'] ?? ''));
        }

        return $data;
    }
}
