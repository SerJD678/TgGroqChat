<?php
// src/Commands/CallbackqueryCommand.php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use App\Callbacks\ResetContextCallback;
use App\Callbacks\HandleRoleCallback;

class CallbackqueryCommand extends SystemCommand
{
    protected $name = 'callbackquery';
    protected $description = 'Обработка callback-запросов';
    protected $version = '1.0.1';

    public function execute(): ServerResponse
    {
        $callbackQuery = $this->getCallbackQuery();
        $data = $callbackQuery->getData();

        if (strpos($data, 'resetcontext_') === 0) {
            // Обработка resetcontext callback
            return ResetContextCallback::handle($callbackQuery);
        }

        if (strpos($data, 'setrole_') === 0) {
            // Обработка setrole callback
            return HandleRoleCallback::handle($callbackQuery);
        }

        // Если данные не распознаны
        return Request::answerCallbackQuery([
            'callback_query_id' => $callbackQuery->getId(),
            'text'              => 'Неправильный запрос.',
        ]);
    }
}
