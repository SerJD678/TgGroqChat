<?php
// src/Commands/StartCommand.php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use App\Utils\Database;
use App\Utils\Logging;

class StartCommand extends SystemCommand
{
    protected $name = 'start';
    protected $description = 'Запуск бота';
    protected $usage = '/start';
    protected $version = '1.1.0';

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $username = $message->getFrom()->getUsername();
        $first_name = $message->getFrom()->getFirstName();
        $language_code = $message->getFrom()->getLanguageCode() ?? 'en';

        // Проверка на наличие реферала
        $text = $message->getText(true);
        $referral_id = is_numeric($text) ? (int)$text : null;

        try {
            // Проверяем, существует ли пользователь
            if (!Database::userExists($chat_id)) {
                // Регистрируем нового пользователя
                $isRegistered = Database::registerUser(
                    $chat_id,
                    $username,
                    $first_name,
                    $language_code,
                    $referral_id
                );

                if ($isRegistered) {
                    Logging::writeToLog("Новый пользователь зарегистрирован: $chat_id", 'INFO', 'StartCommand.php');
                } else {
                    Logging::writeToLog("Ошибка при регистрации пользователя: $chat_id", 'ERROR', 'StartCommand.php');
                }
            } else {
                Logging::writeToLog("Пользователь уже зарегистрирован: $chat_id", 'INFO', 'StartCommand.php');
            }

            // Формируем приветственное сообщение
            // Если first_name не указано, используем username 
            $name = !empty($first_name) ? $first_name : $username;            
            $text = "Привет, *$name*! 🌟 \n\n" .
                    "*Добро пожаловать в чат с Ламой Мудрецом!* \n\n" .

                    "Я здесь, чтобы помочь вам с любыми вопросами и задачами. Если у вас есть что-то на уме или нужна помощь, не стесняйтесь обращаться.\n\n" .

                    "*Чтобы начать, вы можете:*\n" .
                    "- _Задать вопрос_\n" .
                    "- _Попросить помощь с конкретной задачей_ \n" .
                    "- _Узнать больше о моих возможностях_ \n\n" .

                    "*Вот команды, которые помогут вам:*\n" .
                    "/help — _Список доступных команд и информация о них._ \n" .
                    "/setrole — _Выбрать роль для общения на определённую тему._ \n" .
                    "\n*Наслаждайтесь нашим общением!*";
            

        } catch (\PDOException $e) {
            Logging::writeToLog("Ошибка при регистрации пользователя: " . $e->getMessage(), 'ERROR', 'StartCommand.php');
            return Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => 'Произошла ошибка. Попробуйте зайти позже.',
            ]);
        }

        return Request::sendMessage([
            'chat_id' => $chat_id,
            'text'    => $text,
            'parse_mode' => 'Markdown',
        ]);
    }
}
