<?php
// src/Commands/GetAdminLinkCommand.php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Exception\TelegramException;
use App\Utils\Authorization;

class GetAdminLinkCommand extends UserCommand
{
    protected $name = 'getadminlink';
    protected $description = 'Сгенерировать ссылку для авторизации админа';
    protected $usage = '/getadminlink';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $chat_id = $this->getMessage()->getChat()->getId();
        $user_id = $this->getMessage()->getFrom()->getId();

        $bot_owner_id = BOT_OWNER_ID;

        // Проверка, является ли пользователь администратором
        if (intval($user_id) !== intval($bot_owner_id)) {
            return Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => 'У вас нет прав для выполнения этой команды.' . $msg,
            ]);
        }        

        // Получение URL вебхука
        $webhookInfo = Request::getWebhookInfo();
        $currentWebhookUrl = $webhookInfo->getResult()->url;

        // Замена bot.php на index.php
        $admin_url = str_replace('bot.php', 'index.php', $currentWebhookUrl);
        $token = Authorization::generateAuthToken($user_id); // Генерация токена

        // Полная ссылка для авторизации
        $auth_link = $admin_url . '?token=' . $token;

        return Request::sendMessage([
            'chat_id' => $chat_id,
            'text'    => '<i>Ссылка для авторизации:</i> ' . $auth_link,
            'parse_mode' => 'HTML',            
        ]);
    }
}
