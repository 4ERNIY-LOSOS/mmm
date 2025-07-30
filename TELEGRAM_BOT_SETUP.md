# Настройка Telegram Bot с Webhook

Этот проект теперь использует **webhook** вместо polling для более эффективной работы с Telegram Bot API.

## 🚀 Быстрый старт

### 1. Настройка переменных окружения

Скопируйте `.env.example` в `.env` и заполните необходимые переменные:

```bash
cp .env.example .env
```

Основные переменные для бота:
- `TELEGRAM_BOT_TOKEN` - токен вашего бота от @BotFather
- `WEBHOOK_URL` - публичный URL вашего сервера (например, https://yourdomain.com)
- `TELEGRAM_WEBHOOK_SECRET` - секретный токен для дополнительной безопасности (опционально)
- `MINI_APP_BASE_URL` - URL для Mini App

### 2. Запуск проекта

```bash
# Запуск Docker контейнеров
docker-compose up -d

# Установка зависимостей PHP
docker-compose exec php composer install
```

### 3. Настройка webhook

```bash
# Установить webhook
docker-compose exec php php console setup-telegram-webhook-command set

# Установить команды бота
docker-compose exec php php console setup-telegram-webhook-command commands

# Проверить статус webhook
docker-compose exec php php console setup-telegram-webhook-command info

# Протестировать бота
docker-compose exec php php console setup-telegram-webhook-command test
```

## 📋 Доступные команды

### Управление webhook

```bash
# Установить webhook (по умолчанию)
php console setup-telegram-webhook-command set

# Удалить webhook
php console setup-telegram-webhook-command delete

# Получить информацию о webhook
php console setup-telegram-webhook-command info

# Установить команды бота
php console setup-telegram-webhook-command commands

# Протестировать бота
php console setup-telegram-webhook-command test

# Показать справку
php console setup-telegram-webhook-command
```

## 🛠️ Архитектура

### Основные компоненты:

1. **TelegramWebhookController** (`hleb/app/Controllers/TelegramWebhookController.php`)
   - Обрабатывает входящие webhook от Telegram
   - Маршрутизирует сообщения к соответствующим обработчикам
   - Поддерживает команды, callback query и платежи

2. **TelegramBotService** (`hleb/app/Services/TelegramBotService.php`)
   - Сервис для работы с Telegram Bot API
   - Методы для отправки сообщений, фото, документов
   - Управление webhook и командами бота
   - Обработка платежей

3. **SetupTelegramWebhookCommand** (`hleb/app/Commands/SetupTelegramWebhookCommand.php`)
   - Консольная команда для управления webhook
   - Установка и удаление webhook
   - Настройка команд бота

### Endpoint webhook:
```
POST /telegram/webhook
```

## 🤖 Поддерживаемые команды бота

- `/start` - Главное меню с кнопками
- `/help` - Справка по командам
- `/catalog` - Каталог товаров
- `/cart` - Корзина пользователя
- `/orders` - История заказов

## 🔧 Функции

### ✅ Реализовано:
- Webhook обработка входящих сообщений
- Inline клавиатура с кнопками
- Интеграция с Web App (Mini App)
- Обработка callback query
- Поддержка платежей (pre-checkout)
- Консольные команды для управления
- Логирование ошибок
- Валидация токена

### 🔄 В разработке:
- База данных для пользователей и заказов
- Каталог товаров
- Корзина покупок
- Система платежей
- Админ-панель

## 🚨 Требования

- PHP 8.2+
- Docker и Docker Compose
- SSL сертификат для webhook (HTTPS обязателен)
- Публичный домен для webhook

## 🔒 Безопасность

1. **Secret Token**: Используйте `TELEGRAM_WEBHOOK_SECRET` для дополнительной проверки запросов
2. **HTTPS**: Webhook работает только по HTTPS
3. **Валидация**: Все входящие данные валидируются
4. **Логирование**: Ошибки логируются для мониторинга

## 📝 Логи

Логи ошибок записываются в стандартный error_log PHP. В Docker окружении они доступны в:
```bash
docker-compose logs php
```

## 🐛 Отладка

1. **Проверить статус webhook:**
   ```bash
   php console setup-telegram-webhook-command info
   ```

2. **Протестировать бота:**
   ```bash
   php console setup-telegram-webhook-command test
   ```

3. **Проверить логи:**
   ```bash
   docker-compose logs php
   ```

4. **Проверить маршруты:**
   ```bash
   php console --routes-upd
   ```

## 📞 Поддержка

Если у вас возникли проблемы:
1. Проверьте правильность токена бота
2. Убедитесь, что домен доступен по HTTPS
3. Проверьте логи на наличие ошибок
4. Убедитесь, что webhook установлен корректно