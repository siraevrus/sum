# 📚 API Документация - Система Склада

## 🚀 Обзор

API для системы управления складом построен на Laravel с использованием Laravel Sanctum для аутентификации. Поддерживает полный CRUD для всех основных сущностей системы.

## 🔐 Аутентификация

### Bearer Token
Все защищенные эндпоинты требуют Bearer токен в заголовке:
```
Authorization: Bearer {your-token}
```

### Получение токена
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

**Ответ:**
```json
{
  "user": {
    "id": 1,
    "name": "Иван Иванов",
    "email": "user@example.com",
    "role": "admin"
  },
  "token": "1|abc123..."
}
```

## 📦 Товары (Products)

### Получение списка товаров
```http
GET /api/products?page=1&per_page=20&search=название&has_correction=true
```

**Параметры запроса:**
- `page` - номер страницы (по умолчанию: 1)
- `per_page` - количество записей на странице (по умолчанию: 15, максимум: 200)
- `search` - поиск по названию, описанию или производителю
- `warehouse_id` - фильтр по складу
- `template_id` - фильтр по шаблону товара
- `producer` - фильтр по производителю
- `in_stock` - только товары в наличии
- `low_stock` - товары с низким остатком (≤10)
- `active` - только активные товары
- `has_correction` - только товары с уточнениями

**Ответ:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Кирпич красный М-150",
      "description": "Строительный кирпич",
      "quantity": 1000,
      "attributes": {
        "марка": "М-150",
        "цвет": "красный"
      },
      "transport_number": "А123БВ77",
      "producer": "ООО Кирпичный завод",
      "arrival_date": "2025-09-08",
      "is_active": true,
      "calculated_volume": 2.5,
      "correction": "В товаре ошибка с количеством - всего пришло 90",
      "correction_status": "correction",
      "document_path": ["documents/invoice_001.pdf", "documents/spec_001.pdf"],
      "template": {
        "id": 1
      },
      "warehouse": {
        "id": 1
      },
      "creator": {
        "id": 1
      }
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/products?page=1",
    "last": "http://localhost:8000/api/products?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/products?page=2"
  },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  }
}
```

### Создание товара
```http
POST /api/products
Content-Type: application/json

{
  "product_template_id": 1,
  "warehouse_id": 1,
  "quantity": 1000,
  "description": "Строительный кирпич",
  "attributes": {
    "марка": "М-150",
    "цвет": "красный"
  },
  "transport_number": "А123БВ77",
  "producer": "ООО Кирпичный завод",
  "arrival_date": "2025-09-08",
  "is_active": true
}
```

### Обновление товара
```http
PUT /api/products/{id}
Content-Type: application/json

{
  "quantity": 950,
  "description": "Обновленное описание"
}
```

### Удаление товара
```http
DELETE /api/products/{id}
```

## 🔧 Управление уточнениями

### Добавление уточнения к товару
```http
POST /api/products/{id}/correction
Content-Type: application/json

{
  "correction": "В товаре ошибка с количеством - всего пришло 90"
}
```

**Ответ:**
```json
{
  "success": true,
  "message": "Уточнение успешно добавлено к товару",
  "data": {
    "id": 1,
    "correction": "В товаре ошибка с количеством - всего пришло 90",
    "correction_status": "correction",
    "updated_at": "2025-09-09T09:54:41.000000Z"
  }
}
```

### Удаление уточнения
```http
DELETE /api/products/{id}/correction
```

**Ответ:**
```json
{
  "success": true,
  "message": "Уточнение успешно удалено",
  "data": {
    "id": 1,
    "correction": null,
    "correction_status": null,
    "updated_at": "2025-09-09T10:00:00.000000Z"
  }
}
```

## 📊 Статистика товаров
```http
GET /api/products/stats
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "total_products": 150,
    "active_products": 140,
    "in_stock": 120,
    "low_stock": 15,
    "out_of_stock": 5,
    "total_quantity": 50000,
    "total_volume": 1250.5
  }
}
```

## 🏪 Склады (Warehouses)

### Список складов
```http
GET /api/warehouses?company_id=1&is_active=true
```

### Создание склада
```http
POST /api/warehouses
Content-Type: application/json

