@if(!empty($documents))
    <div class="space-y-2">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
            📄 <strong>Прикрепленные документы:</strong>
        </p>
        <div class="space-y-1">
            @foreach($documents as $document)
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $document['index'] }}.</span>
                    <a href="{{ $document['url'] }}"
                       target="_blank"
                       class="text-sm text-primary-600 hover:text-primary-500 underline dark:text-primary-400 dark:hover:text-primary-300">
                       {{ $document['name'] }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endif
