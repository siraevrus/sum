@php
    $state = $getState();
    
    // Если пустое значение
    if (empty($state)) {
        echo '—';
        return;
    }

    // Если это строка, преобразуем в массив
    if (is_string($state)) {
        $state = [$state];
    }

    // Если это не массив, возвращаем прочерк
    if (!is_array($state)) {
        echo '—';
        return;
    }

    $links = [];
    foreach ($state as $document) {
        if (!empty($document)) {
            $fileName = basename($document);
            $fileUrl = asset('storage/' . $document);
            $links[] = $fileUrl;
        }
    }
@endphp

@if(empty($links))
    —
@else
    <div class="space-y-2">
        @foreach($links as $index => $fileUrl)
            @php
                $document = $state[$index];
                $fileName = basename($document);
            @endphp
            <div>
                <a href="{{ $fileUrl }}" 
                   target="_blank" 
                   class="inline-flex items-center text-primary-600 hover:text-primary-800 underline font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    {{ $fileName }}
                </a>
            </div>
        @endforeach
    </div>
@endif
