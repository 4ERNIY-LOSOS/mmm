<?php

namespace App\Services;

class TelegramBotService
{
    private string $botToken;
    private string $apiUrl;

    public function __construct()
    {
        $this->botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}/";
        
        if (empty($this->botToken)) {
            throw new \Exception('TELEGRAM_BOT_TOKEN environment variable is not set');
        }
    }

    /**
     * Отправка сообщения
     */
    public function sendMessage(int $chatId, string $text, array $keyboard = null, array $options = []): array
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $options['parse_mode'] ?? 'HTML',
            'disable_web_page_preview' => $options['disable_web_page_preview'] ?? false,
            'disable_notification' => $options['disable_notification'] ?? false,
        ];

        if ($keyboard) {
            $params['reply_markup'] = json_encode($keyboard);
        }

        return $this->makeRequest('sendMessage', $params);
    }

    /**
     * Редактирование сообщения
     */
    public function editMessage(int $chatId, int $messageId, string $text, array $keyboard = null): array
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if ($keyboard) {
            $params['reply_markup'] = json_encode($keyboard);
        }

        return $this->makeRequest('editMessageText', $params);
    }

    /**
     * Удаление сообщения
     */
    public function deleteMessage(int $chatId, int $messageId): array
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ];

        return $this->makeRequest('deleteMessage', $params);
    }

    /**
     * Отправка фото
     */
    public function sendPhoto(int $chatId, string $photo, string $caption = '', array $keyboard = null): array
    {
        $params = [
            'chat_id' => $chatId,
            'photo' => $photo,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        ];

        if ($keyboard) {
            $params['reply_markup'] = json_encode($keyboard);
        }

        return $this->makeRequest('sendPhoto', $params);
    }

    /**
     * Отправка документа
     */
    public function sendDocument(int $chatId, string $document, string $caption = '', array $keyboard = null): array
    {
        $params = [
            'chat_id' => $chatId,
            'document' => $document,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        ];

        if ($keyboard) {
            $params['reply_markup'] = json_encode($keyboard);
        }

        return $this->makeRequest('sendDocument', $params);
    }

    /**
     * Ответ на callback query
     */
    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): array
    {
        $params = [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert,
        ];

        return $this->makeRequest('answerCallbackQuery', $params);
    }

    /**
     * Отправка инвойса для оплаты
     */
    public function sendInvoice(
        int $chatId,
        string $title,
        string $description,
        string $payload,
        string $providerToken,
        array $prices,
        array $options = []
    ): array {
        $params = [
            'chat_id' => $chatId,
            'title' => $title,
            'description' => $description,
            'payload' => $payload,
            'provider_token' => $providerToken,
            'currency' => $options['currency'] ?? 'RUB',
            'prices' => json_encode($prices),
            'start_parameter' => $options['start_parameter'] ?? 'shop',
            'photo_url' => $options['photo_url'] ?? null,
            'photo_size' => $options['photo_size'] ?? null,
            'photo_width' => $options['photo_width'] ?? null,
            'photo_height' => $options['photo_height'] ?? null,
            'need_name' => $options['need_name'] ?? true,
            'need_phone_number' => $options['need_phone_number'] ?? true,
            'need_email' => $options['need_email'] ?? false,
            'need_shipping_address' => $options['need_shipping_address'] ?? false,
            'send_phone_number_to_provider' => $options['send_phone_number_to_provider'] ?? false,
            'send_email_to_provider' => $options['send_email_to_provider'] ?? false,
            'is_flexible' => $options['is_flexible'] ?? false,
        ];

        // Убираем null значения
        $params = array_filter($params, function($value) {
            return $value !== null;
        });

        return $this->makeRequest('sendInvoice', $params);
    }

    /**
     * Ответ на pre-checkout query
     */
    public function answerPreCheckoutQuery(string $preCheckoutQueryId, bool $ok, string $errorMessage = ''): array
    {
        $params = [
            'pre_checkout_query_id' => $preCheckoutQueryId,
            'ok' => $ok,
        ];

        if (!$ok && $errorMessage) {
            $params['error_message'] = $errorMessage;
        }

        return $this->makeRequest('answerPreCheckoutQuery', $params);
    }

    /**
     * Установка webhook
     */
    public function setWebhook(string $url, array $options = []): array
    {
        $params = [
            'url' => $url,
            'max_connections' => $options['max_connections'] ?? 40,
            'allowed_updates' => $options['allowed_updates'] ?? ['message', 'callback_query', 'pre_checkout_query'],
            'drop_pending_updates' => $options['drop_pending_updates'] ?? true,
        ];

        if (isset($options['certificate'])) {
            $params['certificate'] = $options['certificate'];
        }

        if (isset($options['secret_token'])) {
            $params['secret_token'] = $options['secret_token'];
        }

        return $this->makeRequest('setWebhook', $params);
    }

    /**
     * Удаление webhook
     */
    public function deleteWebhook(bool $dropPendingUpdates = true): array
    {
        $params = [
            'drop_pending_updates' => $dropPendingUpdates,
        ];

        return $this->makeRequest('deleteWebhook', $params);
    }

    /**
     * Получение информации о webhook
     */
    public function getWebhookInfo(): array
    {
        return $this->makeRequest('getWebhookInfo');
    }

    /**
     * Получение информации о боте
     */
    public function getMe(): array
    {
        return $this->makeRequest('getMe');
    }

    /**
     * Установка команд бота
     */
    public function setMyCommands(array $commands): array
    {
        $params = [
            'commands' => json_encode($commands),
        ];

        return $this->makeRequest('setMyCommands', $params);
    }

    /**
     * Выполнение запроса к Telegram API
     */
    private function makeRequest(string $method, array $params = []): array
    {
        $url = $this->apiUrl . $method;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \Exception("cURL Error: " . $error);
        }

        $result = json_decode($response, true);

        if ($httpCode !== 200 || !$result['ok']) {
            $errorMsg = $result['description'] ?? 'Unknown error';
            throw new \Exception("Telegram API Error (HTTP {$httpCode}): " . $errorMsg);
        }

        return $result;
    }

    /**
     * Логирование ошибок
     */
    private function logError(string $message, array $context = []): void
    {
        $logMessage = date('Y-m-d H:i:s') . " [ERROR] " . $message;
        if (!empty($context)) {
            $logMessage .= " Context: " . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        error_log($logMessage);
    }

    /**
     * Проверка валидности токена
     */
    public function validateToken(): bool
    {
        try {
            $result = $this->getMe();
            return isset($result['result']['id']);
        } catch (\Exception $e) {
            return false;
        }
    }
}