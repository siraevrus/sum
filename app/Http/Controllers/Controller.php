<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function jsonNotFound(string $message = 'Не найдено', int $status = 404)
    {
        return response()->json([
            'message' => $message,
        ], $status);
    }
}
