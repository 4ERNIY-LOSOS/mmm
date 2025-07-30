<?php

namespace App\Controllers;

use Hleb\Base\Controller;
use Hleb\Static\Request;
use Hleb\Static\Response;
use App\Services\TelegramBotService;

class TelegramWebhookController extends Controller
{
    private TelegramBotService $botService;

    public function __construct()
    {
        $this->botService = new TelegramBotService();
    }

    /**
     * Обработка webhook от Telegram
     */
    public function handle(): string
    {
        // Получаем данные от Telegram
        $input = Request::getBody();
        $update = json_decode($input, true);

        if (!$update) {
            Response::setStatus(400);
            return json_encode(['error' => 'Invalid JSON']);
        }

        try {
            $this->processUpdate($update);
            return json_encode(['ok' => true]);
        } catch (\Exception $e) {
            error_log('Telegram webhook error: ' . $e->getMessage());
            Response::setStatus(500);
            return json_encode(['error' => 'Internal server error']);
        }
    }

    /**
     * Обработка обновления от Telegram
     */
    private function processUpdate(array $update): void
    {
        // Обработка обычных сообщений
        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
            return;
        }

        // Обработка callback query (нажатия на inline кнопки)
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
            return;
        }

        // Обработка pre_checkout_query (подтверждение платежа)
        if (isset($update['pre_checkout_query'])) {
            $this->handlePreCheckoutQuery($update['pre_checkout_query']);
            return;
        }
    }

    /**
     * Обработка текстовых сообщений
     */
    private function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $userId = $message['from']['id'];
        $username = $message['from']['username'] ?? '';
        $firstName = $message['from']['first_name'] ?? '';

        // Сохраняем информацию о пользователе (в будущем добавим в БД)
        $userInfo = [
            'user_id' => $userId,
            'username' => $username,
            'first_name' => $firstName,
            'chat_id' => $chatId
        ];

        switch ($text) {
            case '/start':
                $this->handleStartCommand($chatId, $userInfo);
                break;
            
            case '/help':
                $this->handleHelpCommand($chatId);
                break;
            
            case '/catalog':
                $this->handleCatalogCommand($chatId);
                break;
            
            case '/cart':
                $this->handleCartCommand($chatId);
                break;
            
            case '/orders':
                $this->handleOrdersCommand($chatId);
                break;
            
            default:
                $this->handleUnknownCommand($chatId, $text);
        }
    }

    /**
     * Обработка команды /start
     */
    private function handleStartCommand(int $chatId, array $userInfo): void
    {
        $welcomeText = "🛍️ Добро пожаловать в наш интернет-магазин!\n\n";
        $welcomeText .= "Выберите действие:";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🛒 Открыть магазин', 'web_app' => ['url' => $this->getWebAppUrl()]]
                ],
                [
                    ['text' => '📱 Каталог', 'callback_data' => 'catalog'],
                    ['text' => '🛍️ Корзина', 'callback_data' => 'cart']
                ],
                [
                    ['text' => '📦 Мои заказы', 'callback_data' => 'orders'],
                    ['text' => '❓ Помощь', 'callback_data' => 'help']
                ]
            ]
        ];

        $this->botService->sendMessage($chatId, $welcomeText, $keyboard);
    }

    /**
     * Обработка команды /help
     */
    private function handleHelpCommand(int $chatId): void
    {
        $helpText = "📋 Доступные команды:\n\n";
        $helpText .= "/start - Главное меню\n";
        $helpText .= "/catalog - Каталог товаров\n";
        $helpText .= "/cart - Ваша корзина\n";
        $helpText .= "/orders - История заказов\n";
        $helpText .= "/help - Эта справка\n\n";
        $helpText .= "Или используйте кнопку 'Открыть магазин' для полного интерфейса!";

        $this->botService->sendMessage($chatId, $helpText);
    }

    /**
     * Обработка команды /catalog
     */
    private function handleCatalogCommand(int $chatId): void
    {
        $catalogText = "📱 Каталог товаров:\n\n";
        $catalogText .= "В данный момент каталог находится в разработке.\n";
        $catalogText .= "Используйте кнопку 'Открыть магазин' для просмотра товаров!";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🛒 Открыть магазин', 'web_app' => ['url' => $this->getWebAppUrl()]]
                ],
                [
                    ['text' => '⬅️ Назад', 'callback_data' => 'back_to_menu']
                ]
            ]
        ];

        $this->botService->sendMessage($chatId, $catalogText, $keyboard);
    }

    /**
     * Обработка команды /cart
     */
    private function handleCartCommand(int $chatId): void
    {
        $cartText = "🛍️ Ваша корзина:\n\n";
        $cartText .= "Корзина пуста.\n";
        $cartText .= "Добавьте товары через наш магазин!";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🛒 Открыть магазин', 'web_app' => ['url' => $this->getWebAppUrl()]]
                ],
                [
                    ['text' => '⬅️ Назад', 'callback_data' => 'back_to_menu']
                ]
            ]
        ];

        $this->botService->sendMessage($chatId, $cartText, $keyboard);
    }

    /**
     * Обработка команды /orders
     */
    private function handleOrdersCommand(int $chatId): void
    {
        $ordersText = "📦 Ваши заказы:\n\n";
        $ordersText .= "У вас пока нет заказов.\n";
        $ordersText .= "Сделайте первый заказ в нашем магазине!";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🛒 Открыть магазин', 'web_app' => ['url' => $this->getWebAppUrl()]]
                ],
                [
                    ['text' => '⬅️ Назад', 'callback_data' => 'back_to_menu']
                ]
            ]
        ];

        $this->botService->sendMessage($chatId, $ordersText, $keyboard);
    }

    /**
     * Обработка неизвестных команд
     */
    private function handleUnknownCommand(int $chatId, string $text): void
    {
        $responseText = "🤖 Я не понимаю эту команду.\n\n";
        $responseText .= "Используйте /help для списка доступных команд или /start для главного меню.";

        $this->botService->sendMessage($chatId, $responseText);
    }

    /**
     * Обработка callback query (нажатия на inline кнопки)
     */
    private function handleCallbackQuery(array $callbackQuery): void
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        $data = $callbackQuery['callback_data'];
        $callbackQueryId = $callbackQuery['id'];

        // Отвечаем на callback query
        $this->botService->answerCallbackQuery($callbackQueryId);

        switch ($data) {
            case 'catalog':
                $this->handleCatalogCommand($chatId);
                break;
            
            case 'cart':
                $this->handleCartCommand($chatId);
                break;
            
            case 'orders':
                $this->handleOrdersCommand($chatId);
                break;
            
            case 'help':
                $this->handleHelpCommand($chatId);
                break;
            
            case 'back_to_menu':
                $this->handleStartCommand($chatId, []);
                break;
            
            default:
                $this->botService->sendMessage($chatId, "Неизвестное действие: " . $data);
        }
    }

    /**
     * Обработка pre_checkout_query (подтверждение платежа)
     */
    private function handlePreCheckoutQuery(array $preCheckoutQuery): void
    {
        $preCheckoutQueryId = $preCheckoutQuery['id'];
        
        // В будущем здесь будет проверка заказа и наличия товаров
        // Пока просто подтверждаем все платежи
        $this->botService->answerPreCheckoutQuery($preCheckoutQueryId, true);
    }

    /**
     * Получить URL для Web App
     */
    private function getWebAppUrl(): string
    {
        $baseUrl = $_ENV['MINI_APP_BASE_URL'] ?? 'https://your-domain.com';
        return $baseUrl . '/shop';
    }
}