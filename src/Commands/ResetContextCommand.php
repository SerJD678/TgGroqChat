<?php
// src/Commands/ResetContextCommand.php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class ResetContextCommand extends UserCommand
{
    protected $name = 'resetcontext';
    protected $description = 'Сбросить историю переписки';
    protected $usage = '/resetcontext';
    protected $version = '1.0.1';

    public function execute(): ServerResponse
    {
        $chat_id = $this->getMessage()->getChat()->getId();

        // Создание клавиатуры
        $keyboard = new InlineKeyboard(
            [
                ['text' => 'Удалить', 'callback_data' => 'resetcontext_confirm'],
                ['text' => 'Отмена', 'callback_data' => 'resetcontext_cancel'],
            ]
        );

        return Request::sendMessage([
            'chat_id'      => $chat_id,
            'text'         => '*Вы уверены, что хотите удалить историю?*',
            'parse_mode' => 'Markdown',
            'reply_markup' => $keyboard,
        ]);
    }
}
