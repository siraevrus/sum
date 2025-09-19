# 🚀 CI/CD Pipeline Guide

## Обзор

Настроен полноценный CI/CD pipeline для Laravel приложения с автоматическим тестированием, проверкой качества кода и деплоем на сервер.

## 📋 Что настроено

### ✅ GitHub Actions Workflows

1. **CI Pipeline** - Основной workflow для тестирования
2. **Code Quality** - Проверка качества кода
3. **API Tests** - Специализированные тесты API
4. **Deploy** - Автоматический деплой на сервер
5. **Auto Format** - Автоматическое форматирование кода
6. **Release** - Создание релизов

### ✅ Инструменты качества кода

- **Laravel Pint** - Форматирование PHP кода
- **PHPStan** - Статический анализ кода
- **Composer Audit** - Проверка уязвимостей
- **PHPUnit** - Тестирование

## 🛠️ Локальная разработка

### Установка зависимостей

```bash
# PHP зависимости
composer install

# Node.js зависимости
npm install

# Установка PHPStan (если еще не установлен)
composer require --dev phpstan/phpstan larastan/larastan
```

### Запуск проверок

```bash
# Все проверки качества кода
composer run quality

# Автоматическое исправление стиля кода
composer run quality:fix

# Запуск тестов
composer run test

# Только форматирование кода
vendor/bin/pint

# Только статический анализ
vendor/bin/phpstan analyse
```

## 🔧 Настройка сервера

### 1. GitHub Secrets

Добавьте в настройки репозитория (Settings → Secrets and variables → Actions):

- `HOST` - IP адрес сервера
- `USERNAME` - SSH пользователь
- `SSH_KEY` - Приватный SSH ключ
- `PORT` - SSH порт (по умолчанию 22)

### 2. Сервер

Убедитесь, что на сервере установлены:

```bash
# PHP 8.4+
php -v

# Composer
composer --version

# Node.js 20+
node -v

# Nginx
nginx -v

# Создайте папку для бэкапов
sudo mkdir -p /var/backups/sklad
```

### 3. Права доступа

```bash
# Установите правильные права
sudo chown -R www-data:www-data /var/www/sklad
sudo chmod -R 755 /var/www/sklad
sudo chmod -R 775 /var/www/sklad/storage
sudo chmod -R 775 /var/www/sklad/bootstrap/cache
```

## 📊 Workflow Triggers

| Workflow | Trigger | Описание |
|----------|---------|----------|
| CI Pipeline | Push в main/develop, PR в main | Тестирование и проверки |
| Code Quality | Push в main/develop, PR в main | Проверка качества кода |
| API Tests | Push в main/develop, PR в main, ежедневно | Тестирование API |
| Deploy | Push в main, ручной запуск | Деплой на сервер |
| Auto Format | PR в main (PHP файлы) | Форматирование кода |
| Release | Push тега v*, ручной запуск | Создание релиза |

## 🚀 Процесс деплоя

### Автоматический деплой

1. Push в ветку `main`
2. Автоматически запускается CI Pipeline
3. После успешных тестов запускается Deploy
4. Создается бэкап
5. Обновляется код
6. Выполняются миграции
7. Перезапускаются сервисы
8. Проверяется работоспособность

### Ручной деплой

1. Перейдите в Actions → Deploy to Production Server
2. Нажмите "Run workflow"
3. Выберите ветку и нажмите "Run workflow"

## 📈 Мониторинг

### GitHub Actions

- Перейдите в раздел "Actions" в репозитории
- Просматривайте статус всех workflows
- Проверяйте логи при ошибках

### Логи на сервере

```bash
# Логи Nginx
sudo tail -f /var/log/nginx/error.log

# Логи PHP-FPM
sudo tail -f /var/log/php8.4-fpm.log

# Логи приложения
tail -f /var/www/sklad/storage/logs/laravel.log
```

## 🔍 Отладка

### Проблемы с тестами

```bash
# Запуск тестов локально
php artisan test

# Запуск конкретного теста
php artisan test --filter=TestName

# С отладочной информацией
php artisan test --verbose
```

### Проблемы с деплоем

1. Проверьте GitHub Secrets
2. Проверьте SSH подключение
3. Проверьте права доступа на сервере
4. Проверьте логи в GitHub Actions

### Проблемы с качеством кода

```bash
# Проверка стиля кода
vendor/bin/pint --test

# Автоматическое исправление
vendor/bin/pint

# Статический анализ
vendor/bin/phpstan analyse
```

## 📚 Полезные команды

```bash
# Очистка кэша
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Оптимизация для продакшена
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Проверка конфигурации
php artisan config:show

# Список маршрутов
php artisan route:list
```

## 🎯 Следующие шаги

1. Настройте GitHub Secrets
2. Протестируйте pipeline на тестовой ветке
3. Настройте уведомления (Slack, Discord, Email)
4. Добавьте дополнительные тесты
5. Настройте мониторинг производительности

---

**Готово!** 🎉 Ваш CI/CD pipeline настроен и готов к использованию.
