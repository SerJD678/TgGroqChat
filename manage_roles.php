<?php
// manage_roles.php
require_once __DIR__ . '/src/Utils/Authorization.php'; 
use App\Utils\Authorization; // Выполнение проверки авторизации 
Authorization::checkAuthorization();

require_once __DIR__ . '/config.php';

// Загружаем файл ролей
$rolesFile = GROQ_ROLES_FILE;
$rolesData = json_decode(file_get_contents($rolesFile), true);
$roles = $rolesData['roles'] ?? [];

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_role'])) {
        $roleId = $_POST['role_id'] ?? '';
        $roleNameEn = $_POST['role_name_en'] ?? '';
        $roleNameRu = $_POST['role_name_ru'] ?? '';
        $roleNameUk = $_POST['role_name_uk'] ?? '';
        $rolePromptEn = $_POST['role_prompt_en'] ?? '';
        $rolePromptRu = $_POST['role_prompt_ru'] ?? '';
        $rolePromptUk = $_POST['role_prompt_uk'] ?? '';

        // Проверка заполнения всех полей
        if (empty($roleId) || empty($roleNameEn) || empty($roleNameRu) || empty($roleNameUk) ||
            empty($rolePromptEn) || empty($rolePromptRu) || empty($rolePromptUk)) {
            $error = 'Все поля должны быть заполнены.';
        } else {
            // Добавление или обновление роли
            $existingRoleKey = array_search($roleId, array_column($roles, 'id'));
            $newRole = [
                'id' => $roleId,
                'name' => [
                    'en' => $roleNameEn,
                    'ru' => $roleNameRu,
                    'uk' => $roleNameUk,
                ],
                'prompt' => [
                    'en' => $rolePromptEn,
                    'ru' => $rolePromptRu,
                    'uk' => $rolePromptUk,
                ]
            ];

            if ($existingRoleKey !== false) {
                $roles[$existingRoleKey] = $newRole;
            } else {
                $roles[] = $newRole;
            }

            $rolesData['roles'] = $roles;
            file_put_contents($rolesFile, json_encode($rolesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $success = 'Роль успешно сохранена.';
        }
    } elseif (isset($_POST['delete_role'])) {
        $roleId = $_POST['role_id'] ?? '';
        $existingRoleKey = array_search($roleId, array_column($roles, 'id'));
        if ($existingRoleKey !== false) {
            unset($roles[$existingRoleKey]);
            $roles = array_values($roles); // Перенумерация массива
            $rolesData['roles'] = $roles;
            file_put_contents($rolesFile, json_encode($rolesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $success = 'Роль успешно удалена.';
        } else {
            $error = 'Роль не найдена.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление ролями Groq</title>    
    <style>
        /* style.css */
        body {
            font-family: 'Roboto', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        h1 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        table th {
            background-color: #f8f8f8;
            color: #333;
            font-weight: bold;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .table-actions {
            display: flex;
            gap: 10px;
        }

        .button {
            display: inline-block;
            margin: 10px 0;
            padding: 10px 20px;
            color: #fff;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: #0056b3;
        }

        .button-danger {
            background-color: #dc3545;
        }

        .button-danger:hover {
            background-color: #c82333;
        }

        .button-secondary {
            background-color: #6c757d;
        }

        .button-secondary:hover {
            background-color: #5a6268;
        }

        form#role-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
        }

        form#role-form input[type="text"],
        form#role-form textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            /*resize: none; /* Отключаем возможность изменения размера пользователем */
        }

        form#role-form label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        form#role-form button {
            max-width: 150px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Управление ролями Groq</h1> 
        <a href="index.php" class="button">Главная</a>

        <?php if (!empty($error)): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <p style="color: green;"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название (EN)</th>
                    <th>Название (RU)</th>
                    <th>Название (UK)</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $role): ?>
                    <tr>
                        <td><?= htmlspecialchars($role['id']) ?></td>
                        <td><?= htmlspecialchars($role['name']['en']) ?></td>
                        <td><?= htmlspecialchars($role['name']['ru']) ?></td>
                        <td><?= htmlspecialchars($role['name']['uk']) ?></td>
                        <td class="table-actions">
                            <button class="button" onclick="editRole(<?= htmlspecialchars(json_encode($role)) ?>)">Редактировать</button>
                            <form method="post" style="display: inline-block;">
                                <input type="hidden" name="role_id" value="<?= htmlspecialchars($role['id']) ?>">
                                <button class="button button-danger" name="delete_role" onclick="return confirm('Вы уверены, что хотите удалить эту роль?');">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button class="button" onclick="addRole()">Добавить роль</button>

        <form method="post" id="role-form" style="display: none; margin-top: 20px;">
            <h3 id="form-title">Добавить роль</h3>
            <label>ID роли:<br><input type="text" name="role_id" id="role_id"></label>
            <label>Название (EN):<br><input type="text" name="role_name_en" id="role_name_en"></label>
            <label>Название (RU):<br><input type="text" name="role_name_ru" id="role_name_ru"></label>
            <label>Название (UK):<br><input type="text" name="role_name_uk" id="role_name_uk"></label>
            <label>Системный промпт (EN):<br><textarea name="role_prompt_en" id="role_prompt_en" rows="3"></textarea></label>
            <label>Системный промпт (RU):<br><textarea name="role_prompt_ru" id="role_prompt_ru" rows="3"></textarea></label>
            <label>Системный промпт (UK):<br><textarea name="role_prompt_uk" id="role_prompt_uk" rows="3"></textarea></label>
            <div style="display: flex; gap: 10px;">
                <button class="button" name="save_role">Сохранить</button>
                <button class="button button-secondary" type="button" onclick="cancelForm()">Отмена</button>
            </div>
        </form>
    </div>

    <script>
        function editRole(role) {
            document.getElementById('role-form').style.display = 'block';
            document.getElementById('form-title').innerText = 'Редактировать роль';
            document.getElementById('role_id').value = role.id;
            document.getElementById('role_name_en').value = role.name.en;
            document.getElementById('role_name_ru').value = role.name.ru;
            document.getElementById('role_name_uk').value = role.name.uk;
            document.getElementById('role_prompt_en').value = role.prompt.en;
            document.getElementById('role_prompt_ru').value = role.prompt.ru;
            document.getElementById('role_prompt_uk').value = role.prompt.uk;

            document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', e => {
                e.target.style.height = 'auto';
                e.target.style.height = (e.target.scrollHeight) + 'px';
            });

            // Автоматическая подстройка высоты при загрузке страницы
            textarea.dispatchEvent(new Event('input'));
        });
        }

        function addRole() {
            document.getElementById('role-form').style.display = 'block';
            document.getElementById('form-title').innerText = 'Добавить роль';
            document.getElementById('role_id').value = '';
            document.getElementById('role_name_en').value = '';
            document.getElementById('role_name_ru').value = '';
            document.getElementById('role_name_uk').value = '';
            document.getElementById('role_prompt_en').value = '';
            document.getElementById('role_prompt_ru').value = '';
            document.getElementById('role_prompt_uk').value = '';
        }

        function cancelForm() {
            document.getElementById('role-form').style.display = 'none';
        }

        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', e => {
                e.target.style.height = 'auto';
                e.target.style.height = (e.target.scrollHeight) + 'px';
            });

            // Автоматическая подстройка высоты при загрузке страницы
            textarea.dispatchEvent(new Event('input'));
        });
    </script>
</body>
</html>
