<?php
// index.php
require_once __DIR__ . '/src/Utils/Authorization.php'; 
require_once __DIR__ . '/config.php';

use App\Utils\Authorization; // Выполнение проверки авторизации 
use App\Utils\Database;
use App\Utils\Logging;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;

// Проверяем и создаём таблицы, если они отсутствуют
function initializeDatabase() {
    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id BIGINT PRIMARY KEY,
            username VARCHAR(255) DEFAULT NULL,
            first_name VARCHAR(255) DEFAULT NULL,
            language_code VARCHAR(10) DEFAULT NULL,
            referral_id BIGINT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS chat_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chat_id VARCHAR(255) DEFAULT NULL,
            role VARCHAR(20) DEFAULT NULL,
            message MEDIUMTEXT,
            timestamp TIMESTAMP NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS groq_roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chat_id VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'default',
            updated_at TIMESTAMP NOT NULL,
            UNIQUE KEY (chat_id)
        )",
        "CREATE TABLE IF NOT EXISTS  auth_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(255) NOT NULL,
            user_id INT NOT NULL,
            expires_at TIMESTAMP NOT NULL
        )",        
        "CREATE TABLE IF NOT EXISTS auth_tokens (
            token varchar(255) NOT NULL,
            user_id bigint(20) NOT NULL,
            expires_at datetime NOT NULL,
            is_used tinyint(1) DEFAULT 0,
            PRIMARY KEY (token)
        )"

    ];

    $db = Database::connect();
    foreach ($queries as $query) {
        $db->exec($query);
    }
}

initializeDatabase();
Authorization::checkAuthorization();

// Создание объекта Telegram
$telegram = new Telegram(TELEGRAM_BOT_API_TOKEN, TELEGRAM_BOT_USERNAME);

// Получаем данные для отображения
$userCount = Database::connect()->query("SELECT COUNT(*) FROM users")->fetchColumn();
$usersPerPage = 10;
$totalPages = ceil($userCount / $usersPerPage);
$currentPage = isset($_GET['page']) ? max(1, min($totalPages, (int)$_GET['page'])) : 1;
$offset = ($currentPage - 1) * $usersPerPage;

// Запрос на получение данных пользователей с датой регистрации и ролью
$query = "
    SELECT 
        u.id, 
        u.username, 
        u.first_name, 
        u.language_code, 
        u.created_at, 
        r.role 
    FROM users u
    LEFT JOIN groq_roles r ON u.id = r.chat_id
    LIMIT $usersPerPage OFFSET $offset
";
$users = Database::connect()->query($query)->fetchAll(PDO::FETCH_ASSOC);


$logFile = LOG_FILE;

