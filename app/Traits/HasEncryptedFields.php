<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait HasEncryptedFields
{
    /**
     * Get encrypted field safely with error handling.
     */
    public function getEncryptedField(string $field): ?string
    {
        try {
            $value = $this->getAttribute($field);

            return $value;
        } catch (\Exception $e) {
            Log::warning('Failed to decrypt field', [
                'model' => static::class,
                'field' => $field,
                'id' => $this->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Set encrypted field safely.
     */
    public function setEncryptedField(string $field, ?string $value): bool
    {
        try {
            $this->setAttribute($field, $value);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to encrypt field', [
                'model' => static::class,
                'field' => $field,
                'id' => $this->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get masked version of encrypted field for display.
     */
    public function getMaskedField(string $field, int $visibleChars = 4): string
    {
        $value = $this->getEncryptedField($field);

        if (! $value) {
            return '****';
        }

        if (strlen($value) <= $visibleChars) {
            return str_repeat('*', strlen($value));
        }

        return substr($value, 0, $visibleChars).str_repeat('*', strlen($value) - $visibleChars);
    }

    /**
     * Check if encrypted field is accessible.
     */
    public function canAccessEncryptedField(string $field): bool
    {
        try {
            $this->getAttribute($field);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
