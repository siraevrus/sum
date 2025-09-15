<?php

namespace App\Filament\Resources\ProductInTransitResource\Pages;

use App\Filament\Resources\ProductInTransitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListProductInTransit extends ListRecords
{
    protected static string $resource = ProductInTransitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Добавить')
                ->visible(function (): bool {
                    $user = Auth::user();
                    if (! $user) {
                        return false;
                    }

                    return in_array($user->role->value, [
                        'admin',
                        'operator',
                        'warehouse_worker',
                        'sales_manager',
                    ], true);
                }),
        ];
    }
}
