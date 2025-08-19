<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Табы для переключения -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex space-x-8 px-6" aria-label="Tabs">
                    <button 
                        x-data="{ active: true }"
                        @click="active = true; $dispatch('tab-changed', { tab: 'producers' })"
                        :class="{ 'border-blue-500 text-blue-600': active, 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': !active }"
                        class="border-b-2 py-4 px-1 text-sm font-medium transition-colors duration-200"
                        x-init="$watch('active', value => { if (value) $dispatch('tab-changed', { tab: 'producers' }) })"
                    >
                        По производителям
                    </button>
                    <button 
                        x-data="{ active: false }"
                        @click="active = true; $dispatch('tab-changed', { tab: 'warehouses' })"
                        :class="{ 'border-blue-500 text-blue-600': active, 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': !active }"
                        class="border-b-2 py-4 px-1 text-sm font-medium transition-colors duration-200"
                        x-init="$watch('active', value => { if (value) $dispatch('tab-changed', { tab: 'warehouses' }) })"
                    >
                        По складам
                    </button>
                </nav>
            </div>
        </div>

        <!-- Контент табов -->
        <div x-data="{ activeTab: 'producers' }" @tab-changed.window="activeTab = $event.detail.tab">
            
            <!-- Таб: По производителям -->
            <div x-show="activeTab === 'producers'" class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Остатки по производителям</h3>
                    </div>
                    <div class="p-6">
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
            </div>

            <!-- Таб: По складам -->
            <div x-show="activeTab === 'warehouses'" class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Остатки по складам</h3>
                    </div>
                    <div class="p-6">
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
            </div>

        </div>

    </div>
</x-filament-panels::page> 