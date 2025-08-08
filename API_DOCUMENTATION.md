# API Документация

## Базовый URL
```
http://localhost:8000/api
```

## Аутентификация

### Регистрация
POST `/auth/register`

Параметры:
```json
{
  "name": "Иван Иванов",
  "email": "ivan@example.com",
  "password": "password123"
}
```

Ответ:
```json
{
  "user": {
    "id": 1,
    "name": "Иван Иванов",
    "email": "ivan@example.com",
    "role": "admin"
  },
  "token": "1|abc123..."
}
```

Примечания:
- Валидация: 422 с ошибками полей.

### Вход
POST `/auth/login`

Параметры:
```json
{
  "email": "ivan@example.com",
  "password": "password123"
}
```

Ответ (успех):
```json
{
  "user": { "id": 1, "name": "Иван Иванов", "email": "ivan@example.com", "role": "admin" },
  "token": "1|abc123..."
}
```

Ответ (ошибка):
```json
{ "message": "Неверные учетные данные" } // 401
{ "message": "Ваш аккаунт заблокирован" } // 401
```

### Выход
POST `/auth/logout`

Заголовки: `Authorization: Bearer {token}`

Ответ:
```json
{ "message": "Успешно вышли из системы" }
```

### Профиль
GET `/auth/me`

Заголовки: `Authorization: Bearer {token}`

Ответ: объект пользователя (без обертки):
```json
{ "id": 1, "name": "Иван Иванов", "email": "ivan@example.com", "role": "admin" }
```

### Обновление профиля
PUT `/auth/profile`

Заголовки: `Authorization: Bearer {token}`

Тело (любые из полей): `name`, `username`, `email`, `password` (+подтверждение)

Ответ:
```json
{ "message": "Профиль обновлен", "user": { "id": 1, "name": "..." } }
```

## Товары

### Получение списка товаров
GET `/products`

**Параметры запроса:**
- `search` - поиск по названию, описанию, производителю
- `warehouse_id` - фильтр по складу
- `template_id` - фильтр по шаблону
- `producer` - фильтр по производителю
- `in_stock` - только с остатками
- `low_stock` - заканчивающиеся товары (≤10)
- `active` - только активные
- `per_page` - количество на странице (по умолчанию 15)
- `page` - номер страницы

Заголовки: `Authorization: Bearer {token}`

Ответ:
```json
{
    "data": [
        {
            "id": 1,
            "name": "Доска обрезная 6м",
            "description": "Обрезная доска 6 метров длиной",
            "attributes": {
                "length": 6.0,
                "width": 15.0,
                "height": 25.0,
                "grade": "A"
            },
            "quantity": 50,
            "calculated_volume": 0.0225,
            "producer": "ООО Лесопилка",
            "arrival_date": "2024-01-15",
            "is_active": true,
            "template": {
                "id": 1,
                "name": "Доска обрезная"
            },
            "warehouse": {
                "id": 1,
                "name": "Основной склад"
            },
            "creator": {
                "id": 1,
                "name": "Администратор"
            }
        }
    ],
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1
    }
}
```

### Получение товара по ID
GET `/products/{id}`

Заголовки: `Authorization: Bearer {token}`

Ответ: объект товара. Ошибки: 404 `{ "message": "Товар не найден" }`, 403 `{ "message": "Доступ запрещен" }`.

### Создание товара
POST `/products`

Заголовки: `Authorization: Bearer {token}`

Параметры:
```json
{
    "product_template_id": 1,
    "warehouse_id": 1,
    "name": "Доска обрезная 4м",
    "description": "Обрезная доска 4 метра",
    "attributes": {
        "length": 4.0,
        "width": 15.0,
        "height": 25.0,
        "grade": "B"
    },
    "quantity": 30,
    "producer": "ООО Лесопилка",
    "arrival_date": "2024-01-20",
    "is_active": true
}
```

Ответ:
```json
{ "message": "Товар успешно создан", "data": { "id": 1, "name": "..." } }
```

### Обновление товара
PUT `/products/{id}`

Заголовки: `Authorization: Bearer {token}`

Ответ: `{ "message": "Товар успешно обновлен", "data": { ... } }`

### Удаление товара
DELETE `/products/{id}`

Заголовки: `Authorization: Bearer {token}`

Ответ: `{ "message": "Товар успешно удален" }`

### Статистика товаров
GET `/products/stats`

Заголовки: `Authorization: Bearer {token}`

