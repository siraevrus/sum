# API Документация

## Базовый URL
`http://localhost:8000/api`

## Общие сведения
- Все защищенные эндпойнты требуют заголовок: `Authorization: Bearer {token}` (Laravel Sanctum)
- Тело запросов и ответы — JSON. Используйте заголовок: `Content-Type: application/json`
- Пагинация: списки возвращают поля `data` и `pagination` или `links`/`meta` в зависимости от ресурса (см. примеры ниже)

## Аутентификация

### Регистрация
POST `/auth/register`

Тело:
```json
{
  "name": "Иван Иванов",
  "email": "ivan@example.com",
  "password": "password123"
}
```

Ответ 201:
```json
{
  "user": { "id": 1, "name": "Иван Иванов", "email": "ivan@example.com", "role": "admin" },
  "token": "1|abc123..."
}
```

Примечание: 422 при ошибках валидации.

### Вход
POST `/auth/login`

Тело:
```json
{ "email": "ivan@example.com", "password": "password123" }
```

Ответ 200:
```json
{ "user": { "id": 1, "name": "Иван Иванов", "email": "ivan@example.com" }, "token": "1|abc123..." }
```

Ошибки: 401 `{"message":"Неверные учетные данные"}` или `{ "message": "Ваш аккаунт заблокирован" }`.

### Выход
POST `/auth/logout`

Ответ 200:
```json
{ "message": "Успешно вышли из системы" }
```

### Текущий пользователь
GET `/auth/me`

Ответ 200: объект пользователя без обертки.

### Обновление профиля
PUT `/auth/profile`

Тело: любые из полей `name`, `username`, `email`, `password` (+ `password_confirmation`), валидация уникальности для `username` и `email`.

Ответ 200:
```json
{ "message": "Профиль обновлен", "user": { "id": 1, "name": "..." } }
```

## Товары

### Список товаров
GET `/products`

Параметры: `search`, `warehouse_id`, `template_id`, `producer`, `in_stock`, `low_stock`, `active`, `per_page`, `page`.

Ответ 200:
```json
{
  "data": [ { "id": 1, "name": "...", "quantity": 50, "template": {"id":1}, "warehouse": {"id":1}, "creator": {"id":1} } ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1 }
}
```

### Товар по ID
GET `/products/{id}` — возвращает объект товара. Ошибки: 404, 403.

### Создать товар
POST `/products`

Тело (обязательные): `product_template_id`, `warehouse_id`, `name`, `quantity`.
Дополнительно: `description`, `attributes` (object), `producer`, `arrival_date`, `is_active`.

Ответ 201:
```json
{ "message": "Товар успешно создан", "data": { "id": 1, "name": "..." } }
```

### Обновить товар
PUT `/products/{id}` — частичное обновление, как валидация `store`.

Ответ 200: `{ "message": "Товар успешно обновлен", "data": { ... } }`

### Удалить товар
DELETE `/products/{id}` — `{ "message": "Товар успешно удален" }`

### Статистика товаров
GET `/products/stats`

Ответ 200:
```json
{ "success": true, "data": { "total_products": 10, "active_products": 8, "in_stock": 7, "low_stock": 2, "out_of_stock": 1, "total_quantity": 150, "total_volume": 3.45 } }
```

### Популярные товары
GET `/products/popular`

Ответ 200: `{ "success": true, "data": [ { "id": 1, "total_sales": 12, "total_revenue": "10000.00" } ] }`

### Экспорт товаров
GET `/products/export`

Те же параметры фильтрации, что и у списка. Ответ 200:
```json
{ "success": true, "data": [ { "id": 1, "name": "...", "quantity": 10, "calculated_volume": 0.22, "warehouse": "...", "template": "...", "arrival_date": "YYYY-MM-DD", "is_active": "Да" } ], "total": 1 }
```

## Продажи

### Список продаж
GET `/sales`

Параметры: `search`, `warehouse_id`, `payment_status`, `delivery_status`, `payment_method`, `date_from`, `date_to`, `active`, `per_page`, `page`.

Ответ 200:
```json
{ "data": [ { "id": 1, "sale_number": "...", "product": {"id":1}, "warehouse": {"id":1}, "user": {"id":1} } ], "links": { ... }, "meta": { ... } }
```

### Продажа по ID
GET `/sales/{id}` — объект продажи. Ошибки: 404, 403.

### Создать продажу
POST `/sales`

