<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Лабораторная работа 4</title>
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

        <form action="index.php" method="POST">
            <div class="form-group">
                <label for="fio">ФИО</label>
                <input
                    type="text"
                    id="fio"
                    name="fio"
                    maxlength="150"
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
                                value="<?php echo $id; ?>"
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

            <button type="submit">Сохранить</button>
        </form>
    </div>
</div>
</body>
</html>