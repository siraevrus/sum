@php($title = 'Доступ запрещён')

@php($message = $message ?? 'У вас нет прав для выполнения этого действия.')
@php($url = route('filament.admin.pages.dashboard'))
@php($query = http_build_query(['message' => $message]))
@php($target = $url . (str_contains($url, '?') ? '&' : '?') . $query)

<script>
    window.location.replace(@json($target));
</script>


