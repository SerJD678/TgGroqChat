<?php
// TgGroqChat_v3/src/Utils/Database.php

namespace App\Utils;

use PDO;
use PDOException;

class Database
{
    /**
     * Экземпляр PDO для подключения к базе данных
     */
    private static ?PDO $pdo = null;

    /**
     * Подключение к базе данных
     *
     * @return PDO
     * @throws PDOException
     */
    public static function connect(): PDO
    {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            try {
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                Logging::writeToLog("Подключение к базе данных успешно установлено.", 'DEBUG', 'Database.php');
            } catch (PDOException $e) {
                Logging::writeToLog("Ошибка подключения к базе данных: " . $e->getMessage(), 'ERROR', 'Database.php');
                throw $e;
            }
        }

        return self::$pdo;
    }

    /**
     * Подготовка SQL-запроса
     *
     * @param string $query
     * @return \PDOStatement
     */
    public static function prepare(string $query)
    {
        return self::connect()->prepare($query);
    }

    /**
     * Получение данных токена авторизации
     *
     * @param string $token Токен авторизации
     * @return array|null Данные токена или null, если токен не найден
     */
    public static function getTokenData(string $token): ?array
    {
        $query = "SELECT * FROM auth_tokens WHERE token = ? AND expires_at > NOW() AND is_used = 0";
        $stmt = self::prepare($query);

        try {
            $stmt->execute([$token]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            Logging::writeToLog("Ошибка получения данных токена: " . $e->getMessage(), 'ERROR', 'Database.php');
            throw $e;
        }
    }

    /**
     * Пометка токена как использованного
     *
     * @param string $token Токен авторизации
     * @return bool Успешность операции
     */
    public static function markTokenAsUsed(string $token): bool
    {
        $query = "UPDATE auth_tokens SET is_used = 1 WHERE token = ?";
        $stmt = self::prepare($query);

        try {
            return $stmt->execute([$token]);
        } catch (PDOException $e) {
            Logging::writeToLog("Ошибка при отметке токена как использованного: " . $e->getMessage(), 'ERROR', 'Database.php');
            throw $e;
        }
    }


    /**
     * Удаление токена авторизации
     *
     * @param string $token Токен авторизации
     * @return bool Успешность операции
     */
    public static function deleteToken(string $token): bool
    {
        $query = "DELETE FROM auth_tokens WHERE token = ?";
        $stmt = self::prepare($query);


        try {
            Logging::writeToLog("Удаляемый токен: $token", 'DEBUG', 'Database.php');
            Logging::writeToLog("Выполняемый запрос: $query", 'DEBUG', 'Database.php');
            Logging::writeToLog("Параметры: " . json_encode([$token]), 'DEBUG', 'Database.php');

            return $stmt->execute([$token]);
        } catch (PDOException $e) {
            Logging::writeToLog("Ошибка удаления токена: " . $e->getMessage(), 'ERROR', 'Database.php');
            throw $e;
        }
    }

    /**
     * Вставка нового токена авторизации
     *
     * @param int $user_id Идентификатор пользователя
     * @return string Сгенерированный токен
     */
    public static function insertAuthToken(int $user_id): string
    {
        $token = bin2hex(random_bytes(16));
        $expires_at = date('Y-m-d H:i:s', time() + 600); // 10 минут

        $query = "INSERT INTO auth_tokens (token, user_id, expires_at, is_used) VALUES (?, ?, ?, 0)";
        $stmt = self::prepare($query);

        Logging::writeToLog("Добавляемый токен: $token", 'DEBUG', 'Database.php');
        Logging::writeToLog("Выполняемый запрос: $query", 'DEBUG', 'Database.php');
        Logging::writeToLog("Параметры: " . json_encode([$token, $user_id, $expires_at]), 'DEBUG', 'Database.php');
        
        
        try {            
            $result = $stmt->execute([$token, $user_id, $expires_at]);
            if ($result) {
                Logging::writeToLog("Токен успешно добавлен в базу данных.", 'DEBUG', 'Database.php');
            } else {
                Logging::writeToLog("Ошибка выполнения запроса, возвращено false.", 'ERROR', 'Database.php');
            }
            return $token;
        } catch (PDOException $e) {
            Logging::writeToLog("Ошибка вставки токена авторизации: " . $e->getMessage(), 'ERROR', 'Database.php');
            throw $e;
        }
        
    }
    
    /**
     * Регистрация нового пользователя
     *
     * @param int $id Идентификатор пользователя
     * @param string|null $username Имя пользователя
     * @param string|null $firstName Имя
     * @param string|null $languageCode Язык пользователя
     * @param int|null $referralId Идентификатор реферала
     * @return bool Успешность операции
     */
    public static function registerUser(int $id, ?string $username, ?string $firstName, ?string $languageCode, ?int $referralId = null): bool
    {
        $query = "
            INSERT INTO users (id, username, first_name, language_code, referral_id)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                username = VALUES(username),
                first_name = VALUES(first_name),
                language_code = VALUES(language_code),
                referral_id = VALUES(referral_id)
        ";

        $stmt = self::prepare($query);

        try {
            return $stmt->execute([$id, $username, $firstName, $languageCode, $referralId]);
        } catch (PDOException $e) {
            Logging::writeToLog("Ошибка регистрации пользователя: " . $e->getMessage(), 'ERROR', 'Database.php');
            throw $e;
        }
    }

    /**
     * Проверка существования пользователя
     *
     * @param int $id Идентификатор пользователя
     * @return bool True, если пользователь существует
     */
    public static function userExists(int $id): bool
    {
        $query = "SELECT COUNT(*) FROM users WHERE id = ?";

        $stmt = self::prepare($query);

        try {
            $stmt->execute([$id]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            Logging::writeToLog("Ошибка проверки существования пользователя: " . $e->getMessage(), 'ERROR', 'Database.php');
            throw $e;
        }
    }

    /**
     * Вставка данных в таблицу
     *
     * @param string $tableName Имя таблицы
     * @param array $data Ассоциативный массив данных [колонка => значение]
     * @return bool Успешность операции
     */
    public static function insert(string $tableName, array $data): bool
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $query = "INSERT INTO {$tableName} ({$columns}) VALUES ({$placeholders})";

        $stmt = self::prepare($query);

        try {
            return $stmt->execute(array_values($data));
        } catch (PDOException $e) {
            Logging::writeToLog("Ошибка вставки данных: " . $e->getMessage(), 'ERROR', 'Database.php');
            throw $e;
        }
    }

    /**
     * Вставка нескольких строк в таблицу chat_history
     *
     * @param array $rows Массив строк для вставки. Каждая строка — ассоциативный массив [колонка => значение]
     * @return bool Успешность операции
     */
    public static function insertChatHistoryMultiple(array $rows): bool
    {
        if (empty($rows)) {
            throw new \InvalidArgumentException("Данные для вставки не могут быть пустыми.");
        }

        $columns = array_keys(reset($rows));
        $columnsString = implode(', ', $columns);

        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($rows), $placeholders));

        $query = "INSERT INTO chat_history ({$columnsString}) VALUES {$allPlaceholders}";

        $values = [];
        foreach ($rows as $row) {
            $values = array_merge($values, array_values($row));
        }

        $stmt = self::prepare($query);

        try {
            return $stmt->execute($values);
        } catch (PDOException $e) {
            Logging::writeToLog("Ошибка вставки данных: " . $e->getMessage(), 'ERROR', 'Database.php');
            throw $e;
        }
    }

    /**
     * Получение роли пользователя по chat_id
     *
     * @param int $chatId Идентификатор чата
     * @return string|null Возвращает роль пользователя или null, если роль не найдена
     */
    public static function getGroqRoleByChatId(int $chatId): ?string
    {
        $query = "SELECT role FROM groq_roles WHERE chat_id = ?";

        $stmt = self::prepare($query);

        try {
            $stmt->execute([$chatId]);
            return $stmt->fetchColumn() ?: null; // Возвращает роль или null, если данных нет
        } catch (PDOException $e) {
            Logging::writeToLog("Ошибка получения Groq роли для chat_id {$chatId}: " . $e->getMessage(), 'ERROR', 'Database.php');
            throw $e;
        }
    }

    /**
     * Вставка или обновление роли пользователя в таблице groq_roles
     *
     * @param int $chatId Идентификатор чата
     * @param string $groqRole Роль пользователя
     * @return bool Успешность операции
     */
    public static function upsertGroqRole(int $chatId, string $groqRole): bool
    {
        $query = "
            INSERT INTO groq_roles (chat_id, role) 
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE role = VALUES(role)
        ";

        $stmt = self::prepare($query);

        try {
            return $stmt->execute([$chatId, $groqRole]);
        } catch (PDOException $e) {
            Logging::writeToLog("Ошибка вставки или обновления Groq роли для chat_id {$chatId}: " . $e->getMessage(), 'ERROR', 'Database.php');
            throw $e;
        }
    }

    
    /**
     * Получение контекста сообщений для чата
     *
     * @param int $chatId Идентификатор чата
     * @param int $limit Количество последних сообщений для выборки
     * @return array Массив сообщений с ролью и текстом
     */
    public static function getChatHistoryByChatId(int $chatId, int $limit, bool $includeTimestamp = false): array
    {
        // Убедимся, что $limit — положительное число
        $limit = max(1, $limit); 
    
        // Встраиваем $limit непосредственно в SQL-запрос
        $query = "SELECT role, message" . ($includeTimestamp ? ", timestamp" : "") . " FROM chat_history WHERE chat_id = ? ORDER BY timestamp DESC LIMIT {$limit}";
    
        $stmt = self::prepare($query);
    
        try {
            $stmt->execute([$chatId]);
            $req = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            Logging::writeToLog("История чата для chat_id {$chatId}: " . json_encode($req), 'DEBUG', 'Database.php');
            return $req;
        } catch (PDOException $e) {
            Logging::writeToLog("Ошибка получения истории чата для chat_id {$chatId}: " . $e->getMessage(), 'ERROR', 'Database.php');
            throw $e;
        }
    }


    


}