Ответ:
```json
{
  "success": true,
  "data": {
    "total_products": 10,
    "active_products": 8,
    "in_stock": 7,
    "low_stock": 2,
    "out_of_stock": 1,
    "total_quantity": 150,
    "total_volume": 3.45
  }
}
```

### Популярные товары
GET `/products/popular`

Заголовки: `Authorization: Bearer {token}`

Ответ:
```json
{ "success": true, "data": [ { "id": 1, "name": "...", "total_sales": 12, "total_revenue": "10000.00" } ] }
```

## Продажи

### Получение списка продаж
GET `/sales`

**Параметры запроса:**
- `search` - поиск по номеру продажи, клиенту, телефону
- `warehouse_id` - фильтр по складу
- `payment_status` - статус оплаты
- `delivery_status` - статус доставки
- `payment_method` - способ оплаты
- `date_from` - дата с
- `date_to` - дата по
- `active` - только активные
- `per_page` - количество на странице
- `page` - номер страницы

Заголовки: `Authorization: Bearer {token}`

Ответ:
```json
{
    "data": [
        {
            "id": 1,
            "sale_number": "SALE-202401-0001",
            "customer_name": "Иванов Иван",
            "customer_phone": "+7 (495) 123-45-67",
            "quantity": 5,
            "unit_price": 2500.00,
            "total_price": 15000.00,
            "payment_status": "paid",
            "delivery_status": "delivered",
            "sale_date": "2024-01-15",
            "product": {
                "id": 1,
                "name": "Доска обрезная 6м"
            },
            "warehouse": {
                "id": 1,
                "name": "Основной склад"
            },
            "user": {
                "id": 1,
                "name": "Оператор"
            }
        }
    ],
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1
    }
}
```

### Получение продажи по ID
GET `/sales/{id}`

Заголовки: `Authorization: Bearer {token}`

Ответ: объект продажи. Ошибки: 404 `{ "message": "Продажа не найдена" }`.

### Создание продажи
POST `/sales`

Заголовки: `Authorization: Bearer {token}`

Параметры:
```json
{
    "product_id": 1,
    "warehouse_id": 1,
    "customer_name": "Петров Петр",
    "customer_phone": "+7 (495) 987-65-43",
    "customer_email": "petrov@example.com",
    "customer_address": "г. Москва, ул. Примерная, д. 1",
    "quantity": 3,
    "unit_price": 2500.00,
    "vat_rate": 20.00,
    "payment_method": "card",
    "payment_status": "pending",
    "delivery_status": "pending",
    "notes": "Доставка на строительную площадку",
    "sale_date": "2024-01-20",
    "is_active": true
}
```

Ответ: `{ "message": "Продажа успешно создана", "sale": { ... } }`

### Обновление продажи
PUT `/sales/{id}`

Заголовки: `Authorization: Bearer {token}`

Ответ: `{ "message": "Продажа успешно обновлена", "sale": { ... } }`

### Удаление продажи
DELETE `/sales/{id}`

Заголовки: `Authorization: Bearer {token}`

Ответ: `{ "message": "Продажа удалена" }`

### Оформление продажи
POST `/sales/{id}/process`

Заголовки: `Authorization: Bearer {token}`

Ответ: `{ "message": "Продажа оформлена", "sale": { ... } }`

### Отмена продажи
POST `/sales/{id}/cancel`

Заголовки: `Authorization: Bearer {token}`

Ответ: `{ "message": "Продажа отменена", "sale": { ... } }`

### Статистика продаж
GET `/sales/stats`

Заголовки: `Authorization: Bearer {token}`

Ответ (объект без обертки):
```json
{
  "total_sales": 25,
  "paid_sales": 20,
  "pending_payments": 3,
  "today_sales": 2,
  "month_revenue": 150000.00,
  "total_revenue": 500000.00,
  "total_quantity": 150,
  "average_sale": 12000.00,
  "in_delivery": 4
}
```

## Коды ошибок

- `400` - Неверные данные
- `401` - Не авторизован
- `403` - Доступ запрещен
- `404` - Не найдено
- `422` - Ошибка валидации
- `500` - Внутренняя ошибка сервера

## Примеры использования

### Получение токена
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@sklad.ru",
    "password": "password"
  }'
```

### Получение товаров
```bash
curl -X GET http://localhost:8000/api/products \
  -H "Authorization: Bearer {token}"
```

### Создание продажи
```bash
curl -X POST http://localhost:8000/api/sales \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "warehouse_id": 1,
    "customer_name": "Клиент",
    "quantity": 2,
    "unit_price": 1000.00,
    "payment_method": "cash",
    "sale_date": "2024-01-20"
  }'
``` 