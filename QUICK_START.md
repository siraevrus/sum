# 🚀 Быстрый старт CI/CD Pipeline

## ⚡ За 5 минут

### 1. Настройте секреты (2 минуты)
```bash
# Перейдите в GitHub → Settings → Secrets and variables → Actions
# Добавьте:
HOST=your-server-ip
USERNAME=your-ssh-user
SSH_KEY=your-private-ssh-key
PORT=22
```

### 2. Установите зависимости (1 минута)
```bash
composer install
npm install
```

### 3. Протестируйте локально (1 минута)
```bash
# Проверка качества кода
composer run quality

# Запуск тестов
composer run test
```

### 4. Запушите изменения (1 минута)
```bash
git add .
git commit -m "feat: setup CI/CD pipeline"
git push origin main
```

## 🎯 Что произойдет

1. **Автоматически запустятся тесты** при push
2. **Проверится качество кода** (Pint, PHPStan)
3. **Выполнится деплой** на сервер (если тесты прошли)
4. **Создастся бэкап** БД перед деплоем
5. **Придут уведомления** в Slack/Discord (если настроены)

## 📊 Мониторинг

- **GitHub Actions** → Просмотр всех workflows
- **Логи** → Детальная информация о выполнении
- **Артефакты** → Скачивание отчетов и бэкапов

## 🔧 Полезные команды

```bash
# Локальная проверка
composer run quality:fix  # Исправить стиль кода
composer run test         # Запустить тесты

# Ручной запуск workflows
# GitHub → Actions → Выберите workflow → Run workflow
```

## 🆘 Если что-то не работает

1. **Проверьте секреты** в GitHub Settings
2. **Посмотрите логи** в GitHub Actions
3. **Проверьте SSH подключение** к серверу
4. **Убедитесь в правах доступа** на сервере

## 📚 Документация

- [PIPELINE_GUIDE.md](PIPELINE_GUIDE.md) - Полное руководство
- [PIPELINE_OVERVIEW.md](PIPELINE_OVERVIEW.md) - Обзор всех workflows
- [.github/SECRETS_SETUP.md](.github/SECRETS_SETUP.md) - Настройка секретов

---

**Готово!** 🎉 Ваш CI/CD pipeline настроен и готов к работе.
