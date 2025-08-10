<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Статистика остатков -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Всего товаров</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $this->getTableQuery()->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">В наличии</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $this->getTableQuery()->where('quantity', '>', 0)->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 dark:bg-yellow-900/40 dark:text-yellow-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Мало остатков</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $this->getTableQuery()->where('quantity', '<=', 10)->where('quantity', '>', 0)->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Нет в наличии</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $this->getTableQuery()->where('quantity', '<=', 0)->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Вкладки -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                    <button
                        class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                        id="producer-tab"
                        onclick="showTab('producer')"
                    >
                        По производителям
                    </button>
                    <button
                        class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                        id="warehouse-tab"
                        onclick="showTab('warehouse')"
                    >
                        По складам
                    </button>
                    <button
                        class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                        id="attributes-tab"
                        onclick="showTab('attributes')"
                    >
                        По характеристикам
                    </button>
                </nav>
            </div>

            <!-- Содержимое вкладок -->
            <div class="p-6">
                <!-- Вкладка "По производителям" -->
                <div id="producer-content" class="tab-content">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Остатки по производителям</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($this->getProducers() as $producer)
                                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">{{ $producer }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        Товаров: {{ $this->getTableQuery()->where('producer', $producer)->count() }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        Общий объем: {{ number_format($this->getTableQuery()->where('producer', $producer)->sum('calculated_volume'), 2) }} м³
                                    </p>
                                    <a href="{{ route('filament.admin.resources.stocks.index', ['tableFilters[producer][value]' => $producer]) }}" 
                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                        Просмотреть →
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Вкладка "По складам" -->
                <div id="warehouse-content" class="tab-content hidden">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Остатки по складам</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($this->getWarehouses() as $warehouse)
                                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">{{ $warehouse->name }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        Товаров: {{ $this->getTableQuery()->where('warehouse_id', $warehouse->id)->count() }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        Общий объем: {{ number_format($this->getTableQuery()->where('warehouse_id', $warehouse->id)->sum('calculated_volume'), 2) }} м³
                                    </p>
                                    <a href="{{ route('filament.admin.resources.stocks.index', ['tableFilters[warehouse_id][value]' => $warehouse->id]) }}" 
                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                        Просмотреть →
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Вкладка "По характеристикам" -->
                <div id="attributes-content" class="tab-content hidden">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Остатки по характеристикам</h3>
                        <div class="space-y-6">
                            @php($summary = $this->getAttributeSummary())
                            @if(empty($summary))
                                <p class="text-sm text-gray-600 dark:text-gray-300">Характеристики отсутствуют.</p>
                            @else
                                @foreach($summary as $attributeName => $values)
                                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">{{ $attributeName }}</h4>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                                <thead class="bg-gray-100 dark:bg-gray-700">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Значение</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Позиций</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Количество</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Общий объём (м³)</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                    @foreach($values as $valueLabel => $data)
                                                        <tr>
                                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $valueLabel === '' ? '—' : $valueLabel }}</td>
                                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $data['items'] }}</td>
                                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ number_format($data['quantity'], 0, ',', ' ') }}</td>
                                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ number_format($data['total_volume'], 2, ',', ' ') }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Показаны топ-10 значений по количеству.</p>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Таблица остатков -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Все остатки</h3>
            </div>
            <div class="p-6">
                {{ $this->table }}
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Скрываем все содержимое вкладок
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            // Убираем активный класс со всех вкладок
            document.querySelectorAll('[id$="-tab"]').forEach(tab => {
                tab.classList.remove('border-blue-500', 'text-blue-600');
                tab.classList.add('border-transparent', 'text-gray-500');
            });

            // Показываем нужное содержимое
            document.getElementById(tabName + '-content').classList.remove('hidden');

            // Добавляем активный класс к нужной вкладке
            document.getElementById(tabName + '-tab').classList.remove('border-transparent', 'text-gray-500');
            document.getElementById(tabName + '-tab').classList.add('border-blue-500', 'text-blue-600');
        }

        // Показываем первую вкладку по умолчанию
        document.addEventListener('DOMContentLoaded', function() {
            showTab('producer');
        });
    </script>
</x-filament-panels::page> 