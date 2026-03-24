<?php
$languages = [
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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Лабораторная работа 3</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .field-error {
            display: block;
            margin-top: 6px;
            color: #ff9db7;
            font-size: 14px;
            min-height: 18px;
        }

        .input-invalid {
            border-color: #ff2a5d !important;
            box-shadow:
                0 0 12px rgba(255, 42, 93, 0.45),
                0 0 25px rgba(255, 42, 93, 0.25),
                inset 0 0 12px rgba(0,0,0,0.4) !important;
        }
    </style>
</head>
<body>
<div class="page">
    <div class="form-container">
        <h1>Форма</h1>

        <form id="application-form" action="form.php" method="POST" novalidate>
            <div class="form-group">
                <label for="fio">ФИО</label>
                <input
                    type="text"
                    id="fio"
                    name="fio"
                    maxlength="150"
                >
                <span class="field-error" id="fio-error"></span>
            </div>

            <div class="form-group">
                <label for="phone">Телефон</label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    placeholder="+7 (999) 123-45-67"
                >
                <span class="field-error" id="phone-error"></span>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="text"
                    id="email"
                    name="email"
                >
                <span class="field-error" id="email-error"></span>
            </div>

            <div class="form-group">
                <label for="birth_date">Дата рождения</label>
                <input
                    type="text"
                    id="birth_date"
                    name="birth_date"
                    placeholder="ДД.ММ.ГГГГ"
                    maxlength="10"
                >
                <span class="field-error" id="birth_date-error"></span>
            </div>

            <div class="form-group">
                <label>Пол</label>
                <div class="radio-group" id="gender-group">
                    <label class="inline-label">
                        <input
                            type="radio"
                            name="gender"
                            value="male"
                        >
                        Мужской
                    </label>

                    <label class="inline-label">
                        <input
                            type="radio"
                            name="gender"
                            value="female"
                        >
                        Женский
                    </label>
                </div>
                <span class="field-error" id="gender-error"></span>
            </div>

            <div class="form-group">
                <label>Любимые языки программирования</label>
                <div class="checkbox-list" id="languages-group">
                    <?php foreach ($languages as $id => $name): ?>
                        <label class="inline-label">
                            <input
                                type="checkbox"
                                name="languages[]"
                                value="<?php echo $id; ?>"
                            >
                            <?php echo htmlspecialchars($name); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <span class="field-error" id="languages-error"></span>
            </div>

            <div class="form-group">
                <label for="biography">Биография</label>
                <textarea
                    id="biography"
                    name="biography"
                    rows="6"
                ></textarea>
                <span class="field-error" id="biography-error"></span>
            </div>

            <div class="form-group checkbox-group">
                <label class="inline-label">
                    <input
                        type="checkbox"
                        id="contract_accepted"
                        name="contract_accepted"
                        value="1"
                    >
                    С контрактом ознакомлен(а)
                </label>
                <span class="field-error" id="contract_accepted-error"></span>
            </div>

            <button type="submit">Сохранить</button>
        </form>
    </div>
</div>

<script>
const form = document.getElementById('application-form');

const fio = document.getElementById('fio');
const phone = document.getElementById('phone');
const email = document.getElementById('email');
const birthDate = document.getElementById('birth_date');
const biography = document.getElementById('biography');
const contractAccepted = document.getElementById('contract_accepted');

const genderInputs = document.querySelectorAll('input[name="gender"]');
const languageInputs = document.querySelectorAll('input[name="languages[]"]');

function setError(element, errorId, message) {
    document.getElementById(errorId).textContent = message;
    if (element) {
        element.classList.add('input-invalid');
    }
}

function clearError(element, errorId) {
    document.getElementById(errorId).textContent = '';
    if (element) {
        element.classList.remove('input-invalid');
    }
}

function normalizeSpaces(value) {
    return value.replace(/\s+/g, ' ').trim();
}

function isValidDate(value) {
    if (!/^\d{2}\.\d{2}\.\d{4}$/.test(value)) {
        return false;
    }

    const parts = value.split('.');
    const day = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10);
    const year = parseInt(parts[2], 10);

    const date = new Date(year, month - 1, day);

    if (
        date.getFullYear() !== year ||
        date.getMonth() !== month - 1 ||
        date.getDate() !== day
    ) {
        return false;
    }

    return true;
}

