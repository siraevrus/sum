@php
    $title = 'Доступ запрещён';
    $message = $message ?? 'У вас нет прав для выполнения этого действия.';

    try {
        $url = route('filament.admin.pages.stock-overview');
    } catch (\Throwable $e) {
        // Фолбэк: прямой путь на страницу остатков
        $url = url('/admin/stock-overview');
    }

    $query = http_build_query(['message' => $message]);
    $target = $url . (str_contains($url, '?') ? '&' : '?') . $query;
@endphp

<script>
    window.location.replace(@json($target));
    </script>


