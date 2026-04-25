<?php
session_start();

header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

$dsn = 'mysql:host=127.0.0.1;dbname=web_lab_3;charset=utf8';
$username = 'web_lab_user';
$password = '1111';

function get_pdo($dsn, $username, $password) {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function stop_with_db_error($e) {
    error_log($e->getMessage());
    http_response_code(500);
    print 'Внутренняя ошибка сервера.';
    exit();
}

function send_auth_request() {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin panel"');
    header('Content-Type: text/html; charset=UTF-8');
    print '<h1>401 Требуется авторизация</h1>';
    exit();
}

function require_admin($pdo) {
    if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
        send_auth_request();
    }

    $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE login = ?');
    $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
        send_auth_request();
    }
}

function get_csrf_token() {
    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['admin_csrf_token'];
}

function check_csrf_token($token) {
    if (empty($_SESSION['admin_csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['admin_csrf_token'], $token);
}

function normalize_fio($fio) {
    $fio = preg_replace('/\s+/u', ' ', $fio);
    return trim($fio);
}

function get_languages($pdo) {
    $stmt = $pdo->query('SELECT id, name FROM programming_languages ORDER BY id');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_application($pdo, $id) {
    $stmt = $pdo->prepare('SELECT * FROM applications WHERE id = ?');
    $stmt->execute([$id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT language_id FROM application_languages WHERE application_id = ?');
    $stmt->execute([$id]);

    $application['languages'] = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $application['languages'][] = (string)$row['language_id'];
    }

    if (!empty($application['birth_date'])) {
        $date = DateTime::createFromFormat('Y-m-d', $application['birth_date']);
        if ($date) {
            $application['birth_date'] = $date->format('d.m.Y');
        }
    }

    return $application;
}

function get_applications($pdo) {
    $stmt = $pdo->query('
        SELECT
            a.id,
            a.login,
            a.fio,
            a.phone,
            a.email,
            a.birth_date,
            a.gender,
            a.biography,
            a.contract_accepted,
            a.created_at,
            GROUP_CONCAT(pl.name ORDER BY pl.id SEPARATOR ", ") AS languages
        FROM applications a
        LEFT JOIN application_languages al ON a.id = al.application_id
        LEFT JOIN programming_languages pl ON pl.id = al.language_id
        GROUP BY a.id
        ORDER BY a.id DESC
    ');

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_language_statistics($pdo) {
    $stmt = $pdo->query('
        SELECT
            pl.id,
            pl.name,
            COUNT(al.application_id) AS users_count
        FROM programming_languages pl
        LEFT JOIN application_languages al ON pl.id = al.language_id
        GROUP BY pl.id, pl.name
        ORDER BY pl.id
    ');

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function validate_form($fio, $phone, $email, $birth_date_input, $gender, $biography, $contract_accepted, $languages, $all_languages) {
    $errors = [];
    $birth_date = null;

    if ($fio === '') {
        $errors['fio'] = 'Поле ФИО обязательно для заполнения.';
    } elseif (mb_strlen($fio) > 150) {
        $errors['fio'] = 'ФИО не должно быть длиннее 150 символов.';
    } elseif (!preg_match('/^[A-Za-zА-Яа-яЁё]+(?:[ -][A-Za-zА-Яа-яЁё]+)*$/u', $fio)) {
        $errors['fio'] = 'В поле ФИО допустимы только буквы, пробелы и дефис.';
    }

    if ($phone === '') {
        $errors['phone'] = 'Поле Телефон обязательно для заполнения.';
    } elseif (!preg_match('/^\+?[0-9\s\-\(\)]+$/', $phone)) {
        $errors['phone'] = 'В поле Телефон допустимы только цифры, пробел, +, круглые скобки и дефис.';
    }

    if ($email === '') {
        $errors['email'] = 'Поле Email обязательно для заполнения.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email.';
    }

    if ($birth_date_input === '') {
        $errors['birth_date'] = 'Поле Дата рождения обязательно для заполнения.';
    } elseif (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $birth_date_input)) {
        $errors['birth_date'] = 'Дата рождения должна быть в формате ДД.ММ.ГГГГ.';
    } else {
        $date = DateTime::createFromFormat('d.m.Y', $birth_date_input);
        $date_errors = DateTime::getLastErrors();
        $has_date_errors = false;

        if ($date_errors !== false) {
            if ($date_errors['warning_count'] > 0 || $date_errors['error_count'] > 0) {
                $has_date_errors = true;
            }
        }

        if (!$date || $has_date_errors) {
            $errors['birth_date'] = 'Введите существующую дату в формате ДД.ММ.ГГГГ.';
        } else {
            $birth_date = $date->format('Y-m-d');
        }
    }

    if ($gender !== 'male' && $gender !== 'female') {
        $errors['gender'] = 'Нужно выбрать допустимый пол.';
    }

    $allowed_languages = [];

    foreach ($all_languages as $language) {
        $allowed_languages[] = (int)$language['id'];
    }

    if (!is_array($languages) || count($languages) === 0) {
        $errors['languages'] = 'Нужно выбрать хотя бы один язык программирования.';
    } else {
        foreach ($languages as $language_id) {
            if (!in_array((int)$language_id, $allowed_languages, true)) {
                $errors['languages'] = 'Выбран недопустимый язык программирования.';
                break;
            }
        }
    }

    if ($biography === '') {
        $errors['biography'] = 'Поле Биография обязательно для заполнения.';
    } elseif (!preg_match('/^[A-Za-zА-Яа-яЁё0-9\s\.,!?\-:;"()«»\n\r]+$/u', $biography)) {
        $errors['biography'] = 'В поле Биография допустимы буквы, цифры, пробелы, перенос строки и знаки препинания . , ! ? - : ; " ( ) « »';
    }

    if ($contract_accepted !== '1') {
        $errors['contract_accepted'] = 'Необходимо ознакомиться с контрактом.';
    }

    return [$errors, $birth_date];
}

function update_application($pdo, $id, $fio, $phone, $email, $birth_date, $gender, $biography, $contract_accepted, $languages) {
    $stmt = $pdo->prepare('UPDATE applications SET fio = ?, phone = ?, email = ?, birth_date = ?, gender = ?, biography = ?, contract_accepted = ? WHERE id = ?');
    $stmt->execute([$fio, $phone, $email, $birth_date, $gender, $biography, $contract_accepted, $id]);

    $stmt = $pdo->prepare('DELETE FROM application_languages WHERE application_id = ?');
    $stmt->execute([$id]);

    $stmt = $pdo->prepare('INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)');

    foreach ($languages as $language_id) {
        $stmt->execute([$id, (int)$language_id]);
    }
}

try {
    $pdo = get_pdo($dsn, $username, $password);
    require_admin($pdo);
} catch (PDOException $e) {
    stop_with_db_error($e);
}

$message = '';
$errors = [];
$edit_application = null;
$csrf_token = get_csrf_token();
$languages = get_languages($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Ошибка CSRF-защиты. Обновите страницу и попробуйте снова.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM applications WHERE id = ?');
            $stmt->execute([$id]);
            $message = 'Запись удалена.';
        }
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);

        $fio = normalize_fio(trim($_POST['fio'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $birth_date_input = trim($_POST['birth_date'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $biography = trim($_POST['biography'] ?? '');
        $selected_languages = $_POST['languages'] ?? [];
        $contract_accepted = isset($_POST['contract_accepted']) ? '1' : '0';

        [$errors, $birth_date] = validate_form($fio, $phone, $email, $birth_date_input, $gender, $biography, $contract_accepted, $selected_languages, $languages);

        if (empty($errors) && $id > 0) {
            update_application($pdo, $id, $fio, $phone, $email, $birth_date, $gender, $biography, 1, $selected_languages);
            $message = 'Запись обновлена.';
        } else {
            $edit_application = [
                'id' => $id,
                'fio' => $fio,
                'phone' => $phone,
                'email' => $email,
                'birth_date' => $birth_date_input,
                'gender' => $gender,
                'biography' => $biography,
                'contract_accepted' => $contract_accepted,
                'languages' => $selected_languages
            ];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_application = get_application($pdo, $edit_id);

    if (!$edit_application) {
        $message = 'Запись не найдена.';
    }
}

$applications = get_applications($pdo);
$statistics = get_language_statistics($pdo);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админка</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page admin-page">
    <div class="form-container admin-container">
        <h1>Админка</h1>

        <p>
            <a class="back-link" href="index.php">Вернуться к форме</a>
        </p>

        <?php if ($message !== ''): ?>
            <div class="message success-message">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($edit_application)): ?>
            <div class="message login-box">
                <h2>Редактирование записи №<?php echo htmlspecialchars($edit_application['id']); ?></h2>

                <?php if (!empty($errors)): ?>
                    <div class="message error-message">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="admin.php" method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_application['id']); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                    <div class="form-group">
                        <label for="fio">ФИО</label>
                        <input type="text" id="fio" name="fio" maxlength="150" value="<?php echo htmlspecialchars($edit_application['fio'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">Телефон</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($edit_application['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="text" id="email" name="email" value="<?php echo htmlspecialchars($edit_application['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="birth_date">Дата рождения</label>
                        <input type="text" id="birth_date" name="birth_date" maxlength="10" value="<?php echo htmlspecialchars($edit_application['birth_date'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Пол</label>
                        <div class="radio-group">
                            <label class="inline-label">
                                <input type="radio" name="gender" value="male" <?php echo (($edit_application['gender'] ?? '') === 'male') ? 'checked' : ''; ?>>
                                Мужской
                            </label>
                            <label class="inline-label">
                                <input type="radio" name="gender" value="female" <?php echo (($edit_application['gender'] ?? '') === 'female') ? 'checked' : ''; ?>>
                                Женский
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Любимые языки программирования</label>
                        <div class="checkbox-list">
                            <?php foreach ($languages as $language): ?>
                                <label class="inline-label">
                                    <input
                                        type="checkbox"
                                        name="languages[]"
                                        value="<?php echo htmlspecialchars($language['id']); ?>"
                                        <?php echo in_array((string)$language['id'], array_map('strval', $edit_application['languages'] ?? []), true) ? 'checked' : ''; ?>
                                    >
                                    <?php echo htmlspecialchars($language['name']); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="biography">Биография</label>
                        <textarea id="biography" name="biography" rows="6"><?php echo htmlspecialchars($edit_application['biography'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group checkbox-group">
                        <label class="inline-label">
                            <input type="checkbox" name="contract_accepted" value="1" <?php echo (($edit_application['contract_accepted'] ?? '') == '1') ? 'checked' : ''; ?>>
                            С контрактом ознакомлен(а)
                        </label>
                    </div>

                    <button type="submit">Сохранить изменения</button>
                </form>
            </div>
        <?php endif; ?>

        <h2>Статистика по языкам</h2>

        <table class="admin-table">
            <thead>
            <tr>
                <th>Язык</th>
                <th>Количество пользователей</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($statistics as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['users_count']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Все отправленные данные</h2>

        <table class="admin-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Логин</th>
                <th>ФИО</th>
                <th>Телефон</th>
                <th>Email</th>
                <th>Дата рождения</th>
                <th>Пол</th>
                <th>Языки</th>
                <th>Биография</th>
                <th>Контракт</th>
                <th>Дата создания</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($applications as $application): ?>
                <tr>
                    <td><?php echo htmlspecialchars($application['id']); ?></td>
                    <td><?php echo htmlspecialchars($application['login'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($application['fio']); ?></td>
                    <td><?php echo htmlspecialchars($application['phone']); ?></td>
                    <td><?php echo htmlspecialchars($application['email']); ?></td>
                    <td><?php echo htmlspecialchars($application['birth_date']); ?></td>
                    <td><?php echo htmlspecialchars($application['gender'] === 'male' ? 'Мужской' : 'Женский'); ?></td>
                    <td><?php echo htmlspecialchars($application['languages'] ?? ''); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($application['biography'])); ?></td>
                    <td><?php echo $application['contract_accepted'] ? 'Да' : 'Нет'; ?></td>
                    <td><?php echo htmlspecialchars($application['created_at']); ?></td>
                    <td>
                        <a class="admin-action-link" href="admin.php?edit=<?php echo htmlspecialchars($application['id']); ?>">Редактировать</a>

                        <form action="admin.php" method="POST" class="inline-form">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($application['id']); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <button type="submit" class="delete-button">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($applications)): ?>
                <tr>
                    <td colspan="12">Записей пока нет.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
