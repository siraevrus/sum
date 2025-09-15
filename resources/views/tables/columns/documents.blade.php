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
    <div>
        @foreach ($documents as $path)
            @php
                $safePath = ltrim($path, '/');
                $url = asset('storage/' . $safePath);
                $fullName = basename($safePath);
                $ext = pathinfo($fullName, PATHINFO_EXTENSION);
                $base = pathinfo($fullName, PATHINFO_FILENAME);
                $short = mb_substr($base, 0, 4);
                $display = $short . '...' . ($ext ? $ext : '');
            @endphp
            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:underline text-xs">{{ $display }}</a>@if (!$loop->last), @endif
        @endforeach
    </div>
@endif


