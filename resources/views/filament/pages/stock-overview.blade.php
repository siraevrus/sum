<x-filament-panels::page>
    <div class="space-y-6" x-data="{
        activeTab: '{{ request()->get('tab', request()->get('company_id') ? 'warehouses' : 'producers') }}'
    }" x-init="
        // Синхронизация таба с URL
        const params = new URLSearchParams(window.location.search);
        if (params.get('tab')) { activeTab = params.get('tab'); }
        $watch('activeTab', (val) => {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', val);
            window.history.replaceState({}, '', url);
        });
        window.addEventListener('popstate', () => {
            const p = new URLSearchParams(window.location.search);
            if (p.get('tab')) { activeTab = p.get('tab'); }
        });
    ">
        @php
            $producerCount = count($this->getProducerStats());
            $warehouseCount = count($this->getWarehouseStats());
            $companyCount = count($this->getCompanyStats());
        @endphp
        <!-- Табы для переключения -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex space-x-8 px-6" aria-label="Tabs">
                    <button 
                        @click="activeTab = 'producers'; $dispatch('tab-changed', { tab: 'producers' })"
                        :class="{ 'border-green-500 text-green-600': activeTab === 'producers', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'producers' }"
                        class="border-b-2 py-4 px-1 text-sm font-medium transition-colors duration-200"
                    >
                        По производителям
                        <span class="ml-2 inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs text-gray-600 dark:text-gray-300">{{ $producerCount }}</span>
                    </button>
                    <button 
                        @click="activeTab = 'warehouses'; $dispatch('tab-changed', { tab: 'warehouses' })"
                        :class="{ 'border-green-500 text-green-600': activeTab === 'warehouses', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'warehouses' }"
                        class="border-b-2 py-4 px-1 text-sm font-medium transition-colors duration-200"
                    >
                        По складам
                        <span class="ml-2 inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs text-gray-600 dark:text-gray-300">{{ $warehouseCount }}</span>
                    </button>
                    <button 
                        @click="activeTab = 'companies'; $dispatch('tab-changed', { tab: 'companies' })"
                        :class="{ 'border-green-500 text-green-600': activeTab === 'companies', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'companies' }"
                        class="border-b-2 py-4 px-1 text-sm font-medium transition-colors duration-200"
                    >
                        Компании
                        <span class="ml-2 inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs text-gray-600 dark:text-gray-300">{{ $companyCount }}</span>
                    </button>
                </nav>
            </div>
        </div>

        <!-- Контент табов -->
        <div @tab-changed.window="activeTab = $event.detail.tab">
            
            <!-- Таб: По производителям -->
            <div x-show="activeTab === 'producers'" class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Остатки по производителям</h3>
                    </div>
                    <div class="p-6">
                        @if(count($this->getProducerStats()) > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($this->getProducerStats() as $producerId => $stats)
                                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">{{ $stats['name'] }}</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">
                                            Товаров: {{ $stats['total_products'] }}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">
                                            Общий объем: {{ number_format($stats['total_volume'], 3, '.', ' ') }} м³
                                        </p>
                                        <a href="{{ route('filament.admin.resources.stocks.index', ['tableFilters[producer_id][value]' => $producerId]) }}" 
                                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                            Просмотреть →
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <p class="text-gray-500 dark:text-gray-400">Нет данных о производителях</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Таб: По складам -->
            <div x-show="activeTab === 'warehouses'" class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        @if(request()->get('company_id'))
                            @php
                                $company = \App\Models\Company::find(request()->get('company_id'));
                            @endphp
                            @if($company)
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Склады компании "{{ $company->name }}"
                                </h3>
                            @else
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Остатки по складам</h3>
                            @endif
                        @else
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Остатки по складам</h3>
                        @endif
                    </div>
                    <div class="p-6">
                        @if(count($this->getWarehouseStats()) > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($this->getWarehouseStats() as $warehouseId => $stats)
                                    @php
                                        $warehouse = \App\Models\Warehouse::find($warehouseId);
                                    @endphp
                                    @if($warehouse)
                                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">{{ $warehouse->name }}</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                                Товаров: {{ $stats['total_products'] }}
                                            </p>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                                Общий объем: {{ number_format($stats['total_volume'], 3, '.', ' ') }} м³
                                            </p>
                                            <a href="{{ route('filament.admin.resources.stocks.index', ['tableFilters[warehouse_id][value]' => $warehouseId]) }}" 
                                               class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                                Просмотреть →
                                            </a>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <p class="text-gray-500 dark:text-gray-400">Нет данных о складах</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Таб: Компании -->
            <div x-show="activeTab === 'companies'" class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Остатки по компаниям</h3>
                    </div>
                    <div class="p-6">
                        @if(count($this->getCompanyStats()) > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($this->getCompanyStats() as $companyId => $stats)
                                    @php
                                        $company = \App\Models\Company::find($companyId);
                                    @endphp
                                    @if($company)
                                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">{{ $company->name }}</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                                Товаров: {{ $stats['total_products'] }}
                                            </p>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                                Общий объем: {{ number_format($stats['total_volume'], 3, '.', ' ') }} м³
                                            </p>
                                            <a href="{{ route('filament.admin.pages.stock-overview', ['company_id' => $companyId]) }}" 
                                               class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                                Просмотреть →
                                            </a>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <p class="text-gray-500 dark:text-gray-400">Нет данных о компаниях</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

    </div>
</x-filament-panels::page> 