function validateFio() {
    const value = normalizeSpaces(fio.value);
    const regex = /^[A-Za-zА-Яа-яЁё]+(?:[ -][A-Za-zА-Яа-яЁё]+)*$/u;

    fio.value = value;

    if (value === '') {
        setError(fio, 'fio-error', 'Поле ФИО обязательно для заполнения.');
        return false;
    }

    if (value.length > 150) {
        setError(fio, 'fio-error', 'ФИО не должно быть длиннее 150 символов.');
        return false;
    }

    if (!regex.test(value)) {
        setError(fio, 'fio-error', 'ФИО должно содержать только буквы, пробелы и дефис.');
        return false;
    }

    clearError(fio, 'fio-error');
    return true;
}

function validatePhone() {
    const value = phone.value.trim();
    const regex = /^\+?[0-9\s\-\(\)]+$/;

    if (value === '') {
        setError(phone, 'phone-error', 'Поле Телефон обязательно для заполнения.');
        return false;
    }

    if (!regex.test(value)) {
        setError(phone, 'phone-error', 'Телефон содержит недопустимые символы.');
        return false;
    }

    clearError(phone, 'phone-error');
    return true;
}

function validateEmail() {
    const value = email.value.trim();
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (value === '') {
        setError(email, 'email-error', 'Поле Email обязательно для заполнения.');
        return false;
    }

    if (!regex.test(value)) {
        setError(email, 'email-error', 'Неверный формат email.');
        return false;
    }

    clearError(email, 'email-error');
    return true;
}

function validateBirthDate() {
    const value = birthDate.value.trim();

    if (value === '') {
        setError(birthDate, 'birth_date-error', 'Поле Дата рождения обязательно для заполнения.');
        return false;
    }

    if (!isValidDate(value)) {
        setError(birthDate, 'birth_date-error', 'Дата должна быть в формате ДД.ММ.ГГГГ и быть реальной.');
        return false;
    }

    clearError(birthDate, 'birth_date-error');
    return true;
}

function validateGender() {
    let checked = false;

    genderInputs.forEach(function(input) {
        if (input.checked) {
            checked = true;
        }
    });

    if (!checked) {
        document.getElementById('gender-error').textContent = 'Выберите пол.';
        return false;
    }

    document.getElementById('gender-error').textContent = '';
    return true;
}

function validateLanguages() {
    let checked = false;

    languageInputs.forEach(function(input) {
        if (input.checked) {
            checked = true;
        }
    });

    if (!checked) {
        document.getElementById('languages-error').textContent = 'Выберите хотя бы один язык программирования.';
        return false;
    }

    document.getElementById('languages-error').textContent = '';
    return true;
}

function validateBiography() {
    const value = biography.value.trim();

    if (value === '') {
        setError(biography, 'biography-error', 'Поле Биография обязательно для заполнения.');
        return false;
    }

    clearError(biography, 'biography-error');
    return true;
}

function validateContract() {
    if (!contractAccepted.checked) {
        document.getElementById('contract_accepted-error').textContent = 'Необходимо ознакомиться с контрактом.';
        return false;
    }

    document.getElementById('contract_accepted-error').textContent = '';
    return true;
}

function validateForm() {
    const results = [
        validateFio(),
        validatePhone(),
        validateEmail(),
        validateBirthDate(),
        validateGender(),
        validateLanguages(),
        validateBiography(),
        validateContract()
    ];

    return results.every(function(result) {
        return result === true;
    });
}

fio.addEventListener('input', validateFio);
phone.addEventListener('input', validatePhone);
email.addEventListener('input', validateEmail);
birthDate.addEventListener('input', validateBirthDate);
biography.addEventListener('input', validateBiography);
contractAccepted.addEventListener('change', validateContract);

genderInputs.forEach(function(input) {
    input.addEventListener('change', validateGender);
});

languageInputs.forEach(function(input) {
    input.addEventListener('change', validateLanguages);
});

birthDate.addEventListener('input', function() {
    let value = birthDate.value.replace(/[^\d]/g, '');

    if (value.length > 2) {
        value = value.slice(0, 2) + '.' + value.slice(2);
    }

    if (value.length > 5) {
        value = value.slice(0, 5) + '.' + value.slice(5, 9);
    }

    birthDate.value = value;
});

fio.addEventListener('blur', function() {
    fio.value = normalizeSpaces(fio.value);
    validateFio();
});

form.addEventListener('submit', function(event) {
    if (!validateForm()) {
        event.preventDefault();
    }
});
</script>
</body>
</html>