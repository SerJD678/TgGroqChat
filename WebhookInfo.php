<?php
// WebhookInfo.php
require_once __DIR__ . '/src/Utils/Authorization.php'; 
use App\Utils\Authorization; // Выполнение проверки авторизации 
//Authorization::checkAuthorization();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;

// Определение пути к файлу bot.php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$script = str_replace('WebhookInfo.php', 'bot.php', $_SERVER['SCRIPT_NAME']);
$webhook_url = $protocol . $domain . $script;

$telegram = new Telegram(TELEGRAM_BOT_API_TOKEN, 'LamaSageBot');

$action = '';
$response = '';
$statusMessage = '';
$webhookInfoResponse = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {        
        $action = $_POST['action'];
    }
}

try {
    if ($action === 'set') {
        // Установка вебхука
        $response = $telegram->setWebhook($webhook_url);
        $statusMessage = $response->isOk() ? "Вебхук успешно установлен." : "Ошибка при установке вебхука.";
    } elseif ($action === 'remove') {
        // Отключение вебхука
        $response = $telegram->deleteWebhook();
        $statusMessage = $response->isOk() ? "Вебхук успешно отключен." : "Ошибка при отключении вебхука.";
    }
} catch (Exception $e) {
    $response = "Ошибка: " . $e->getMessage();
}

$webhookInfo = Request::getWebhookInfo();
$webhookInfoResponse = json_encode($webhookInfo->getResult(), JSON_PRETTY_PRINT);

$currentWebhookUrl = $webhookInfo->getResult()->url ?? 'Вебхук не установлен';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Webhook Info</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 20px;
            max-width: 600px;
            width: 100%;
            text-align: center; /* Центрируем содержимое */
        }
        h1 {
            font-size: 24px;
            margin-bottom: 10px;
            text-align: center;
        }
        p {
            font-size: 14px;
            color: #555;
            text-align: center;
            margin-bottom: 10px;
        }
        .btn {
            display: inline-block;
            margin: 10px 5px; /* Небольшие отступы между кнопками */
            padding: 10px 20px;
            font-size: 14px;
            color: #fff;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #dc3545;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .status {
            margin-bottom: 20px;
        }
        .response {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f1f1f1;
            font-size: 14px;
            text-align: center;        
        }
		.webhook-info {
            background-color: #f8f9fa;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 20px;
        }

        .webhook-info-pre {
            white-space: pre-wrap; /* Обеспечивает сохранение форматирования JSON и перенос текста */
            word-break: break-all; /* Дополнительно обеспечивает перенос длинных слов */
        }
        .webhook-url {
            color: green;
        }

    </style>
</head>
<body>
    <div class="container">
        <h1>Webhook Info</h1>
        <a href="index.php" class="button">Главная</a>
        <div class="status">
            <p>
                <strong>Текущий вебхук:</strong>
                <?php if (!empty($currentWebhookUrl)): ?>
                    <span class="webhook-url"><?= $currentWebhookUrl ?></span>
                <?php else: ?>
                    Вебхук не установлен
                <?php endif; ?>
            </p>
            <?php if ($statusMessage): ?>
                <p><strong>Статус:</strong> <?= $statusMessage ?></p>
            <?php endif; ?>
        </div>
     
        <form action="" method="post">
            <button type="submit" name="action" value="set" class="btn">Подключить</button>
            <button type="submit" name="action" value="remove" class="btn btn-secondary">Отключить</button>
        </form>
        <div class="response"><?= $response?></div>
        <div class="webhook-info">
            <h2>Ответ API Telegram</h2>
            <pre class="webhook-info-pre"><?= $webhookInfoResponse ?></pre>
        </div>
    </div>
</body>
</html>
