# GitHub Secrets Setup

Для работы CI/CD pipeline необходимо настроить следующие секреты в GitHub:

## Обязательные секреты

### Для деплоя на сервер
1. `HOST` - IP адрес или домен вашего сервера
2. `USERNAME` - имя пользователя для SSH подключения
3. `SSH_KEY` - приватный SSH ключ для подключения к серверу
4. `PORT` - порт для SSH подключения (обычно 22)

### Для уведомлений (опционально)
5. `SLACK_WEBHOOK_URL` - Webhook URL для Slack уведомлений
6. `DISCORD_WEBHOOK` - Webhook URL для Discord уведомлений

### Для безопасности
7. `GITHUB_TOKEN` - Автоматически предоставляется GitHub

## Как добавить секреты

1. Перейдите в настройки репозитория: `Settings` → `Secrets and variables` → `Actions`
2. Нажмите `New repository secret`
3. Добавьте каждый секрет с соответствующим именем и значением

## Генерация SSH ключа

Если у вас нет SSH ключа:

```bash
# Сгенерируйте новый SSH ключ
ssh-keygen -t ed25519 -C "your-email@example.com"

# Скопируйте публичный ключ на сервер
ssh-copy-id -i ~/.ssh/id_ed25519.pub username@your-server.com

# Скопируйте приватный ключ в GitHub Secret SSH_KEY
cat ~/.ssh/id_ed25519
```

## Проверка подключения

Проверьте SSH подключение:

```bash
ssh -i ~/.ssh/id_ed25519 username@your-server.com
```

## Настройка сервера

Убедитесь, что на сервере:

1. Установлен PHP 8.4+
2. Установлен Composer
3. Установлен Node.js 20+
4. Установлен Nginx
5. Настроены права доступа для пользователя www-data
6. Создана папка для бэкапов: `sudo mkdir -p /var/backups/sklad`

## Структура папок на сервере

```
/var/www/sklad/          # Основная папка проекта
/var/backups/sklad/      # Папка для бэкапов
```

## Права доступа

```bash
# Установите правильные права
sudo chown -R www-data:www-data /var/www/sklad
sudo chmod -R 755 /var/www/sklad
sudo chmod -R 775 /var/www/sklad/storage
sudo chmod -R 775 /var/www/sklad/bootstrap/cache
```
