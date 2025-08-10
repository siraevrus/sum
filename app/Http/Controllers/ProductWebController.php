<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductWebController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_template_id' => ['required', 'exists:product_templates,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'producer' => ['nullable', 'string', 'max:255'],
            'transport_number' => ['nullable', 'string', 'max:255'],
            'arrival_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);

        $attributes = [];
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'attribute_') && $value !== null) {
                $attributeName = str_replace('attribute_', '', $key);
                $attributes[$attributeName] = $value;
            }
        }

        $data = array_merge($validated, [
            'created_by' => Auth::id(),
            'is_active' => true,
            'attributes' => $attributes,
        ]);

        // Рассчитать объем, если у шаблона есть формула
        if (!empty($data['product_template_id']) && !empty($attributes)) {
            $template = ProductTemplate::find($data['product_template_id']);
            if ($template && $template->formula) {
                $attrsForFormula = $attributes;
                $attrsForFormula['quantity'] = $data['quantity'];
                $testResult = $template->testFormula($attrsForFormula);
                if ($testResult['success']) {
                    $data['calculated_volume'] = $testResult['result'];
                }
            }
        }

        Product::create($data);

        return redirect('/admin/products');
    }
}


