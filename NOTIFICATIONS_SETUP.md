# 🔔 Настройка уведомлений

## Проблема
У вас возникла ошибка: `Error: Specify secrets.SLACK_WEBHOOK_URL`

## ✅ Решение

### 1. Исправлено в коде
- ✅ Добавлены проверки на наличие секретов
- ✅ Уведомления отправляются только при наличии секретов
- ✅ Добавлено логирование статуса уведомлений

### 2. Настройка Slack уведомлений

#### Шаг 1: Создайте Slack App
1. Перейдите в https://api.slack.com/apps
2. Нажмите "Create New App"
3. Выберите "From scratch"
4. Введите название и выберите workspace

#### Шаг 2: Настройте Incoming Webhooks
1. В настройках приложения → "Incoming Webhooks"
2. Включите "Activate Incoming Webhooks"
3. Нажмите "Add New Webhook to Workspace"
4. Выберите канал (например, #deployments)
5. Скопируйте Webhook URL

#### Шаг 3: Добавьте секрет в GitHub
1. GitHub → Settings → Secrets and variables → Actions
2. Нажмите "New repository secret"
3. Name: `SLACK_WEBHOOK_URL`
4. Value: `https://hooks.slack.com/services/YOUR/WEBHOOK/URL`

### 3. Настройка Discord уведомлений

#### Шаг 1: Создайте Webhook
1. Откройте Discord сервер
2. Настройки канала → Интеграции → Webhooks
3. Создайте новый webhook
4. Скопируйте Webhook URL

#### Шаг 2: Добавьте секрет в GitHub
1. GitHub → Settings → Secrets and variables → Actions
2. Нажмите "New repository secret"
3. Name: `DISCORD_WEBHOOK`
4. Value: `https://discord.com/api/webhooks/YOUR/WEBHOOK/URL`

### 4. Тестирование уведомлений

#### Ручной тест
1. GitHub → Actions → Test Notifications
2. Нажмите "Run workflow"
3. Выберите тип уведомления (success/failure/info)
4. Нажмите "Run workflow"

#### Автоматический тест
1. Сделайте push в main ветку
2. Проверьте уведомления в Slack/Discord
3. Или посмотрите логи в GitHub Actions

## 📊 Статус уведомлений

### Текущий статус
- ❌ Slack: Не настроен
- ❌ Discord: Не настроен
- ✅ Логирование: Работает

### После настройки
- ✅ Slack: Будет отправлять уведомления
- ✅ Discord: Будет отправлять уведомления
- ✅ Логирование: Продолжит работать

## 🔧 Альтернативные варианты

### 1. Только логирование (текущее состояние)
- ✅ Работает без настройки
- ✅ Показывает статус в GitHub Actions
- ❌ Нет уведомлений в мессенджерах

### 2. Email уведомления
Можно добавить email уведомления через GitHub Actions:
```yaml
- name: Send Email
  uses: dawidd6/action-send-mail@v3
  with:
    server_address: smtp.gmail.com
    server_port: 465
    username: ${{ secrets.EMAIL_USERNAME }}
    password: ${{ secrets.EMAIL_PASSWORD }}
    subject: "Deployment Status"
    body: "Workflow completed with status: ${{ job.status }}"
    to: your-email@example.com
```

### 3. Telegram уведомления
Можно добавить Telegram бота:
```yaml
- name: Send Telegram
  uses: appleboy/telegram-action@master
  with:
    to: ${{ secrets.TELEGRAM_TO }}
    message: "Deployment completed!"
  env:
    TELEGRAM_TOKEN: ${{ secrets.TELEGRAM_TOKEN }}
```

## 🎯 Рекомендации

1. **Начните с Slack** - проще настроить
2. **Используйте отдельный канал** для уведомлений
3. **Тестируйте на тестовых ветках** сначала
4. **Настройте фильтры** в мессенджерах

## 📚 Полезные ссылки

- [Slack Incoming Webhooks](https://api.slack.com/messaging/webhooks)
- [Discord Webhooks](https://support.discord.com/hc/en-us/articles/228383668-Intro-to-Webhooks)
- [GitHub Secrets](https://docs.github.com/en/actions/security-guides/encrypted-secrets)

---

**После настройки секретов уведомления будут работать автоматически!** 🎉
