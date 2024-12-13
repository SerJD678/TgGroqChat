<?php
// TgGroqChat_v3/src/Groq/GroqHandler.php 
namespace App\Groq;

use App\Utils\Database;
use App\Utils\Logging;

class GroqHandler
{
    // Конфигурация GROQ API
    private string $groqApiUrl;
    private string $groqApiKey;
    private string $groqModel;
    private int $maxTokens;
    private float $temperature;
    private int $contextLength;

    public function __construct()
    {
        // Конфигурация GROQ API
        $this->groqApiUrl = GROQ_API_URL;
        $this->groqApiKey = GROQ_API_KEY;
        $this->groqModel = GROQ_MODEL;
        $this->maxTokens = GROQ_MAX_TOKENS;
        $this->temperature = GROQ_TEMPERATURE;
        $this->contextLength = GROQ_CONTEXT_LENGTH;
    }

    /**
     * Определение языка текста
     */
    private function detectLanguage(string $text): string
    {
        if (preg_match('/[А-Яа-яЁё]/u', $text)) {
            return 'ru'; // Русский язык
        } elseif (preg_match('/[А-ЯҐЄІЇа-яґєії]/u', $text)) {
            return 'uk'; // Украинский язык
        }
        return 'en'; // Английский язык
    }

    /**
     * Обработка сообщения с помощью Groq API
     */
    public function handleGroqMessage(string $message, int $chatId, string $userLanguage = 'en'): ?string
    {
        Logging::writeToLog("\n handleGroqMessage вызван.", 'INFO', 'GroqHandler.php');
        Logging::writeToLog('Входные данные:' . json_encode(['message' => $message, 'chatId' => $chatId]), 'INFO', 'GroqHandler.php');

        // Проверка ключа и URL
        if (empty($this->groqApiKey) || empty($this->groqApiUrl)) {
            Logging::writeToLog('Ошибка: GROQ API конфигурация не задана.', 'ERROR', 'GroqHandler.php');
            return null;
        }

        // Определение языка сообщения
        $userLanguageText = $this->detectLanguage($message);

        // Проверка, отличается ли текущий язык пользователя от языка сообщения
        if ($userLanguage != $userLanguageText) {
            // Если языки отличаются, обновляем текущий язык пользователя
            $userLanguage = $userLanguageText;
        }

        
        Logging::writeToLog('Язык сообщения: ' . $userLanguage, 'INFO', 'GroqHandler.php');

        try {
            // Получение роли пользователя
            
            $userRole = Database::getGroqRoleByChatId($chatId);

            // Если роли нет, сохраняем как 'default'
            if (!$userRole) {
                $userRole = 'default';
                Database::upsertGroqRole($chatId, $userRole);                
            }

            Logging::writeToLog("Роль Groq: $userRole", 'INFO', 'GroqHandler.php');

            // Получение контекста сообщений

            $contextMessages = Database::getChatHistoryByChatId($chatId, $this->contextLength);

            Logging::writeToLog("Контекст сообщений: " . json_encode($contextMessages), 'INFO', 'GroqHandler.php');

            // Формирование системного промпта
            $systemPrompt = GroqRole::getSystemPrompt($userLanguage, $userRole);
            Logging::writeToLog("Системный промпт: $systemPrompt", 'INFO', 'GroqHandler.php');

            // Формирование массива сообщений
            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            foreach ($contextMessages as $contextMessage) {
                $messages[] = ['role' => $contextMessage['role'], 'content' => $contextMessage['message']];
            }
            $messages[] = ['role' => 'user', 'content' => $message];

            $data = [
                'model' => $this->groqModel,
                'messages' => $messages,
                'temperature' => $this->temperature,
                'max_tokens' => $this->maxTokens,
            ];

            // Запрос к API Groq
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->groqApiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->groqApiKey
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                Logging::writeToLog('Ошибка CURL: ' . curl_error($ch), 'ERROR', 'GroqHandler.php');
                curl_close($ch);
                return null;
            }
            curl_close($ch);

            $responseData = json_decode($response, true);
            // Обработка ответа от API Groq
            Logging::writeToLog("Ответ от Groq API: " . json_encode($responseData), 'DEBUG', 'GroqHandler.php');

            $content = $responseData['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                Logging::writeToLog('Ошибка: пустой ответ от Groq API.', 'ERROR', 'GroqHandler.php');
                if ($chatId === BOT_OWNER_ID) {
                    // Пользователь — создатель бота
                    return  "Ошибка от Groq API: " . json_encode($responseData);
                } else {
                    // Пользователь не является создателем бота
                    return null;
                }
                
            }

            Logging::writeToLog("Ответ от Groq Текст: $content", 'INFO', 'GroqHandler.php');
            return $content;

        } catch (\Exception $e) {
            Logging::writeToLog('Ошибка: ' . $e->getMessage(), 'ERROR', 'GroqHandler.php');
            return null;
        }
    }
}
