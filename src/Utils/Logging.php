<?php
// TgGroqChat_v3/src/Utils/Logging.php

namespace App\Utils;

/**
 * Логирование событий и ошибок
 */
class Logging
{
    /**
     * Функция для декодирования Unicode
     */
    public static function decodeUnicode(string $str): string
    {
        $decoded = preg_replace_callback(
            '/\\\\u([0-9a-fA-F]{4})/',
            function ($match) {
                return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
            },
            $str
        );
    
        // Если декодирование не удалось, возвращаем исходную строку
        return $decoded !== null ? $decoded : $str;
    }
    

    /**
     * Функция для записи логов в файл с проверкой уровня логирования
     */
    public static function writeToLog(string $message, string $level = 'INFO', string $file = 'unknown'): void
    {
        if (!defined('ENABLE_LOGGING') || !ENABLE_LOGGING) {
            return;
        }

        $allowedLevels = [
            'ALL' => 0,
            'ERROR' => 1,
            'DEBUG' => 2,
            'WARNING' => 3,
            'INFO' => 4,
        ];

        $currentLogLevel = $allowedLevels[strtoupper(LOG_LEVEL)] ?? 4;
        $messageLogLevel = $allowedLevels[strtoupper($level)] ?? 4;

        // Ошибки логируются всегда, если логирование включено
        if ($level === 'ERROR' || $currentLogLevel === 0 || $messageLogLevel <= $currentLogLevel) {
            $logFile = LOG_FILE; // Путь для лог-файла
            $dateTime = date('Y-m-d H:i:s');

            // Если сообщение — массив, преобразуем его в строку
            if (is_array($message)) {
                $message = print_r($message, true);
            }

            $decodedMessage = self::decodeUnicode($message);
            //$decodedMessage = $message;
            $logMessage = "[$dateTime] [$level] $file | $decodedMessage\n";

            if (!file_exists($logFile)) {
                file_put_contents($logFile, '');
            }

            // Создаем файл, если он не существует, и записываем в него
            file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        }
    }
}