{
  "name": "Основной склад",
  "address": "ул. Складская, 1",
  "company_id": 1
}
```

## 🏢 Компании (Companies)

### Список компаний
```http
GET /api/companies?is_active=true&search=название
```

### Архивирование компании
```http
POST /api/companies/{id}/archive
```

**Ответ:**
```json
{
  "success": true,
  "message": "Компания успешно архивирована",
  "data": {
    "id": 1,
    "name": "ООО Компания",
    "is_archived": true,
    "archived_at": "2025-09-09T10:00:00.000000Z"
  }
}
```

## 💰 Продажи (Sales)

### Создание продажи
```http
POST /api/sales
Content-Type: application/json

{
  "product_id": 1,
  "warehouse_id": 1,
  "customer_name": "Иван Иванов",
  "customer_phone": "+7 (999) 123-45-67",
  "customer_email": "ivan@example.com",
  "quantity": 5,
  "unit_price": 1000.00,
  "vat_rate": 20.00,
  "payment_method": "cash",
  "sale_date": "2025-09-09"
}
```

### Обработка продажи
```http
POST /api/sales/{id}/process
```

### Отмена продажи
```http
POST /api/sales/{id}/cancel
```

## 📋 Запросы (Requests)

### Создание запроса
```http
POST /api/requests
Content-Type: application/json

{
  "warehouse_id": 1,
  "product_template_id": 1,
  "title": "Запрос на пополнение кирпича",
  "quantity": 1000,
  "priority": "high",
  "description": "Необходимо пополнить склад"
}
```

## 👥 Пользователи (Users)

### Список пользователей
```http
GET /api/users?role=admin&company_id=1
```

### Блокировка пользователя
```http
POST /api/users/{id}/block
```

## 🏗️ Шаблоны товаров (Product Templates)

### Список шаблонов
```http
GET /api/product-templates?is_active=true
```

### Тестирование формулы
```http
POST /api/product-templates/{id}/test-formula
Content-Type: application/json

{
  "values": {
    "length": 10,
    "width": 5,
    "height": 2
  }
}
```

## 📈 Остатки (Stocks)

### Агрегированные остатки
```http
GET /api/stocks?warehouse_id=1&in_stock=true&low_stock=true
```

**Ответ:**
```json
{
  "data": [
    {
      "id": "template_1_warehouse_1",
      "product_template_id": 1,
      "warehouse_id": 1,
      "producer": "ООО Кирпичный завод",
      "name": "Кирпич красный М-150",
      "available_quantity": 950,
      "available_volume": 2.375,
      "items_count": 1,
      "first_arrival": "2025-09-08",
      "last_arrival": "2025-09-08",
      "template": {
        "id": 1,
        "name": "Кирпич"
      },
      "warehouse": {
        "id": 1,
        "name": "Основной склад"
      }
    }
  ]
}
```

## 🔍 Фильтрация и поиск

### Общие параметры фильтрации:
- `page` - номер страницы
- `per_page` - количество записей на странице
- `search` - текстовый поиск
- `sort` - поле для сортировки
- `order` - направление сортировки (asc/desc)

### Специфичные фильтры:
- **Товары**: `warehouse_id`, `template_id`, `producer`, `in_stock`, `low_stock`, `active`, `has_correction`
- **Продажи**: `warehouse_id`, `payment_status`, `delivery_status`, `payment_method`, `date_from`, `date_to`
- **Запросы**: `status`, `priority`, `user_id`, `warehouse_id`, `product_template_id`
- **Пользователи**: `role`, `company_id`, `warehouse_id`, `is_blocked`
- **Компании**: `is_active`, `is_archived`
- **Склады**: `company_id`, `is_active`

## ⚠️ Коды ошибок

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "This action is unauthorized."
}
```

### 404 Not Found
```json
{
  "message": "No query results for model [App\\Models\\Product] 999"
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### 500 Internal Server Error
```json
{
  "message": "Server Error"
}
```

## 📝 Особенности

### Роли пользователей:
- `admin` - полный доступ ко всем функциям
- `warehouse_worker` - доступ к складу и приемке
- `manager` - управление продажами и запросами

### Права доступа:
- Не-админы видят только данные своего склада
- Некоторые операции требуют специальных ролей
- Архивирование вместо удаления для критичных данных

### Новые возможности:
- ✅ Система уточнений для товаров
- ✅ Фильтрация товаров с коррекциями
- ✅ Управление документами
- ✅ Визуальное выделение проблемных товаров

## 🔗 Полезные ссылки

- **OpenAPI спецификация**: `/openapi.yaml` или `/openapi.json`
- **Swagger UI**: `/docs.html` (если настроен)
- **Postman коллекция**: `/postman.json`

## 📞 Поддержка

Для получения помощи по API обращайтесь к разработчикам системы.