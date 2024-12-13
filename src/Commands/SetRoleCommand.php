<?php
// src/Commands/SetRoleCommand.php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use App\Utils\Database;
use App\Utils\Logging;
use App\Groq\GroqRole; // Подключаем класс GroqRole

class SetRoleCommand extends UserCommand
{
    protected $name = 'setrole';
    protected $description = 'Выбор роли для общения с Groq';
    protected $usage = '/setrole';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $language_code = $message->getFrom()->getLanguageCode() ?? 'en';

        // Получаем текущую роль из базы данных
        try {
            $role = Database::getGroqRoleByChatId($chat_id);
        } catch (\PDOException $e) {
            Logging::writeToLog("Ошибка получения роли: " . $e->getMessage(), 'ERROR', 'SetRoleCommand.php');
            $role = null;
        }

        // Формируем клавиатуру
        $keyboard = new InlineKeyboard(...$this->getRoleButtons($language_code));

        $text = '*Выберите мою роль для общения:*';
        if ($role) {
            $text .= "\nТекущая роль: _" . GroqRole::getRoleName($role, $language_code) . "_";
        }

        // Отправка сообщения с клавиатурой
        return Request::sendMessage([
            'chat_id' => $chat_id,
            'text'    => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => $keyboard,
        ]);
    }

    private function getRoleButtons(string $language_code): array
    {
        $buttons = [];
        $roles = json_decode(file_get_contents(GROQ_ROLES_FILE), true)['roles'] ?? [];

        foreach ($roles as $role) {
            $buttons[] = [new InlineKeyboardButton([
                'text' => GroqRole::getRoleName($role['id'], $language_code),
                'callback_data' => 'setrole_' . $role['id'],
            ])];
        }

        return $buttons;
    }
}
