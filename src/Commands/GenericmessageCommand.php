<?php
// src/Commands/GenericmessageCommand.php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use App\Groq\GroqHandler;
use App\Utils\Logging;
use App\Utils\Database;
use App\Handlers\MediaMessageHandler; // Подключаем обработчик для медиа

class GenericmessageCommand extends SystemCommand
{
    protected $name = 'genericmessage';
    protected $description = 'Обработка всех обычных сообщений';
    protected $version = '1.2.0';

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $text    = $message->getText(true); // Получение текста сообщения
        $userLanguage = $message->getFrom()->getLanguageCode() ?? 'en';

        // Проверяем наличие медиа-контента
        if ($message->getPhoto() || $message->getVideo() || $message->getAnimation()) {
            Logging::writeToLog('Обнаружено сообщение с медиа.', 'INFO', 'GenericmessageCommand.php');
            
            // Передаем обработку медиа в отдельный файл
            return MediaMessageHandler::handle($message);
        }

        //Logging::writeToLog("\n GenericmessageCommand вызван.", 'INFO', 'GenericmessageCommand.php');
        //Logging::writeToLog('Входные данные:' . json_encode($message), 'DEBUG', 'GenericmessageCommand.php');

        try {
            if (empty($text)) {
                // Если текст сообщения пустой, отправляем сообщение пользователю
                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text'    => "Произошла ошибка при обработке. \n ".
                                 "Пустое сообщение: " . $text .
                                 "\n Попробуйте повторить позже.",
                ]);
            }

            // Создаем экземпляр обработчика Groq
            $groqHandler = new GroqHandler();

            // Отправляем сообщение в Groq и получаем ответ
            $responseGroqText = $groqHandler->handleGroqMessage($text, $chat_id, $userLanguage);

            if (!$responseGroqText) {
                // Если ответ пустой, отправляем сообщение об ошибке
                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text'    => "Извините, произошла ошибка при обработке вашего запроса. \n Попробуйте повторить позже.",
                ]);
            } else {
                // Если ответ не пустой, сохраняем его в базе данных
                Database::insertChatHistoryMultiple([
                    [
                        'chat_id' => $chat_id,
                        'role'    => 'user',
                        'message' => $text,
                    ],
                    [
                        'chat_id' => $chat_id,
                        'role'    => 'assistant',
                        'message' => $responseGroqText,
                    ]
                ]);
            }

            // Отправляем ответ от Groq пользователю
            return Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => "*Мудрец:* \n$responseGroqText",
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Exception $e) {
            // Логируем ошибку и отправляем сообщение пользователю
            Logging::writeToLog('Ошибка: ' . $e->getMessage(), 'ERROR', 'GenericmessageCommand.php');

            return Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => 'Произошла ошибка при обработке вашего сообщения.',
            ]);
        }
    }
}
