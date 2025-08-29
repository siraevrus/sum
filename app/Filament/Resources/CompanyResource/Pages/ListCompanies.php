<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('archived')
                ->label('Архивированные компании')
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->url(route('filament.admin.resources.companies.index', ['tableFilters[is_archived][value]' => 'true'])),
        ];
    }
}