Обязательные: `product_id`, `warehouse_id`, `quantity`, `unit_price`, `payment_method`, `sale_date`.
Дополнительно: `customer_name`, `customer_phone`, `customer_email`, `customer_address`, `vat_rate`, `payment_status`, `delivery_status`, `notes`, `is_active`.

Ответ 201: `{ "message": "Продажа успешно создана", "sale": { ... } }`

Ошибки: 400 при недостаточном остатке, 403 при доступе к чужому складу.

### Обновить продажу
PUT `/sales/{id}` — частичное обновление. Пересчет сумм при изменении `quantity`/`unit_price`/`vat_rate`.

### Удалить продажу
DELETE `/sales/{id}` — `{ "message": "Продажа удалена" }`

### Оформить продажу
POST `/sales/{id}/process` — списывает товар, проверяет остаток. Успех: `{ "message": "Продажа оформлена", "sale": { ... } }`

### Отменить продажу
POST `/sales/{id}/cancel` — `{ "message": "Продажа отменена", "sale": { ... } }`

### Статистика продаж
GET `/sales/stats`

Ответ 200: объект без обертки с ключами `total_sales`, `paid_sales`, `pending_payments`, `today_sales`, `month_revenue`, `total_revenue`, `total_quantity`, `average_sale`, `in_delivery`.

### Экспорт продаж
GET `/sales/export`

Параметры, как у списка. Ответ 200: `{ "success": true, "data": [ ... ], "total": 123 }`

## Запросы (requests)

### Список запросов
GET `/requests`

Параметры: `status`, `priority`, `user_id`, `warehouse_id`, `product_template_id`, `sort`, `order`, `per_page`, `page`.

Ответ 200:
```json
{ "success": true, "data": [ { "id": 1, "status": "pending", "warehouse": {"id":1}, "product_template": {"id":1}, "user": {"id":1} } ], "pagination": { ... } }
```

### Запрос по ID
GET `/requests/{id}` — `{ "success": true, "data": { ... } }`

### Создать запрос
POST `/requests`

Обязательные: `warehouse_id`, `product_template_id`, `title`, `quantity`, `priority` (`low|normal|high|urgent`). Опционально: `description`, `status`.

Ответ 201: `{ "success": true, "message": "Запрос успешно создан", "data": { ... } }`

### Обновить запрос
PUT `/requests/{id}` — частичное обновление полей, включая `admin_notes`.

### Удалить запрос
DELETE `/requests/{id}` — `{ "success": true, "message": "Запрос успешно удален" }`

### Обработать запрос
POST `/requests/{id}/process` — устанавливает `status = completed`.

### Отклонить запрос
POST `/requests/{id}/reject` — устанавливает `status = rejected`.

### Статистика запросов
GET `/requests/stats` — `{ "success": true, "data": { "total": 10, "pending": 3, ... } }`

## Пользователи

### Список пользователей
GET `/users`

Параметры: `role`, `company_id`, `warehouse_id`, `is_blocked`, `search`, `sort`, `order`, `per_page`, `page`.

Ответ 200: `{ "success": true, "data": [ ... ], "pagination": { ... } }`

### Пользователь по ID
GET `/users/{id}` — `{ "success": true, "data": { ... } }`

### Создать пользователя
POST `/users`

Обязательные: `name`, `email` (уникальный), `password`, `role` (одно из `UserRole::cases()`), опционально `company_id`, `warehouse_id`, `phone`, `is_blocked`.

Ответ 201: `{ "success": true, "message": "Пользователь успешно создан", "data": { ... } }`

### Обновить пользователя
PUT `/users/{id}` — частичное обновление. При смене `password` — хеширование на сервере.

### Удалить пользователя
DELETE `/users/{id}` — нельзя удалить себя (400). Успех: `{ "success": true, "message": "Пользователь успешно удален" }`

### Заблокировать/Разблокировать
POST `/users/{id}/block` и `/users/{id}/unblock` — возвращают `{ "success": true, "message": "...", "data": { ... } }`

### Профиль текущего пользователя
GET `/users/profile` — `{ "success": true, "data": { ... } }`

### Обновление профиля текущего пользователя
PUT `/users/profile`

Поля: `name`, `username`, `email`, `phone`, а также `current_password` и `new_password` для смены пароля (проверяется текущий пароль). Ответ: `{ "success": true, "message": "Профиль успешно обновлен", "data": { ... } }`

### Статистика пользователей
GET `/users/stats` — `{ "success": true, "data": { "total": 10, "active": 9, "blocked": 1, "by_role": { ... } } }`

