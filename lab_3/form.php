<?php
$dsn = 'mysql:host=localhost;port=8889;dbname=web_lab_3;charset=utf8';
$username = 'root';
$password = 'root';

$languages_list = [
    1 => 'Pascal',
    2 => 'C',
    3 => 'C++',
    4 => 'JavaScript',
    5 => 'PHP',
    6 => 'Python',
    7 => 'Java',
    8 => 'Haskell',
    9 => 'Clojure',
    10 => 'Prolog',
    11 => 'Scala',
    12 => 'Go'
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$fio = trim($_POST['fio'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$birth_date_input = trim($_POST['birth_date'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$biography = trim($_POST['biography'] ?? '');
$languages = $_POST['languages'] ?? [];
$contract_accepted = isset($_POST['contract_accepted']) ? 1 : 0;

$fio = preg_replace('/\s+/u', ' ', $fio);
$fio = trim($fio);

if ($fio === '') {
    $errors[] = 'Поле ФИО обязательно для заполнения.';
} elseif (mb_strlen($fio) > 150) {
    $errors[] = 'ФИО не должно быть длиннее 150 символов.';
} elseif (!preg_match('/^[A-Za-zА-Яа-яЁё]+(?:[ -][A-Za-zА-Яа-яЁё]+)*$/u', $fio)) {
    $errors[] = 'ФИО должно содержать только буквы, пробелы и дефис.';
}

if ($phone === '') {
    $errors[] = 'Поле Телефон обязательно для заполнения.';
} elseif (!preg_match('/^\+?[0-9\s\-\(\)]+$/', $phone)) {
    $errors[] = 'Телефон содержит недопустимые символы.';
}

if ($email === '') {
    $errors[] = 'Поле Email обязательно для заполнения.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Неверный формат email.';
}

$birth_date = null;

if ($birth_date_input === '') {
    $errors[] = 'Поле Дата рождения обязательно для заполнения.';
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
        $errors[] = 'Дата рождения должна быть в формате ДД.ММ.ГГГГ.';
    } else {
        $birth_date = $date->format('Y-m-d');
    }
}

if ($gender !== 'male' && $gender !== 'female') {
    $errors[] = 'Выбран недопустимый пол.';
}

$allowed_languages = array_keys($languages_list);

if (!is_array($languages) || count($languages) === 0) {
    $errors[] = 'Нужно выбрать хотя бы один язык программирования.';
} else {
    foreach ($languages as $language_id) {
        if (!in_array((int)$language_id, $allowed_languages, true)) {
            $errors[] = 'Выбран недопустимый язык программирования.';
            break;
        }
    }
}

if ($biography === '') {
    $errors[] = 'Поле Биография обязательно для заполнения.';
}

if ($contract_accepted !== 1) {
    $errors[] = 'Необходимо ознакомиться с контрактом.';
}

if (!empty($errors)) {
    echo '<!DOCTYPE html>';
    echo '<html lang="ru">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>Ошибки</title>';
    echo '<link rel="stylesheet" href="style.css">';
    echo '</head>';
    echo '<body>';
    echo '<div class="page">';
    echo '<div class="form-container">';
    echo '<div class="message error-message">';
    echo '<h2>Ошибки</h2>';
    echo '<ul>';

    foreach ($errors as $error) {
        echo '<li>' . htmlspecialchars($error) . '</li>';
    }

    echo '</ul>';
    echo '<a class="back-link" href="index.php">Вернуться назад</a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</body>';
    echo '</html>';
    exit();
}

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare('
        INSERT INTO applications (fio, phone, email, birth_date, gender, biography, contract_accepted)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');

    $stmt->execute([
        $fio,
        $phone,
        $email,
        $birth_date,
        $gender,
        $biography,
        $contract_accepted
    ]);

    $application_id = $pdo->lastInsertId();

    $language_stmt = $pdo->prepare('
        INSERT INTO application_languages (application_id, language_id)
        VALUES (?, ?)
    ');

    foreach ($languages as $language_id) {
        $language_stmt->execute([
            $application_id,
            (int)$language_id
        ]);
    }

    echo '<!DOCTYPE html>';
    echo '<html lang="ru">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>Успех</title>';
    echo '<link rel="stylesheet" href="style.css">';
    echo '</head>';
    echo '<body>';
    echo '<div class="page">';
    echo '<div class="form-container">';
    echo '<div class="message success-message">';
    echo '<h2>Данные успешно сохранены</h2>';
    echo '<a class="back-link" href="index.php">Вернуться к форме</a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</body>';
    echo '</html>';
} catch (PDOException $e) {
    echo '<!DOCTYPE html>';
    echo '<html lang="ru">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>Ошибка</title>';
    echo '<link rel="stylesheet" href="style.css">';
    echo '</head>';
    echo '<body>';
    echo '<div class="page">';
    echo '<div class="form-container">';
    echo '<div class="message error-message">';
    echo '<h2>Ошибка базы данных</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<a class="back-link" href="index.php">Вернуться назад</a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</body>';
    echo '</html>';
}