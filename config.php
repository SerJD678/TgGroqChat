<?php

// TgGroqChat_v3/config.php

use Dotenv\Dotenv;

// Проверяем, загружен ли конфиг
if (!defined('CONFIG_LOADED')) {
    define('CONFIG_LOADED', true);

    // Подключение автозагрузчика Composer
    require_once __DIR__ . '/vendor/autoload.php';

    // Загружаем переменные окружения
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Проверка наличия всех необходимых переменных окружения
    $requiredEnvVars = ['DB_USER', 'DB_PASS', 'DB_NAME', 'TELEGRAM_BOT_API_TOKEN', 'TELEGRAM_BOT_USERNAME', 'GROQ_API_KEY'];
    foreach ($requiredEnvVars as $var) {
        if (empty($_ENV[$var])) {            
            die("Ошибка: переменная $var отсутствует в .env файле.");
        }
    }

    // Настройки подключения к базе данных
    define('DB_HOST', $_ENV['DB_HOST']);
    define('DB_USER', $_ENV['DB_USER']);
    define('DB_PASS', $_ENV['DB_PASS']);
    define('DB_NAME', $_ENV['DB_NAME']);

    // Токен Telegram-бота
    define('TELEGRAM_BOT_API_TOKEN', $_ENV['TELEGRAM_BOT_API_TOKEN']);
    //username_bot
    define('TELEGRAM_BOT_USERNAME', $_ENV['TELEGRAM_BOT_USERNAME']);
    define('BOT_OWNER_ID', $_ENV['BOT_OWNER_ID']); 

    // Настройки Groq API
    define('GROQ_API_URL', $_ENV['GROQ_API_URL'] ?? 'https://api.groq.com/openai/v1/chat/completions'); // URL GROQ API
    define('GROQ_API_KEY', $_ENV['GROQ_API_KEY']);
    define('GROQ_MODEL', $_ENV['GROQ_MODEL'] ?? 'llama-3.1-70b-specdec');
    define('GROQ_MAX_TOKENS', (int) ($_ENV['MAX_TOKENS'] ?? 300));
    define('GROQ_TEMPERATURE', (float) ($_ENV['TEMPERATURE'] ?? 1.2));
    define('GROQ_CONTEXT_LENGTH', (int) ($_ENV['CONTEXT_LENGTH'] ?? 6)); 
    define('GROQ_ROLES_FILE', __DIR__ . '/src/Groq/groq_roles.json');   

    // Настройки логирования
    define('ENABLE_LOGGING', $_ENV['ENABLE_LOGGING'] ?? true);
    define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'ERROR'); // Уровень логирования: ERROR, DEBUG, WARNING, INFO, ALL
    define('LOG_FILE', __DIR__ . '/log.txt');
    
    //Авиротизация    
    define('AUTHORIZED_USERS', $_ENV['AUTHORIZED_USERS'] ?? 'Admin'); // Список пользователей для авторизации
    define('AUTHORIZED_PASSWORD', $_ENV['AUTHORIZED_PASSWORD'] ?? 1324); // Пароль для авторизации'
}
