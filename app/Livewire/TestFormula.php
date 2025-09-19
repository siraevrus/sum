<?php

namespace App\Livewire;

use App\Models\ProductTemplate;
use Livewire\Component;

class TestFormula extends Component
{
    public ProductTemplate $template;

    public array $testValues = [];

    public ?string $result = null;

    public ?string $error = null;

    public function mount(ProductTemplate $template)
    {
        $this->template = $template;
        $this->initializeTestValues();
    }

    public function initializeTestValues()
    {
        $this->testValues = [];
        foreach ($this->template->formulaAttributes as $attribute) {
            $this->testValues[$attribute->variable] = '';
        }
    }

    public function testFormula()
    {
        $this->result = null;
        $this->error = null;

        // Проверяем, что все значения заполнены
        $emptyValues = array_filter($this->testValues, function ($value) {
            return empty($value);
        });

        if (! empty($emptyValues)) {
            $this->error = 'Пожалуйста, заполните все значения';

            return;
        }

        // Проверяем, что все значения числовые
        foreach ($this->testValues as $variable => $value) {
            if (! is_numeric($value)) {
                $this->error = "Значение для переменной '{$variable}' должно быть числом";

                return;
            }
        }

        // Тестируем формулу
        $testResult = $this->template->testFormula($this->testValues);

        if ($testResult['success']) {
            $this->result = "Результат: {$testResult['result']} {$this->template->unit}";
        } else {
            $this->error = $testResult['error'];
        }
    }

    public function render()
    {
        return view('livewire.test-formula');
    }
}
