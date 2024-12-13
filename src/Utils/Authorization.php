<?
namespace App\Utils;

use App\Utils\Database;

class Authorization
{
    public static function authorize($token)
    {
        $tokenData = Database::getTokenData($token);
    
        if (!$tokenData) {
            // Токен не действителен или просрочен
            return false;
        }
    
        // Помечаем токен как использованный
        if (!Database::markTokenAsUsed($token)) {
            Logging::writeToLog("Не удалось пометить токен как использованный: $token", 'ERROR', 'Authorization.php');
            return false;
        }
    
        // Создание сессии на 24 часа
        session_start();
        $_SESSION['admin'] = true;
        $_SESSION['expiry_time'] = time() + 86400; // 24 часа
    
        return true;
    }
    

    public static function checkSession()
    {
        session_start();

        if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
            if (time() > $_SESSION['expiry_time']) {
                session_destroy();
                return false;
            }

            return true;
        }

        return false;
    }

    public static function blockPreviewBots(): void
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (strpos($user_agent, 'Telegram') !== false || strpos($user_agent, 'Facebook') !== false) {
            echo "Доступ к этому ресурсу невозможен.";
            exit();
        }
    }

    public static function checkAuthorization()
    {
        self::blockPreviewBots();

        

        // Проверка токена, если он передан в URL
        if (isset($_GET['token'])) {
            if (self::checkSession()) {
                header('Location: index.php');
                exit();                
            }
            
            $token = $_GET['token'];
            $confirm = $_GET['confirm'] ?? null;

            if ($confirm !== '1') {
                // Если нет подтверждения, выводим инструкцию
                echo '<p>Для завершения действия нажмите на подтверждающую ссылку:</p>';
                echo "<a href=\"?token=$token&confirm=1\">Подтвердить</a>";
                exit();
            }

            if (self::authorize($token)) {
                header('Location: index.php');
                exit();
            } else {
                echo '<p>Неверный или просроченный токен. Попробуйте снова.</p>';
                exit();
            }
        }

        // Проверка сессии
        if (!self::checkSession()) {
            echo '<p>Доступ закрыт.</p>';
            exit();
        }
    }
    

    public static function generateAuthToken($user_id)
    {
        return Database::insertAuthToken($user_id);
    }
}
