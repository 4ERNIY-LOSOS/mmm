<?php

namespace App\Commands;

use Hleb\Base\Task;
use App\Services\TelegramBotService;

class SetupTelegramWebhookCommand extends Task
{
    /** php console setup-telegram-webhook-command [action] **/

    /**
     * Управление webhook для Telegram бота
     * Доступные действия: set, delete, info, commands, test
     * 
     * @param string|null $action - действие (set|delete|info|commands|test), по умолчанию 'set'
     * 
     * @return int - код выполнения команды
     */
    protected function run(?string $action = 'set'): int
    {
        $action = $action ?? 'set';
        
        try {
            $botService = new TelegramBotService();
            
            switch ($action) {
                case 'set':
                    return $this->setWebhook($botService);
                
                case 'delete':
                    return $this->deleteWebhook($botService);
                
                case 'info':
                    return $this->getWebhookInfo($botService);
                
                case 'commands':
                    return $this->setBotCommands($botService);
                
                case 'test':
                    return $this->testBot($botService);
                
                default:
                    $this->showHelp();
                    return self::ERROR_CODE;
            }
        } catch (\Exception $e) {
            $this->error("Ошибка: " . $e->getMessage());
            return self::ERROR_CODE;
        }
    }

    /**
     * Установка webhook
     */
    private function setWebhook(TelegramBotService $botService): int
    {
        $webhookUrl = $this->getWebhookUrl();
        
        if (!$webhookUrl) {
            $this->error("Не указан WEBHOOK_URL в переменных окружения");
            return self::ERROR_CODE;
        }

        $this->info("Установка webhook: " . $webhookUrl);

        $options = [
            'max_connections' => 40,
            'allowed_updates' => ['message', 'callback_query', 'pre_checkout_query'],
            'drop_pending_updates' => true,
        ];

        // Добавляем secret token если указан
        $secretToken = $_ENV['TELEGRAM_WEBHOOK_SECRET'] ?? null;
        if ($secretToken) {
            $options['secret_token'] = $secretToken;
            $this->info("Используется secret token для дополнительной безопасности");
        }

        $result = $botService->setWebhook($webhookUrl, $options);

        if ($result['ok']) {
            $this->success("✅ Webhook успешно установлен!");
            $this->info("URL: " . $webhookUrl);
            
            // Проверяем статус webhook
            $this->line("");
            $this->getWebhookInfo($botService);
            
            return self::SUCCESS_CODE;
        } else {
            $this->error("❌ Ошибка при установке webhook: " . ($result['description'] ?? 'Unknown error'));
            return self::ERROR_CODE;
        }
    }

    /**
     * Удаление webhook
     */
    private function deleteWebhook(TelegramBotService $botService): int
    {
        $this->info("Удаление webhook...");

        $result = $botService->deleteWebhook(true);

        if ($result['ok']) {
            $this->success("✅ Webhook успешно удален!");
            return self::SUCCESS_CODE;
        } else {
            $this->error("❌ Ошибка при удалении webhook: " . ($result['description'] ?? 'Unknown error'));
            return self::ERROR_CODE;
        }
    }

    /**
     * Получение информации о webhook
     */
    private function getWebhookInfo(TelegramBotService $botService): int
    {
        $this->info("Получение информации о webhook...");

        $result = $botService->getWebhookInfo();

        if ($result['ok']) {
            $info = $result['result'];
            
            $this->line("📊 Информация о webhook:");
            $this->line("URL: " . ($info['url'] ?: 'не установлен'));
            $this->line("Проверка SSL: " . ($info['has_custom_certificate'] ? 'да' : 'нет'));
            $this->line("Ожидающие обновления: " . $info['pending_update_count']);
            $this->line("IP адрес: " . ($info['ip_address'] ?? 'не определен'));
            $this->line("Максимум соединений: " . ($info['max_connections'] ?? 'не указано'));
            $this->line("Разрешенные обновления: " . json_encode($info['allowed_updates'] ?? []));
            
            if (isset($info['last_error_date'])) {
                $this->line("Последняя ошибка: " . date('Y-m-d H:i:s', $info['last_error_date']));
                $this->line("Сообщение ошибки: " . ($info['last_error_message'] ?? ''));
            }
            
            return self::SUCCESS_CODE;
        } else {
            $this->error("❌ Ошибка при получении информации: " . ($result['description'] ?? 'Unknown error'));
            return self::ERROR_CODE;
        }
    }

