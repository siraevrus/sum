@php
    $value = $getState();

    $documents = [];
    if (is_array($value)) {
        $documents = $value;
    } elseif (is_string($value)) {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $documents = $decoded;
        } elseif (trim($value) !== '') {
            $documents = [$value];
        }
    }

    $documents = array_values(array_filter($documents, fn ($p) => is_string($p) && trim($p) !== ''));
@endphp

@if (empty($documents))
    —
@else
    <div class="space-y-1">
        @foreach ($documents as $path)
            @php
                $safePath = ltrim($path, '/');
                $url = asset('storage/' . $safePath);
                $name = basename($safePath);
            @endphp
            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:underline">
                {{ $name }}
            </a>
        @endforeach
    </div>
@endif


