<?php
header('Content-Type: text/html; charset=UTF-8');

$dsn = 'mysql:host=127.0.0.1;dbname=web_lab_3;charset=utf8';
$username = 'web_lab_user';
$password = '1111';

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

function set_error_cookie($field, $message) {
    setcookie($field . '_error', '1', 0, '/');
    setcookie($field . '_message', $message, 0, '/');
}

function clear_error_cookie($field) {
    setcookie($field . '_error', '', time() - 3600, '/');
    setcookie($field . '_message', '', time() - 3600, '/');
}

function set_value_cookie($field, $value, $expire) {
    setcookie($field . '_value', $value, $expire, '/');
}

function normalize_fio($fio) {
    $fio = preg_replace('/\s+/u', ' ', $fio);
    return trim($fio);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $messages = [];
    $errors = [];
    $error_messages = [];
    $values = [];

    $fields = [
        'fio',
        'phone',
        'email',
        'birth_date',
        'gender',
        'biography',
        'contract_accepted'
    ];

    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
        $error_messages[$field] = !empty($_COOKIE[$field . '_message']) ? $_COOKIE[$field . '_message'] : '';
        $values[$field] = !empty($_COOKIE[$field . '_value']) ? $_COOKIE[$field . '_value'] : '';

        if ($errors[$field]) {
            clear_error_cookie($field);
        }
    }

    if (!empty($_COOKIE['languages_value'])) {
        $decoded_languages = json_decode($_COOKIE['languages_value'], true);
        $values['languages'] = is_array($decoded_languages) ? $decoded_languages : [];
    } else {
        $values['languages'] = [];
    }

    $errors['languages'] = !empty($_COOKIE['languages_error']);
    $error_messages['languages'] = !empty($_COOKIE['languages_message']) ? $_COOKIE['languages_message'] : '';

    if ($errors['languages']) {
        setcookie('languages_error', '', time() - 3600, '/');
        setcookie('languages_message', '', time() - 3600, '/');
    }

    if (!empty($_COOKIE['save'])) {
        $messages[] = 'Данные успешно сохранены.';
        setcookie('save', '', time() - 3600, '/');
    }

    include 'form.php';
    exit();
}

$fio = normalize_fio(trim($_POST['fio'] ?? ''));
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$birth_date_input = trim($_POST['birth_date'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$biography = trim($_POST['biography'] ?? '');
$languages = $_POST['languages'] ?? [];
$contract_accepted = isset($_POST['contract_accepted']) ? '1' : '';

$has_errors = false;

set_value_cookie('fio', $fio, time() + 365 * 24 * 60 * 60);
set_value_cookie('phone', $phone, time() + 365 * 24 * 60 * 60);
set_value_cookie('email', $email, time() + 365 * 24 * 60 * 60);
set_value_cookie('birth_date', $birth_date_input, time() + 365 * 24 * 60 * 60);
set_value_cookie('gender', $gender, time() + 365 * 24 * 60 * 60);
set_value_cookie('biography', $biography, time() + 365 * 24 * 60 * 60);
set_value_cookie('contract_accepted', $contract_accepted, time() + 365 * 24 * 60 * 60);
setcookie('languages_value', json_encode($languages), time() + 365 * 24 * 60 * 60, '/');

if ($fio === '') {
    set_error_cookie('fio', 'Поле ФИО обязательно для заполнения.');
    $has_errors = true;
} elseif (mb_strlen($fio) > 150) {
    set_error_cookie('fio', 'ФИО не должно быть длиннее 150 символов.');
    $has_errors = true;
} elseif (!preg_match('/^[A-Za-zА-Яа-яЁё]+(?:[ -][A-Za-zА-Яа-яЁё]+)*$/u', $fio)) {
    set_error_cookie('fio', 'В поле ФИО допустимы только буквы, пробелы и дефис.');
    $has_errors = true;
} else {
    clear_error_cookie('fio');
}

if ($phone === '') {
    set_error_cookie('phone', 'Поле Телефон обязательно для заполнения.');
    $has_errors = true;
} elseif (!preg_match('/^\+?[0-9\s\-\(\)]+$/', $phone)) {
    set_error_cookie('phone', 'В поле Телефон допустимы только цифры, пробел, +, круглые скобки и дефис.');
    $has_errors = true;
} else {
    clear_error_cookie('phone');
}

if ($email === '') {
    set_error_cookie('email', 'Поле Email обязательно для заполнения.');
    $has_errors = true;
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_error_cookie('email', 'Введите корректный email.');
    $has_errors = true;
} else {
    clear_error_cookie('email');
}

$birth_date = null;

if ($birth_date_input === '') {
    set_error_cookie('birth_date', 'Поле Дата рождения обязательно для заполнения.');
    $has_errors = true;
} elseif (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $birth_date_input)) {
    set_error_cookie('birth_date', 'Дата рождения должна быть в формате ДД.ММ.ГГГГ.');
    $has_errors = true;
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
        set_error_cookie('birth_date', 'Введите существующую дату в формате ДД.ММ.ГГГГ.');
        $has_errors = true;
    } else {
        $birth_date = $date->format('Y-m-d');
        clear_error_cookie('birth_date');
    }
}

if ($gender !== 'male' && $gender !== 'female') {
    set_error_cookie('gender', 'Нужно выбрать допустимый пол.');
    $has_errors = true;
} else {
    clear_error_cookie('gender');
}

$allowed_languages = array_keys($languages_list);

if (!is_array($languages) || count($languages) === 0) {
    setcookie('languages_error', '1', 0, '/');
    setcookie('languages_message', 'Нужно выбрать хотя бы один язык программирования.', 0, '/');
    $has_errors = true;
} else {
    $languages_valid = true;

    foreach ($languages as $language_id) {
        if (!in_array((int)$language_id, $allowed_languages, true)) {
            $languages_valid = false;
            break;
        }
    }

    if (!$languages_valid) {
        setcookie('languages_error', '1', 0, '/');
        setcookie('languages_message', 'Выбран недопустимый язык программирования.', 0, '/');
        $has_errors = true;
    } else {
        setcookie('languages_error', '', time() - 3600, '/');
        setcookie('languages_message', '', time() - 3600, '/');
    }
}

if ($biography === '') {
    set_error_cookie('biography', 'Поле Биография обязательно для заполнения.');
    $has_errors = true;
} elseif (!preg_match('/^[A-Za-zА-Яа-яЁё0-9\s\.,!?\-:;"()«»\n\r]+$/u', $biography)) {
    set_error_cookie('biography', 'В поле Биография допустимы буквы, цифры, пробелы, перенос строки и знаки препинания . , ! ? - : ; " ( ) « »');
    $has_errors = true;
} else {
    clear_error_cookie('biography');
}

if ($contract_accepted !== '1') {
    set_error_cookie('contract_accepted', 'Необходимо ознакомиться с контрактом.');
    $has_errors = true;
} else {
    clear_error_cookie('contract_accepted');
}

if ($has_errors) {
    header('Location: index.php');
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
        1
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

    setcookie('save', '1', time() + 365 * 24 * 60 * 60, '/');
    header('Location: index.php');
    exit();
} catch (PDOException $e) {
    die('Ошибка базы данных: ' . htmlspecialchars($e->getMessage()));
}