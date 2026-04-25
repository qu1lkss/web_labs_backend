<?php
session_start();

header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

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

function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function check_csrf_token($token) {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function set_error_cookie($field, $message) {
    setcookie($field . '_error', '1', 0, '/');
    setcookie($field . '_message', $message, 0, '/');
}

function clear_error_cookie($field) {
    setcookie($field . '_error', '', time() - 3600, '/');
    setcookie($field . '_message', '', time() - 3600, '/');
}

function set_value_cookie($field, $value) {
    setcookie($field . '_value', $value, time() + 365 * 24 * 60 * 60, '/');
}

function normalize_fio($fio) {
    $fio = preg_replace('/\s+/u', ' ', $fio);
    return trim($fio);
}

function get_application($pdo, $application_id) {
    $stmt = $pdo->prepare('SELECT * FROM applications WHERE id = ?');
    $stmt->execute([$application_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT language_id FROM application_languages WHERE application_id = ?');
    $stmt->execute([$application_id]);

    $languages = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $languages[] = (string)$row['language_id'];
    }

    $application['languages'] = $languages;

    if (!empty($application['birth_date'])) {
        $date = DateTime::createFromFormat('Y-m-d', $application['birth_date']);
        if ($date) {
            $application['birth_date'] = $date->format('d.m.Y');
        }
    }

    return $application;
}

function generate_login($pdo) {
    do {
        $login = 'user' . random_int(100000, 999999);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM applications WHERE login = ?');
        $stmt->execute([$login]);
        $count = (int)$stmt->fetchColumn();
    } while ($count > 0);

    return $login;
}

function generate_password() {
    return bin2hex(random_bytes(4));
}

function read_form_state_from_cookies() {
    $fields = [
        'fio',
        'phone',
        'email',
        'birth_date',
        'gender',
        'biography',
        'contract_accepted',
        'languages'
    ];

    $errors = [];
    $error_messages = [];
    $values = [];
    $has_errors = false;

    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
        $error_messages[$field] = !empty($_COOKIE[$field . '_message']) ? $_COOKIE[$field . '_message'] : '';

        if ($field === 'languages') {
            if (!empty($_COOKIE['languages_value'])) {
                $decoded = json_decode($_COOKIE['languages_value'], true);
                $values['languages'] = is_array($decoded) ? $decoded : [];
            } else {
                $values['languages'] = [];
            }
        } else {
            $values[$field] = !empty($_COOKIE[$field . '_value']) ? $_COOKIE[$field . '_value'] : '';
        }

        if ($errors[$field]) {
            $has_errors = true;
            clear_error_cookie($field);
        }
    }

    return [$errors, $error_messages, $values, $has_errors];
}

function set_form_value_cookies($fio, $phone, $email, $birth_date, $gender, $biography, $contract_accepted, $languages) {
    set_value_cookie('fio', $fio);
    set_value_cookie('phone', $phone);
    set_value_cookie('email', $email);
    set_value_cookie('birth_date', $birth_date);
    set_value_cookie('gender', $gender);
    set_value_cookie('biography', $biography);
    set_value_cookie('contract_accepted', $contract_accepted);
    setcookie('languages_value', json_encode($languages), time() + 365 * 24 * 60 * 60, '/');
}

function validate_form($fio, $phone, $email, $birth_date_input, $gender, $biography, $contract_accepted, $languages, $languages_list) {
    $has_errors = false;
    $birth_date = null;

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
        set_error_cookie('languages', 'Нужно выбрать хотя бы один язык программирования.');
        $has_errors = true;
    } else {
        foreach ($languages as $language_id) {
            if (!in_array((int)$language_id, $allowed_languages, true)) {
                set_error_cookie('languages', 'Выбран недопустимый язык программирования.');
                $has_errors = true;
                break;
            }
        }

        if (!$has_errors) {
            clear_error_cookie('languages');
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

    return [$has_errors, $birth_date];
}

function save_languages($pdo, $application_id, $languages) {
    $delete_stmt = $pdo->prepare('DELETE FROM application_languages WHERE application_id = ?');
    $delete_stmt->execute([$application_id]);

    $insert_stmt = $pdo->prepare('INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)');

    foreach ($languages as $language_id) {
        $insert_stmt->execute([$application_id, (int)$language_id]);
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    if (!check_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['login_error'] = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
        header('Location: index.php');
        exit();
    }

    $login = trim($_POST['login'] ?? '');
    $plain_password = trim($_POST['password'] ?? '');

    try {
        $pdo = get_pdo($dsn, $username, $password);
        $stmt = $pdo->prepare('SELECT id, password_hash FROM applications WHERE login = ?');
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($plain_password, $user['password_hash'])) {
            $_SESSION['application_id'] = (int)$user['id'];
            $_SESSION['message'] = 'Вы успешно вошли. Теперь можно изменить данные.';
            header('Location: index.php');
            exit();
        }

        $_SESSION['login_error'] = 'Неверный логин или пароль.';
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        stop_with_db_error($e);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $messages = [];
    $login_error = '';
    $csrf_token = get_csrf_token();

    if (!empty($_SESSION['message'])) {
        $messages[] = $_SESSION['message'];
        unset($_SESSION['message']);
    }

    if (!empty($_SESSION['login_error'])) {
        $login_error = $_SESSION['login_error'];
        unset($_SESSION['login_error']);
    }

    if (!empty($_SESSION['generated_login']) && !empty($_SESSION['generated_password'])) {
        $generated_login = $_SESSION['generated_login'];
        $generated_password = $_SESSION['generated_password'];
        unset($_SESSION['generated_login']);
        unset($_SESSION['generated_password']);
    } else {
        $generated_login = '';
        $generated_password = '';
    }

    [$errors, $error_messages, $cookie_values, $has_cookie_errors] = read_form_state_from_cookies();

    $is_authorized = !empty($_SESSION['application_id']);
    $values = $cookie_values;

    if ($is_authorized && !$has_cookie_errors) {
        try {
            $pdo = get_pdo($dsn, $username, $password);
            $application = get_application($pdo, $_SESSION['application_id']);

            if ($application) {
                $values = [
                    'fio' => $application['fio'],
                    'phone' => $application['phone'],
                    'email' => $application['email'],
                    'birth_date' => $application['birth_date'],
                    'gender' => $application['gender'],
                    'biography' => $application['biography'],
                    'contract_accepted' => (string)$application['contract_accepted'],
                    'languages' => $application['languages']
                ];
            } else {
                session_destroy();
                header('Location: index.php');
                exit();
            }
        } catch (PDOException $e) {
            stop_with_db_error($e);
        }
    }

    include 'form.php';
    exit();
}

if (!check_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['message'] = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    header('Location: index.php');
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

set_form_value_cookies($fio, $phone, $email, $birth_date_input, $gender, $biography, $contract_accepted, $languages);

[$has_errors, $birth_date] = validate_form($fio, $phone, $email, $birth_date_input, $gender, $biography, $contract_accepted, $languages, $languages_list);

if ($has_errors) {
    header('Location: index.php');
    exit();
}

try {
    $pdo = get_pdo($dsn, $username, $password);

    if (!empty($_SESSION['application_id'])) {
        $stmt = $pdo->prepare('UPDATE applications SET fio = ?, phone = ?, email = ?, birth_date = ?, gender = ?, biography = ?, contract_accepted = ? WHERE id = ?');
        $stmt->execute([$fio, $phone, $email, $birth_date, $gender, $biography, 1, $_SESSION['application_id']]);
        save_languages($pdo, $_SESSION['application_id'], $languages);
        $_SESSION['message'] = 'Данные успешно изменены.';
        header('Location: index.php');
        exit();
    }

    $login = generate_login($pdo);
    $plain_password = generate_password();
    $password_hash = password_hash($plain_password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('INSERT INTO applications (login, password_hash, fio, phone, email, birth_date, gender, biography, contract_accepted) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$login, $password_hash, $fio, $phone, $email, $birth_date, $gender, $biography, 1]);

    $application_id = $pdo->lastInsertId();
    save_languages($pdo, $application_id, $languages);

    $_SESSION['message'] = 'Данные успешно сохранены. Сохраните логин и пароль.';
    $_SESSION['generated_login'] = $login;
    $_SESSION['generated_password'] = $plain_password;

    header('Location: index.php');
    exit();
} catch (PDOException $e) {
    stop_with_db_error($e);
}
