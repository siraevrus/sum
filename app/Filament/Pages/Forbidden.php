<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Forbidden extends Page
{
    protected static ?string $navigationIcon = null;

    protected static ?string $navigationLabel = null;

    protected static ?string $title = 'Доступ запрещён';

    protected static ?int $navigationSort = null;

    protected static string $view = 'filament.pages.forbidden';

    public ?string $message = null;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->message = (string) request()->query('message', 'У вас нет прав для выполнения этого действия.');
    }
}


