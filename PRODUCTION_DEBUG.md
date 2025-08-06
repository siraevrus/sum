# Диагностика создания товаров в продакшене

## Проблема
Товары не создаются в продакшене, хотя в тестовой среде все работает.

## Команды для диагностики

### 1. Запустите диагностическую команду
```bash
php artisan debug:product-creation
```

### 2. Проверьте конкретного пользователя
```bash
php artisan debug:product-creation --user-id=1
```

### 3. Проверьте логи ошибок
```bash
tail -f storage/logs/laravel.log
```

### 4. Проверьте права доступа к файлам
```bash
ls -la storage/
ls -la bootstrap/cache/
```

## Возможные причины и решения

### 1. Проблемы с базой данных
- **Проверьте подключение к БД** в `.env`
- **Убедитесь, что все миграции выполнены**: `php artisan migrate:status`
- **Проверьте права доступа к БД**

### 2. Проблемы с правами доступа
- **Проверьте роль пользователя** в продакшене
- **Убедитесь, что пользователь связан с компанией**
- **Проверьте, что есть шаблоны товаров и склады**

### 3. Проблемы с валидацией
- **Проверьте, что все обязательные поля заполнены**
- **Убедитесь, что выбран правильный шаблон и склад**

### 4. Проблемы с сессией/аутентификацией
- **Проверьте, что пользователь аутентифицирован**
- **Очистите кэш**: `php artisan cache:clear`

## Пошаговая диагностика

### Шаг 1: Проверьте подключение к БД
```bash
php artisan tinker
>>> DB::connection()->getPdo()
>>> Product::count()
```

### Шаг 2: Проверьте пользователя
```bash
php artisan tinker
>>> $user = User::first()
>>> $user->role->value
>>> $user->company_id
```

### Шаг 3: Проверьте данные
```bash
php artisan tinker
>>> ProductTemplate::count()
>>> Warehouse::count()
>>> Company::count()
```

### Шаг 4: Попробуйте создать товар вручную
```bash
php artisan tinker
>>> $template = ProductTemplate::first()
>>> $warehouse = Warehouse::first()
>>> $user = User::first()
>>> $product = Product::create([
    'product_template_id' => $template->id,
    'warehouse_id' => $warehouse->id,
    'name' => 'Test Product',
    'quantity' => 1,
    'arrival_date' => now(),
    'is_active' => true,
    'attributes' => [],
    'created_by' => $user->id,
])
```

## Если товар создается вручную, но не через форму

### 1. Проверьте валидацию формы
- Убедитесь, что все обязательные поля заполнены
- Проверьте, что выбран правильный шаблон и склад

### 2. Проверьте права доступа к ресурсу
```bash
php artisan tinker
>>> $user = User::first()
>>> App\Filament\Resources\ProductResource::canViewAny()
```

### 3. Проверьте фильтрацию по компании
```bash
php artisan tinker
>>> $user = User::first()
>>> $query = App\Filament\Resources\ProductResource::getEloquentQuery()
>>> $products = $query->get()
>>> $products->count()
```

## Решение проблем

### Если товар не создается из-за валидации:
1. Проверьте, что все обязательные поля заполнены
2. Убедитесь, что выбран существующий шаблон и склад
3. Проверьте формат даты

### Если товар создается, но не отображается:
1. Проверьте роль пользователя
2. Убедитесь, что склад принадлежит компании пользователя
3. Проверьте фильтрацию в `getEloquentQuery()`

### Если есть ошибки в логах:
1. Проверьте права доступа к файлам
2. Убедитесь, что все зависимости установлены
3. Проверьте версию PHP и расширения

## Команды для исправления

```bash
# Очистка кэша
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Проверка миграций
php artisan migrate:status
php artisan migrate --force

# Проверка прав доступа
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
``` 