    /**
     * Установка команд бота
     */
    private function setBotCommands(TelegramBotService $botService): int
    {
        $this->info("Установка команд бота...");

        $commands = [
            ['command' => 'start', 'description' => 'Запустить бота и открыть главное меню'],
            ['command' => 'help', 'description' => 'Показать справку'],
            ['command' => 'catalog', 'description' => 'Показать каталог товаров'],
            ['command' => 'cart', 'description' => 'Показать корзину'],
            ['command' => 'orders', 'description' => 'Показать историю заказов'],
        ];

        $result = $botService->setMyCommands($commands);

        if ($result['ok']) {
            $this->success("✅ Команды бота успешно установлены!");
            $this->line("Установленные команды:");
            foreach ($commands as $command) {
                $this->line("  /{$command['command']} - {$command['description']}");
            }
            return self::SUCCESS_CODE;
        } else {
            $this->error("❌ Ошибка при установке команд: " . ($result['description'] ?? 'Unknown error'));
            return self::ERROR_CODE;
        }
    }

    /**
     * Тестирование бота
     */
    private function testBot(TelegramBotService $botService): int
    {
        $this->info("Тестирование бота...");

        // Проверяем токен
        if (!$botService->validateToken()) {
            $this->error("❌ Неверный токен бота!");
            return self::ERROR_CODE;
        }

        // Получаем информацию о боте
        $result = $botService->getMe();
        
        if ($result['ok']) {
            $bot = $result['result'];
            $this->success("✅ Бот работает!");
            $this->line("Имя: " . $bot['first_name']);
            $this->line("Username: @" . $bot['username']);
            $this->line("ID: " . $bot['id']);
            $this->line("Может присоединяться к группам: " . ($bot['can_join_groups'] ? 'да' : 'нет'));
            $this->line("Может читать все сообщения: " . ($bot['can_read_all_group_messages'] ? 'да' : 'нет'));
            $this->line("Поддерживает inline режим: " . ($bot['supports_inline_queries'] ? 'да' : 'нет'));
            return self::SUCCESS_CODE;
        } else {
            $this->error("❌ Ошибка при получении информации о боте: " . ($result['description'] ?? 'Unknown error'));
            return self::ERROR_CODE;
        }
    }

    /**
     * Получить URL для webhook
     */
    private function getWebhookUrl(): ?string
    {
        $baseUrl = $_ENV['WEBHOOK_URL'] ?? $_ENV['APP_URL'] ?? null;
        
        if (!$baseUrl) {
            return null;
        }

        return rtrim($baseUrl, '/') . '/telegram/webhook';
    }

    /**
     * Показать справку
     */
    private function showHelp(): void
    {
        $this->line("Команда для управления Telegram webhook");
        $this->line("");
        $this->line("Использование:");
        $this->line("  php console setup-telegram-webhook-command [action]");
        $this->line("");
        $this->line("Доступные действия:");
        $this->line("  set      - Установить webhook (по умолчанию)");
        $this->line("  delete   - Удалить webhook");
        $this->line("  info     - Показать информацию о webhook");
        $this->line("  commands - Установить команды бота");
        $this->line("  test     - Протестировать бота");
        $this->line("");
        $this->line("Примеры:");
        $this->line("  php console setup-telegram-webhook-command set");
        $this->line("  php console setup-telegram-webhook-command delete");
        $this->line("  php console setup-telegram-webhook-command info");
        $this->line("");
        $this->line("Переменные окружения:");
        $this->line("  TELEGRAM_BOT_TOKEN    - Токен бота (обязательно)");
        $this->line("  WEBHOOK_URL или APP_URL - Базовый URL для webhook");
        $this->line("  TELEGRAM_WEBHOOK_SECRET - Secret token для безопасности (опционально)");
    }

    /**
     * Вывод информационного сообщения
     */
    private function info(string $message): void
    {
        echo "\033[36m[INFO]\033[0m " . $message . "\n";
    }

    /**
     * Вывод сообщения об успехе
     */
    private function success(string $message): void
    {
        echo "\033[32m[SUCCESS]\033[0m " . $message . "\n";
    }

    /**
     * Вывод сообщения об ошибке
     */
    private function error(string $message): void
    {
        echo "\033[31m[ERROR]\033[0m " . $message . "\n";
    }

    /**
     * Вывод обычного сообщения
     */
    private function line(string $message): void
    {
        echo $message . "\n";
    }


}