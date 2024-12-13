<?php
//  TgGroqChat_v3/src/Groq/GroqRole.php 

namespace App\Groq;

use App\Utils\Logging;

class GroqRole
{
    /**
     * Получение системного промпта в зависимости от роли и языка
     *
     * @param string $userLanguage Язык пользователя (например, 'en', 'ru', 'uk')
     * @param string $role Идентификатор роли (например, 'default', 'smm')
     * @return string Системный промпт с языковой меткой
     */
    public static function getSystemPrompt(string $userLanguage, string $role): string
    {
        // Логирование вызова функции
        Logging::writeToLog('getSystemPrompt вызван с параметрами: ' . json_encode(['userLanguage' => $userLanguage, 'role' => $role]), 'INFO', 'GroqRole.php');

        // Путь к JSON-файлу с системными промптами
        $rolesFile = GROQ_ROLES_FILE;

        // Загружаем роли из JSON
        $rolesData = json_decode(file_get_contents($rolesFile), true);
        $prompts = $rolesData['roles'] ?? [];

        // Поиск нужной роли
        $prompt = '';
        foreach ($prompts as $roleData) {
            if ($roleData['id'] === $role) {
                $prompt = $roleData['prompt'][$userLanguage] ?? $roleData['prompt']['en'];
                break;
            }
        }

        // Языковая метка
        $languageTag = [
            'ru' => ' Отвечай на Русском языке.',
            'uk' => ' Відповідь Українською мовою.',
            'en' => ' Answer in English.',
        ];

        return $prompt . ($languageTag[$userLanguage] ?? '');
    }

    /**
     * Получение имени роли на указанном языке
     *
     * @param string $role_id Идентификатор роли (например, 'default', 'smm')
     * @param string $language_code Код языка (например, 'en', 'ru', 'uk')
     * @return string Имя роли на языке пользователя или на английском
     */
    public static function getRoleName(string $role_id, string $language_code): string
    {
        // Логирование вызова функции
        Logging::writeToLog('getRoleName вызван с параметрами: ' . json_encode(['role_id' => $role_id, 'language_code' => $language_code]), 'INFO', 'GroqRole.php');

        // Путь к JSON-файлу с ролями
        $rolesFile = GROQ_ROLES_FILE;

        // Загружаем роли из JSON
        $rolesData = json_decode(file_get_contents($rolesFile), true)['roles'] ?? [];

        foreach ($rolesData as $role) {
            if ($role['id'] === $role_id) {
                // Поиск имени роли на указанном языке
                Logging::writeToLog('Имя роли: ' . $role['name'][$language_code] ?? $role['name']['en'], 'INFO', 'GroqRole.php');

                return $role['name'][$language_code] ?? $role['name']['en'];
            }
        }

        return $role_id;
    }
}