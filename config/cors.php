<?php

return [
    // Пути, для которых применяется CORS (включаем API и Sanctum cookie)
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Разрешенные HTTP-методы (включая OPTIONS для preflight)
    'allowed_methods' => ['*'],

    // Разрешенные источники (по умолчанию все; при необходимости сузьте доменами)
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    // Разрешенные заголовки (включая Authorization)
    'allowed_headers' => ['*'],

    // Заголовки, которые можно «экспортировать» на клиент
    'exposed_headers' => [],

    // Время кеширования preflight-ответа (в секундах)
    'max_age' => 0,

    // Если используете cookie/сессии с кросс-доменными запросами, включите true
    'supports_credentials' => false,
];
