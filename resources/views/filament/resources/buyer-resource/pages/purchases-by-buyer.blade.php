<x-filament-panels::page>
    <div class="mb-6">
        <h2 class="text-xl font-bold">Покупки клиента</h2>
        <div><b>Имя:</b> {{ $customer_name }}</div>
        <div><b>Телефон:</b> {{ $phone }}</div>
    </div>
    <div>
        <h3 class="text-lg font-semibold mb-2">История покупок</h3>
        <table class="min-w-full border text-sm">
            <thead>
                <tr>
                    <th class="border px-2 py-1">Дата</th>
                    <th class="border px-2 py-1">Товар</th>
                    <th class="border px-2 py-1">Количество</th>
                    <th class="border px-2 py-1">Сумма</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                    <tr>
                        <td class="border px-2 py-1">{{ $purchase->sale_date }}</td>
                        <td class="border px-2 py-1">{{ $purchase->product_id }}</td>
                        <td class="border px-2 py-1">{{ $purchase->quantity }}</td>
                        <td class="border px-2 py-1">{{ $purchase->total_price }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="border px-2 py-1 text-center">Нет покупок</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
