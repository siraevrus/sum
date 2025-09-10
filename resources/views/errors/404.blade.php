<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница не найдена</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
        <div class="mb-6">
            <div class="text-6xl text-gray-400 mb-4">404</div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Страница не найдена</h1>
            <p class="text-gray-600">Запрашиваемая страница не существует или была перемещена.</p>
        </div>
        
        <div class="space-y-4">
            <a href="{{ url('/') }}" 
               class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                На главную страницу
            </a>
        </div>
    </div>
</body>
</html>
