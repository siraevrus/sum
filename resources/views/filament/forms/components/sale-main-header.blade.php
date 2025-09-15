<div class="flex items-center justify-between">
    <h2 class="text-base font-semibold leading-6 text-gray-900">Основная информация</h2>
    <div class="text-sm text-gray-600">
        № {{ $get('sale_number') ?: '' }}
    </div>
    @php
        // Если номер еще не сгенерирован (первичный рендер), попросим Livewire дернуть дефолт
        if (! $get('sale_number')) {
            $set('sale_number', \App\Models\Sale::generateSaleNumber());
        }
    @endphp
</div>

