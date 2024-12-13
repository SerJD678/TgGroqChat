<?php
// src/Commands/HelpCommand.php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class HelpCommand extends UserCommand
{
    protected $name = 'help';
    protected $description = 'Получить список доступных команд';
    protected $usage = '/help';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $chat_id = $this->getMessage()->getChat()->getId();       
        
        $admin = (intval($chat_id) === intval(BOT_OWNER_ID));

        $text = "\n*Список доступных команд:*\n" .
                "/start - _Начать работу с ботом._\n" .
                "/help - _Получить список доступных команд._\n" .
                "/setrole - _Выбрать роль для общения на определённую тему._\n";
                
        $text .= $admin ? "/resetcontext - _Сбросить историю переписки._\n" : ''; 

        if ($admin) {
            $text .= "\n*Список админских команд:*\n" .
                "/getadminlink - _Получить ссылку для авторизации в ботом._\n";
        }

        return Request::sendMessage([
            'chat_id' => $chat_id,
            'text'    => $text,
            'parse_mode' => 'Markdown',
        ]);
    }
}