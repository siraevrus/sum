@php($title = 'Доступ запрещён')

<x-filament-panels::page>
    <div class="max-w-3xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8">
            <div class="flex items-start space-x-4">
                <div class="shrink-0">
                    <div class="p-3 rounded-full bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-300">{{ $message ?? 'У вас нет прав для просмотра этой страницы.' }}</p>

                    <div class="mt-6 flex items-center gap-3">
                        <a href="{{ url()->previous() }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
                            Назад
                        </a>
                        <a href="{{ route('filament.admin.pages.dashboard') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-primary-600 text-white hover:bg-primary-700">
                            На главную
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>