## Склады

### Список складов
GET `/warehouses`

Параметры: `company_id`, `is_active`, `search`, `sort`, `order`, `per_page`, `page`.

Ответ 200: `{ "success": true, "data": [ ... ], "pagination": { ... } }`

### Склад по ID
GET `/warehouses/{id}` — `{ "success": true, "data": { ... } }`

### Создать склад
POST `/warehouses` — обязательные: `name`, `address`, `company_id`. Ответ 201: `{ "success": true, "message": "Склад успешно создан", "data": { ... } }`

### Обновить склад
PUT `/warehouses/{id}` — частичное обновление.

### Удалить склад
DELETE `/warehouses/{id}` — 400 если есть товары или сотрудники. Иначе: `{ "success": true, "message": "Склад успешно удален" }`

### Активировать/Деактивировать
POST `/warehouses/{id}/activate` и `/warehouses/{id}/deactivate` — возвращают `{ "success": true, "message": "...", "data": { ... } }`

### Статистика склада
GET `/warehouses/{id}/stats` — `{ "success": true, "data": { "total_products": ..., ... } }`

### Товары склада
GET `/warehouses/{id}/products` — параметры: `is_active`, `product_template_id`, `search`, `sort`, `order`, `per_page`, `page`.

### Сотрудники склада
GET `/warehouses/{id}/employees` — параметры: `role`, `is_blocked`, `search`, `sort`, `order`, `per_page`, `page`.

### Статистика всех складов
GET `/warehouses/stats` — `{ "success": true, "data": { "total": 3, "active": 2, "inactive": 1 } }`

## Шаблоны товаров (product-templates)

### Список шаблонов
GET `/product-templates` — параметры: `is_active`, `search`, `sort`, `order`, `per_page`.

Ответ 200: `{ "success": true, "data": [ ... ], "pagination": { ... } }`

### Шаблон по ID
GET `/product-templates/{id}` — `{ "success": true, "data": { ... } }`

### Создать шаблон
POST `/product-templates` — обязательные: `name`, `unit`; опционально: `description`, `formula`, `is_active`.

Ответ 201: `{ "success": true, "message": "Шаблон товара успешно создан", "data": { ... } }`

### Обновить шаблон
PUT `/product-templates/{id}` — частичное обновление.

### Удалить шаблон
DELETE `/product-templates/{id}` — 400 если есть связанные товары. Иначе: `{ "success": true, "message": "Шаблон товара успешно удален" }`

### Активировать/Деактивировать шаблон
POST `/product-templates/{id}/activate` и `/product-templates/{id}/deactivate`

### Тест формулы
POST `/product-templates/{id}/test-formula`

Тело:
```json
{ "values": { "length": 6, "width": 15, "height": 25 } }
```
Ответ 200: `{ "success": true|false, "data": { ... } }`

### Характеристики шаблона
GET `/product-templates/{id}/attributes` — список атрибутов с сортировкой по `sort_order`.

POST `/product-templates/{id}/attributes` — создать атрибут. Поля: `name`, `variable` (a-zA-Z0-9_ с буквы), `type` (`number|text|select`), `value`, `unit`, `is_required`, `is_in_formula`, `sort_order`.

PUT `/product-templates/{id}/attributes/{attributeId}` — обновить атрибут.

DELETE `/product-templates/{id}/attributes/{attributeId}` — удалить атрибут.

### Товары по шаблону
GET `/product-templates/{id}/products` — параметры: `is_active`, `warehouse_id`, `search`, `sort`, `order`, `per_page`.

### Доступные единицы измерения
GET `/product-templates/units` — `{ "success": true, "data": [ "м3", "шт", ... ] }`

## Коды ошибок
- 400 — Неверные данные/логические ограничения
- 401 — Не авторизован
- 403 — Доступ запрещен
- 404 — Не найдено
- 422 — Ошибка валидации
- 500 — Внутренняя ошибка сервера

## Примеры

### Войти и получить токен
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sklad.ru","password":"password"}'
```

### Получить товары
```bash
curl -X GET "http://localhost:8000/api/products?in_stock=1&per_page=20" \
  -H "Authorization: Bearer {token}"
```

### Создать продажу
```bash
curl -X POST http://localhost:8000/api/sales \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"product_id":1,"warehouse_id":1,"quantity":2,"unit_price":1000,"payment_method":"cash","sale_date":"2024-01-20"}'
```