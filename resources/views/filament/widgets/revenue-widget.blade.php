<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Выручка
        </x-slot>
        
        <x-slot name="headerEnd">
            <div class="flex items-center gap-3">
                <!-- Фильтр периодов -->
                <select 
                    wire:model.live="period" 
                    class="text-sm border-gray-300 rounded-md focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="day">День</option>
                    <option value="week">Неделя</option>
                    <option value="month">Месяц</option>
                    <option value="custom">Период</option>
                </select>
                
                <!-- Кастомные даты (показываются только для периода "custom") -->
                @if($period === 'custom')
                    <input 
                        type="date" 
                        wire:model.live="dateFrom"
                        placeholder="От"
                        class="text-sm border-gray-300 rounded-md focus:border-primary-500 focus:ring-primary-500"
                    />
                    <input 
                        type="date" 
                        wire:model.live="dateTo"
                        placeholder="До"
                        class="text-sm border-gray-300 rounded-md focus:border-primary-500 focus:ring-primary-500"
                    />
                @endif
            </div>
        </x-slot>

               <div class="space-y-4">
                   <!-- Таблица выручки -->
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                USD
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                RUB
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                UZS
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $revenueData['USD']['formatted'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $revenueData['RUB']['formatted'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $revenueData['UZS']['formatted'] }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Сообщение при отсутствии продаж -->
            @if(array_sum(array_column($revenueData, 'amount')) == 0)
                <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                    Нет продаж за выбранный период
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
