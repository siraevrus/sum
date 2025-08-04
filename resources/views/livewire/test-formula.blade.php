<div>
    <div class="space-y-4">
        <div>
            <h3 class="text-lg font-medium">Тестирование формулы: {{ $template->name }}</h3>
            <p class="text-sm text-gray-600">Формула: {{ $template->formula ?: 'Не задана' }}</p>
        </div>

        @if($error)
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-800">{{ $error }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if($result)
            <div class="bg-green-50 border border-green-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-800">{{ $result }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="space-y-4">
            @foreach($template->formulaAttributes as $attribute)
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        {{ $attribute->name }}
                        @if($attribute->unit)
                            ({{ $attribute->unit }})
                        @endif
                    </label>
                    <input 
                        type="number" 
                        step="0.01"
                        wire:model="testValues.{{ $attribute->variable }}"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        placeholder="Введите значение"
                    >
                </div>
            @endforeach
        </div>

        <div class="flex justify-end">
            <button 
                wire:click="testFormula"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                Тестировать формулу
            </button>
        </div>
    </div>
</div>
