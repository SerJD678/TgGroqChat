<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\TelegramLog;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use App\Utils\Logging;

// Создание экземпляра Telegram API
try {
    $telegram = new Telegram(TELEGRAM_BOT_API_TOKEN, 'LamaSageBot');

    // Настройка Monolog
    $logger = new Logger('telegram_bot');
    $logger->pushHandler(new StreamHandler(LOG_FILE, Logger::DEBUG));

    // Установка логирования
    TelegramLog::initialize($logger);

    // Логирование входящих данных от Telegram
    $input = file_get_contents('php://input');
    Logging::writeToLog("\nВходящий запрос от Telegram... " , 'DEBUG', 'bot.php');
    Logging::writeToLog("\nInput: " . $input."\n", 'DEBUG', 'bot.php');


    //TelegramLog::debug('Входящий от Telegram:' . $input);



    // Подключение пользовательских команд
    $telegram->addCommandsPath(__DIR__ . '/src/Commands');


    // Запуск обработки
    $telegram->handle();


} catch (Exception $e) {
    Logging::writeToLog('Ошибка: ' . $e->getMessage(), 'ERROR', 'bot.php');    
}