// Обработка действий с веб-интерфейса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_clear_logs'])) {
        file_put_contents($logFile, '');
    } elseif (isset($_POST['confirm_clear_chat_history'])) {
        Database::connect()->exec("DELETE FROM chat_history");
    } elseif (isset($_POST['send_message'])) {
        $message = $_POST['broadcast_message'] ?? '';
        $users = Database::connect()->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($users as $userId) {
            sleep(1); // Задержка между сообщениями
            Request::sendMessage([
                'chat_id' => $userId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);
        }
    } elseif (isset($_POST['view_user_chat'])) {
        $userId = (int)$_POST['user_id'];
        $chatHistory = Database::getChatHistoryByChatId($userId, 50, true);
    } elseif (isset($_POST['confirm_clear_user_chat'])) {
        $userId = (int)$_POST['user_id'];
        $stmt = Database::prepare("DELETE FROM chat_history WHERE chat_id = ?");
        $stmt->execute([$userId]);
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
     <style> body { font-family: Arial, sans-serif; background-color: #f0f0f0; margin: 0; padding: 20px; } .container { max-width: 900px; margin: 0 auto; padding: 20px; background-color: #fff; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); } .button { display: inline-block; padding: 10px 15px; margin: 5px; border: none; background-color: #007bff; color: #fff; text-decoration: none; cursor: pointer; } .button:hover { background-color: #0056b3; } .danger { background-color: #dc3545; } .danger:hover { background-color: #c82333; } .table { width: 100%; border-collapse: collapse; } .table th, .table td { padding: 10px; border: 1px solid #ddd; } .table th { background-color: #f8f9fa; }      
     .log-container { overflow-x: auto; max-width: 100%; background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 20px; }     
     .message { border: 1px solid #ccc; padding: 10px; margin-bottom: 5px; border-radius: 5px; } .message.system { background-color: #f1f1f1; } .message.user { background-color: #e7f7ff; } .message.bot { background-color: #f0ffe7; } .message time { display: block; font-size: 0.8em; color: #666; } ul { list-style-type: none; /* Убирает маркеры списка */ padding-left: 0; /* Убирает отступ */ }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Panel</h1>        
        <h2>Пользователи: <?= $userCount ?></h2>        
        <form method="post">
            <a href="index.php" class="button">Главная</a>
            <button class="button" name="show_broadcast">Написать сообщение всем</button>
            <button class="button" name="view_logs">Посмотреть лог</button>
            
            <button class="button danger" name="clear_chat_history">Очистить историю чатов</button>
            <a href="WebhookInfo.php" target="_blank" class="button">Вебхук</a>
            <a href="manage_roles.php"  class="button">Управление ролями</a>
        </form>

        <?php if (isset($_POST['clear_logs']) || isset($_POST['clear_chat_history'])): ?>
            <h3>Вы уверены?</h3>
            <form method="post">
                <?php if (isset($_POST['clear_logs'])): ?>
                    <button class="button danger" name="confirm_clear_logs">Да, очистить логи</button>
                <?php endif; ?>
                <?php if (isset($_POST['clear_chat_history'])): ?>
                    <button class="button danger" name="confirm_clear_chat_history">Да, очистить историю чатов</button>
                <?php endif; ?>
                <button class="button" name="cancel">Отмена</button>
            </form>
        <?php endif; ?>

        <?php if (isset($_POST['show_broadcast'])): ?>
            <h3>Написать сообщение всем пользователям</h3>
            <form method="post">
                <textarea name="broadcast_message" rows="5" cols="50"></textarea><br>
                <button class="button" name="send_message">Отправить</button>
            </form>
        <?php endif; ?>

        <?php if (isset($_POST['view_logs'])): ?>
            <h3>Лог:</h3> 
            <form method="post">
                <button class="button danger" name="clear_logs">Очистить логи</button>
            </form>
            <div class="log-container"> 
			    <? $logs = file_exists($logFile) ? file_get_contents($logFile) : '';?>
                <pre><?= htmlspecialchars($logs) ?></pre> 
            </div>
        <?php endif; ?>

        <h3>Пользователи: </h3> 
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Имя</th>
                    <th>Язык</th>
                    <th>Дата регистрации</th>
                    <th>Роль</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['username'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($user['first_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($user['language_code'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($user['created_at'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($user['role'] ?? 'default') ?></td>
                        <td>
                            <form method="post" style="display:inline-block;">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button class="button" name="view_user_chat">История чата</button>
                            </form>
                            <form method="post" style="display:inline-block;">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button class="button danger" name="clear_user_chat">Очистить историю</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (isset($_POST['clear_user_chat'])): ?>
            <h3>Вы уверены, что хотите очистить историю чата пользователя ID: <?= htmlspecialchars($_POST['user_id']) ?>?</h3>
            <form method="post">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($_POST['user_id']) ?>">
                <button class="button danger" name="confirm_clear_user_chat">Да, очистить</button>
                <button class="button" name="cancel">Отмена</button>
            </form>
        <?php endif; ?>

        <?php if (isset($chatHistory)): ?>
            <h3>История чата пользователя ID: <?= htmlspecialchars($_POST['user_id']) ?></h3>
            <ul>
                <?php foreach (array_reverse($chatHistory) as $message): ?>
                    <li class="message <?= $message['role'] ?>">
                        <small><i><?= htmlspecialchars($message['timestamp']) ?> </i></small> |
                        <strong><?= htmlspecialchars($message['role']) ?>:</strong> <?= htmlspecialchars($message['message']) ?>
                        
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>


    </div>
</body>
</html>
