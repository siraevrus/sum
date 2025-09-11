<?php

namespace App\Traits;

trait SafeFilamentFormatting
{
    /**
     * Safely format state for Filament display with HTML escaping.
     */
    protected function safeFormatState($state, bool $allowEmpty = true): string
    {
        if ($state === null || $state === '') {
            return $allowEmpty ? '' : '—';
        }

        // Если это число, форматируем без экранирования
        if (is_numeric($state)) {
            return (string) $state;
        }

        // Экранируем HTML для строк
        return e((string) $state);
    }

    /**
     * Safely format numeric state with decimal places.
     */
    protected function safeFormatNumeric($state, int $decimals = 3, string $default = '0.000'): string
    {
        if (is_numeric($state)) {
            return number_format((float) $state, $decimals, '.', ' ');
        }

        return e($default);
    }

    /**
     * Safely format state with prefix/suffix.
     */
    protected function safeFormatWithIcon($state, string $icon = '', bool $condition = true): string
    {
        $safeState = $this->safeFormatState($state);

        if ($condition && $icon) {
            return $icon.' '.$safeState;
        }

        return $safeState;
    }

    /**
     * Safely format array state.
     */
    protected function safeFormatArray($state, string $separator = ', ', string $default = '—'): string
    {
        if (! is_array($state) || empty($state)) {
            return e($default);
        }

        $escapedItems = array_map(fn ($item) => e((string) $item), $state);

        return implode($separator, $escapedItems);
    }

    /**
     * Safely format boolean state.
     */
    protected function safeFormatBoolean($state, string $trueLabel = 'Да', string $falseLabel = 'Нет'): string
    {
        return e($state ? $trueLabel : $falseLabel);
    }
}
