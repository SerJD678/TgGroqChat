<?php 
// src/Commands/ForwardedMessageCommand.php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use App\Utils\Logging;
use App\Utils\Database;

class ForwardedMessageCommand extends SystemCommand
{
    protected $name = 'forwardedmessage';
    protected $description = 'Обработка пересланных сообщений';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $userLanguage = $message->getFrom()->getLanguageCode() ?? 'en';

        Logging::writeToLog("\n ForwardedMessageCommand вызван.", 'INFO', 'ForwardedMessageCommand.php');
        Logging::writeToLog('Входные данные:' . json_encode($message), 'DEBUG', 'ForwardedMessageCommand.php');

        try {
            // Проверяем, есть ли медиа-контент
            $mediaType = null;
            $mediaData = null;

            if ($message->getPhoto()) {
                $mediaType = 'photo';
                $mediaData = end($message->getPhoto())->getFileId();
            } elseif ($message->getVideo()) {
                $mediaType = 'video';
                $mediaData = $message->getVideo()->getFileId();
            } elseif ($message->getAnimation()) {
                $mediaType = 'animation';
                $mediaData = $message->getAnimation()->getFileId();
            }

            // Проверяем, переслано ли сообщение
            $isForwarded = $message->getForwardFrom() || $message->getForwardFromChat();

            if ($isForwarded) {
                // Сохраняем пересланное сообщение в базу данных
                // Database::insertChatHistoryMultiple([
                //     [
                //         'chat_id' => $chat_id,
                //         'role'    => 'user',
                //         'message' => 'Пересланное сообщение',
                //     ],
                //     [
                //         'chat_id' => $chat_id,
                //         'role'    => 'assistant',
                //         'message' => $mediaType ? "Переслано медиа: $mediaType" : "Переслан текст",
                //     ],
                // ]);

                // Отправляем сообщение пользователю
                $replyText = "Вы переслали сообщение.";
                if ($mediaType) {
                    $replyText .= "\nТип медиа: $mediaType";
                }

                // Отправка ответа пользователю
                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text'    => $replyText,
                ]);
            } else {
                // Если сообщение не переслано, возвращаем сообщение об ошибке
                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text'    => 'Это сообщение не является пересланным.',
                ]);
            }
        } catch (\Exception $e) {
            // Логируем ошибку и отправляем сообщение пользователю
            Logging::writeToLog('Ошибка: ' . $e->getMessage(), 'ERROR', 'ForwardedMessageCommand.php');
            
            return Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => 'Произошла ошибка при обработке вашего пересланного сообщения.',
            ]);
        }
    }
}
