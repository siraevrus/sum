<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'formula',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the attributes for the template.
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('sort_order');
    }

    /**
     * Get the formula attributes (used in formula).
     */
    public function formulaAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->where('is_in_formula', true)->orderBy('sort_order');
    }

    /**
     * Get the required attributes.
     */
    public function requiredAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->where('is_required', true)->orderBy('sort_order');
    }

    /**
     * Scope a query to only include active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Test the formula with sample data.
     */
    public function testFormula(array $values = []): array
    {
        if (!$this->formula) {
            return [
                'success' => false,
                'error' => 'Формула не задана',
                'result' => null
            ];
        }

        try {
            // Получаем переменные из формулы
            $variables = $this->extractVariablesFromFormula();
            
            // Проверяем, что все переменные есть в значениях
            $missingVariables = array_diff($variables, array_keys($values));
            if (!empty($missingVariables)) {
                return [
                    'success' => false,
                    'error' => 'Отсутствуют переменные: ' . implode(', ', $missingVariables),
                    'result' => null
                ];
            }

            // Заменяем переменные на значения
            $expression = $this->formula;
            foreach ($values as $variable => $value) {
                $expression = str_replace($variable, $value, $expression);
            }

            // Вычисляем результат
            $result = $this->evaluateExpression($expression);
            
            return [
                'success' => true,
                'error' => null,
                'result' => round($result, 2)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Ошибка вычисления: ' . $e->getMessage(),
                'result' => null
            ];
        }
    }

    /**
     * Extract variables from formula.
     */
    private function extractVariablesFromFormula(): array
    {
        if (!$this->formula) {
            return [];
        }

        // Ищем переменные в формуле (только английские буквы)
        preg_match_all('/[a-zA-Z_][a-zA-Z0-9_]*/', $this->formula, $matches);
        
        // Фильтруем только переменные (исключаем математические функции)
        $mathFunctions = ['sin', 'cos', 'tan', 'sqrt', 'pow', 'abs', 'round', 'floor', 'ceil'];
        $variables = array_filter($matches[0], function($match) use ($mathFunctions) {
            return !in_array(strtolower($match), $mathFunctions);
        });

        return array_unique($variables);
    }

    /**
     * Safely evaluate mathematical expression.
     */
    private function evaluateExpression(string $expression): float
    {
        // Удаляем все пробелы
        $expression = str_replace(' ', '', $expression);
        
        // Проверяем на безопасность (только математические операции)
        if (!preg_match('/^[0-9+\-*\/()\.]+$/', $expression)) {
            throw new \Exception('Выражение содержит недопустимые символы');
        }

        // Используем eval для вычисления (в продакшене лучше использовать библиотеку math.js)
        $result = eval("return $expression;");
        
        if (!is_numeric($result)) {
            throw new \Exception('Результат не является числом');
        }

        return (float) $result;
    }

    /**
     * Get available units for attributes.
     */
    public static function getAvailableUnits(): array
    {
        return [
            'мм' => 'мм',
            'см' => 'см',
            'метр' => 'метр',
            'радиус' => 'радиус',
            'м³' => 'м³',
            'м²' => 'м²',
            'кг' => 'кг',
            'грамм' => 'грамм',
        ];
    }
}
