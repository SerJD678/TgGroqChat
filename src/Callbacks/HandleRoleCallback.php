<?php
// src/Callbacks/HandleRoleCallback.php

namespace App\Callbacks;

use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use App\Utils\Database;
use App\Groq\GroqRole; // Подключаем класс GroqRole
use App\Utils\Logging;

class HandleRoleCallback
{
    public static function handle(CallbackQuery $callbackQuery): ServerResponse
    {
        $data = $callbackQuery->getData();
        $chat_id = $callbackQuery->getFrom()->getId();
        $user_language = $callbackQuery->getFrom()->getLanguageCode() ?? 'en'; // Получаем язык пользователя, по умолчанию 'en'    

        Logging::writeToLog(' Язык пользователя: ' . $user_language, 'INFO', 'HandleRoleCallback.php');

        if (strpos($data, 'setrole_') === 0) {
            $role_id = str_replace('setrole_', '', $data);

            // Загружаем роли из JSON
            $roles = json_decode(file_get_contents(GROQ_ROLES_FILE), true)['roles'] ?? [];
            
            // Поиск нужной роли
            $role_name = GroqRole::getRoleName($role_id, $user_language);

            Logging::writeToLog('Выбрана роль: ' . $role_name, 'INFO', 'HandleRoleCallback.php');

            // Обновляем роль в базе данных
            Database::upsertGroqRole($chat_id, $role_id);

            return Request::editMessageText([
                'chat_id'    => $chat_id,
                'message_id' => $callbackQuery->getMessage()->getMessageId(),
                'text'       => "*Роль успешно установлена:* _{$role_name}_",
                'parse_mode' => 'Markdown',
            ]);
        }

        return Request::answerCallbackQuery([
            'callback_query_id' => $callbackQuery->getId(),
            'text'              => 'Неправильный запрос.',
        ]);
    }
}
