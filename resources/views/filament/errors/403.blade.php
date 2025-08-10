@php($title = 'Доступ запрещён')

@php($message = $message ?? 'У вас нет прав для выполнения этого действия.')
@php(
    $url = function () {
        try {
            return route('filament.admin.pages.stock-overview');
        } catch (Throwable $e) {
            // Фолбэк: прямой путь на страницу остатков
            return url('/admin/stock-overview');
        }
    }()
)
@php($query = http_build_query(['message' => $message]))
@php($target = $url . (str_contains($url, '?') ? '&' : '?') . $query)

<script>
    window.location.replace(@json($target));
</script>


