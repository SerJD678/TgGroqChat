<?php
// src/Callbacks/ResetContextCallback.php

namespace App\Callbacks;

use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use App\Utils\Database;
use App\Utils\Logging;

class ResetContextCallback
{
    public static function handle(CallbackQuery $callbackQuery): ServerResponse
    {
        $data = $callbackQuery->getData();
        $chat_id = $callbackQuery->getFrom()->getId();
        
        if ($data === 'resetcontext_confirm') {
            // Удаляем историю из базы данных
            Database::prepare("DELETE FROM chat_history WHERE chat_id = ?")->execute([$chat_id]);

            return Request::editMessageText([
                'chat_id'    => $chat_id,
                'message_id' => $callbackQuery->getMessage()->getMessageId(),
                'text'       => '*История успешно удалена.*',
                'parse_mode' => 'Markdown',
            ]);
        }

        if ($data === 'resetcontext_cancel') {
            // Удаляем сообщение при отмене
            return Request::deleteMessage([
                'chat_id'    => $callbackQuery->getMessage()->getChat()->getId(),
                'message_id' => $callbackQuery->getMessage()->getMessageId(),
            ]);
        }

        // Если запрос не распознан
        return Request::answerCallbackQuery([
            'callback_query_id' => $callbackQuery->getId(),
            'text'              => 'Неправильный запрос.',
        ]);
    }
}
