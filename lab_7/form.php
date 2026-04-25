<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Лабораторная работа 7</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page">
    <div class="form-container">
        <h1>Форма</h1>

        <?php if (!empty($messages)): ?>
            <div class="message success-message">
                <?php foreach ($messages as $message): ?>
                    <p><?php echo htmlspecialchars($message); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($generated_login) && !empty($generated_password)): ?>
            <div class="message credentials-message">
                <h2>Данные для входа</h2>
                <p>Логин: <b><?php echo htmlspecialchars($generated_login); ?></b></p>
                <p>Пароль: <b><?php echo htmlspecialchars($generated_password); ?></b></p>
                <p>Сохраните эти данные. Пароль показывается только один раз.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($is_authorized)): ?>
            <div class="message success-message">
                <p>Вы вошли в систему. Можно изменить ранее отправленные данные.</p>
                <p><a class="back-link" href="index.php?action=logout">Выйти</a></p>
            </div>
        <?php else: ?>
            <div class="message login-box">
                <h2>Вход для редактирования</h2>

                <?php if (!empty($login_error)): ?>
                    <p class="login-error"><?php echo htmlspecialchars($login_error); ?></p>
                <?php endif; ?>

                <form action="index.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                    <div class="form-group">
                        <label for="login">Логин</label>
                        <input type="text" id="login" name="login" autocomplete="username">
                    </div>

                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input type="password" id="password" name="password" autocomplete="current-password">
                    </div>

                    <button type="submit">Войти</button>
                </form>
            </div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group">
                <label for="fio">ФИО</label>
                <input
                    type="text"
                    id="fio"
                    name="fio"
                    maxlength="150"
                    autocomplete="name"
                    value="<?php echo htmlspecialchars($values['fio'] ?? ''); ?>"
                    class="<?php echo !empty($errors['fio']) ? 'input-invalid' : ''; ?>"
                >
                <span class="field-error"><?php echo htmlspecialchars($error_messages['fio'] ?? ''); ?></span>
            </div>

            <div class="form-group">
                <label for="phone">Телефон</label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    placeholder="+7 (999) 123-45-67"
                    autocomplete="tel"
                    value="<?php echo htmlspecialchars($values['phone'] ?? ''); ?>"
                    class="<?php echo !empty($errors['phone']) ? 'input-invalid' : ''; ?>"
                >
                <span class="field-error"><?php echo htmlspecialchars($error_messages['phone'] ?? ''); ?></span>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="text"
                    id="email"
                    name="email"
                    autocomplete="email"
                    value="<?php echo htmlspecialchars($values['email'] ?? ''); ?>"
                    class="<?php echo !empty($errors['email']) ? 'input-invalid' : ''; ?>"
                >
                <span class="field-error"><?php echo htmlspecialchars($error_messages['email'] ?? ''); ?></span>
            </div>

            <div class="form-group">
                <label for="birth_date">Дата рождения</label>
                <input
                    type="text"
                    id="birth_date"
                    name="birth_date"
                    placeholder="ДД.ММ.ГГГГ"
                    maxlength="10"
                    value="<?php echo htmlspecialchars($values['birth_date'] ?? ''); ?>"
                    class="<?php echo !empty($errors['birth_date']) ? 'input-invalid' : ''; ?>"
                >
                <span class="field-error"><?php echo htmlspecialchars($error_messages['birth_date'] ?? ''); ?></span>
            </div>

            <div class="form-group">
                <label>Пол</label>
                <div class="radio-group <?php echo !empty($errors['gender']) ? 'group-invalid' : ''; ?>">
                    <label class="inline-label">
                        <input
                            type="radio"
                            name="gender"
                            value="male"
                            <?php echo (($values['gender'] ?? '') === 'male') ? 'checked' : ''; ?>
                        >
                        Мужской
                    </label>

                    <label class="inline-label">
                        <input
                            type="radio"
                            name="gender"
                            value="female"
                            <?php echo (($values['gender'] ?? '') === 'female') ? 'checked' : ''; ?>
                        >
                        Женский
                    </label>
                </div>
                <span class="field-error"><?php echo htmlspecialchars($error_messages['gender'] ?? ''); ?></span>
            </div>

            <div class="form-group">
                <label>Любимые языки программирования</label>
                <div class="checkbox-list <?php echo !empty($errors['languages']) ? 'group-invalid' : ''; ?>">
                    <?php foreach ($languages_list as $id => $name): ?>
                        <label class="inline-label">
                            <input
                                type="checkbox"
                                name="languages[]"
                                value="<?php echo htmlspecialchars($id); ?>"
                                <?php echo in_array((string)$id, array_map('strval', $values['languages'] ?? []), true) ? 'checked' : ''; ?>
                            >
                            <?php echo htmlspecialchars($name); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <span class="field-error"><?php echo htmlspecialchars($error_messages['languages'] ?? ''); ?></span>
            </div>

            <div class="form-group">
                <label for="biography">Биография</label>
                <textarea
                    id="biography"
                    name="biography"
                    rows="6"
                    class="<?php echo !empty($errors['biography']) ? 'input-invalid' : ''; ?>"
                ><?php echo htmlspecialchars($values['biography'] ?? ''); ?></textarea>
                <span class="field-error"><?php echo htmlspecialchars($error_messages['biography'] ?? ''); ?></span>
            </div>

            <div class="form-group checkbox-group">
                <label class="inline-label">
                    <input
                        type="checkbox"
                        id="contract_accepted"
                        name="contract_accepted"
                        value="1"
                        <?php echo (($values['contract_accepted'] ?? '') === '1') ? 'checked' : ''; ?>
                    >
                    С контрактом ознакомлен(а)
                </label>
                <span class="field-error"><?php echo htmlspecialchars($error_messages['contract_accepted'] ?? ''); ?></span>
            </div>

            <button type="submit">
                <?php echo !empty($is_authorized) ? 'Сохранить изменения' : 'Сохранить'; ?>
            </button>
        </form>
    </div>
</div>
</body>
</html>
