<?php
// src/Handlers/MediaMessageHandler.php

namespace App\Handlers;

use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use App\Utils\Logging;
use App\Utils\Database;

class MediaMessageHandler
{
    public static function handle(Message $message): ServerResponse
    {
        $chat_id = $message->getChat()->getId();
        $caption = $message->getCaption(); // Подпись к медиа (если есть)

        try {
            // Определяем тип медиа
            if ($message->getPhoto()) {
                $photos = $message->getPhoto(); // Сохраняем в переменную
                $fileId = end($photos)->getFileId();
                $mediaType = 'photo';
            } elseif ($message->getVideo()) {
                $fileId = $message->getVideo()->getFileId();
                $mediaType = 'video';
            } elseif ($message->getAnimation()) {
                $fileId = $message->getAnimation()->getFileId();
                $mediaType = 'animation';
            } else {
                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text'    => 'Неизвестный тип медиа.',
                ]);
            }

            // Удаляем ссылки из текста (если есть подпись)
            if (!empty($caption)) {
                $originalCaption = $caption;
                $caption = preg_replace('/\bhttps?:\/\/\S+/i', '', $caption);
                Logging::writeToLog("Оригинальная подпись: $originalCaption", 'INFO', 'MediaMessageHandler.php');
                Logging::writeToLog("Очищенная подпись: $caption", 'INFO', 'MediaMessageHandler.php');
            }

            // Логируем тип медиа и его File ID
            Logging::writeToLog("Обнаружено медиа ($mediaType): $fileId", 'INFO', 'MediaMessageHandler.php');

            // Сохраняем информацию о медиа и подписи (без ссылок) в базу данных
            // Database::insertChatHistoryMultiple([
            //     [
            //         'chat_id' => $chat_id,
            //         'role'    => 'user',
            //         'message' => "Медиа ($mediaType): $fileId\n" . ($caption ? "Подпись: $caption" : ''),
            //     ]
            // ]);

            // Отправляем сообщение пользователю с медиа и очищенной подписью
            $response = Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => $caption ? "Ваше медиа ($mediaType):\n$caption" : "Ваше медиа ($mediaType) получено.",
            ]);

            // Пересылаем медиа обратно пользователю
            switch ($mediaType) {
                case 'photo':
                    Request::sendPhoto([
                        'chat_id' => $chat_id,
                        'photo'   => $fileId,
                        'caption' => $caption,
                    ]);
                    break;

                case 'video':
                    Request::sendVideo([
                        'chat_id' => $chat_id,
                        'video'   => $fileId,
                        'caption' => $caption,
                    ]);
                    break;

                case 'animation':
                    Request::sendAnimation([
                        'chat_id' => $chat_id,
                        'animation' => $fileId,
                        'caption'   => $caption,
                    ]);
                    break;
            }

            return $response;
        } catch (\Exception $e) {
            Logging::writeToLog('Ошибка обработки медиа: ' . $e->getMessage(), 'ERROR', 'MediaMessageHandler.php');

            return Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => 'Произошла ошибка при обработке вашего медиа-сообщения.',
            ]);
        }
    }
}
