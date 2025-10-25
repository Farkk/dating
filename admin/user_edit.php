<?php
/**
 * Страница редактирования/создания пользователя
 * Если есть параметр ?id=X - редактирование, иначе создание нового
 */

require_once '../config/db.php';
require_once 'auth.php';

// Проверка авторизации
requireAuth();

$is_edit_mode = isset($_GET['id']);
$user_id = $is_edit_mode ? (int)$_GET['id'] : null;
$user = null;
$error_message = '';
$validation_errors = [];

// Если режим редактирования, загружаем данные пользователя
if ($is_edit_mode) {
    $sql = "SELECT * FROM users WHERE id = :id";
    $user = executeQuery($sql, ['id' => $user_id])->fetch();

    if (!$user) {
        header('Location: users.php');
        exit;
    }

    // Преобразуем массив интересов из JSON (MySQL) в строку
    if ($user['interests']) {
        // Декодируем JSON массив
        $interests_array = json_decode($user['interests'], true);
        if (is_array($interests_array)) {
            $user['interests_string'] = implode(', ', $interests_array);
        } else {
            $user['interests_string'] = '';
        }
    } else {
        $user['interests_string'] = '';
    }
}

// Обработка POST-запроса (сохранение)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF токена
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Ошибка валидации CSRF токена';
    } else {
        // Получение данных из формы
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $date_of_birth = $_POST['date_of_birth'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $city = trim($_POST['city'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $interests_input = trim($_POST['interests'] ?? '');
        $rating = floatval($_POST['rating'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Валидация
        if (empty($username)) {
            $validation_errors['username'] = 'Username обязателен';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validation_errors['email'] = 'Некорректный email';
        }
        if (!$is_edit_mode && empty($password)) {
            $validation_errors['password'] = 'Пароль обязателен при создании пользователя';
        }
        if (empty($first_name)) {
            $validation_errors['first_name'] = 'Имя обязательно';
        }
        if (empty($last_name)) {
            $validation_errors['last_name'] = 'Фамилия обязательна';
        }
        if (empty($date_of_birth)) {
            $validation_errors['date_of_birth'] = 'Дата рождения обязательна';
        }
        if (empty($gender)) {
            $validation_errors['gender'] = 'Пол обязателен';
        }

        // Обработка интересов - преобразуем в массив для JSON (MySQL)
        $interests_array = [];
        if (!empty($interests_input)) {
            $interests_array = array_map('trim', explode(',', $interests_input));
            $interests_array = array_filter($interests_array); // Убираем пустые элементы
        }
        $interests_json = !empty($interests_array) ? json_encode($interests_array, JSON_UNESCAPED_UNICODE) : null;

        if (empty($validation_errors)) {
            try {
                if ($is_edit_mode) {
                    // Обновление существующего пользователя
                    $sql = "UPDATE users SET
                            username = :username,
                            email = :email,
                            first_name = :first_name,
                            last_name = :last_name,
                            date_of_birth = :date_of_birth,
                            gender = :gender,
                            city = :city,
                            bio = :bio,
                            interests = :interests,
                            rating = :rating,
                            is_active = :is_active,
                            updated_at = CURRENT_TIMESTAMP";

                    $params = [
                        'username' => $username,
                        'email' => $email,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'date_of_birth' => $date_of_birth,
                        'gender' => $gender,
                        'city' => $city,
                        'bio' => $bio,
                        'interests' => $interests_json,
                        'rating' => $rating,
                        'is_active' => $is_active,
                    ];

                    // Если указан новый пароль, обновляем его
                    if (!empty($password)) {
                        $sql .= ", password_hash = :password_hash";
                        $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                    }

                    $sql .= " WHERE id = :id";
                    $params['id'] = $user_id;

                    executeQuery($sql, $params);

                    // Редирект с сообщением об успехе
                    session_start();
                    $_SESSION['success_message'] = 'Пользователь успешно обновлен';
                    header('Location: users.php');
                    exit;

                } else {
                    // Создание нового пользователя
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);

                    $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name,
                                              date_of_birth, gender, city, bio, interests, rating, is_active)
                            VALUES (:username, :email, :password_hash, :first_name, :last_name,
                                   :date_of_birth, :gender, :city, :bio, :interests, :rating, :is_active)";

                    $params = [
                        'username' => $username,
                        'email' => $email,
                        'password_hash' => $password_hash,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'date_of_birth' => $date_of_birth,
                        'gender' => $gender,
                        'city' => $city,
                        'bio' => $bio,
                        'interests' => $interests_json,
                        'rating' => $rating,
                        'is_active' => $is_active,
                    ];

                    executeQuery($sql, $params);

                    // Редирект с сообщением об успехе
                    session_start();
                    $_SESSION['success_message'] = 'Пользователь успешно создан';
                    header('Location: users.php');
                    exit;
                }
            } catch (PDOException $e) {
                // Обработка ошибок уникальности
                if (strpos($e->getMessage(), 'users_username_key') !== false) {
                    $validation_errors['username'] = 'Пользователь с таким username уже существует';
                } elseif (strpos($e->getMessage(), 'users_email_key') !== false) {
                    $validation_errors['email'] = 'Пользователь с таким email уже существует';
                } else {
                    $error_message = 'Ошибка при сохранении: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <title><?php echo $is_edit_mode ? 'Редактирование пользователя' : 'Создание пользователя'; ?> - Админ-панель</title>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <nav class="admin-nav">
            <div class="admin-logo">
                <i class="fas fa-shield-alt"></i>
                <span>Админ-панель Dating Site</span>
            </div>
            <div class="nav-links">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i> Главная
                </a>
                <a href="users.php" class="nav-link active">
                    <i class="fas fa-users"></i> Пользователи
                </a>
                <a href="meetings.php" class="nav-link">
                    <i class="fas fa-calendar"></i> Встречи
                </a>
                <a href="reviews.php" class="nav-link">
                    <i class="fas fa-star"></i> Отзывы
                </a>
            </div>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars(getCurrentAdminUsername()); ?></span>
                <a href="logout.php" class="nav-link" title="Выход">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <div class="admin-container">
        <div class="page-header">
            <h1>
                <i class="fas fa-user-edit"></i>
                <?php echo $is_edit_mode ? 'Редактирование пользователя' : 'Создание нового пользователя'; ?>
            </h1>
            <p>
                <?php echo $is_edit_mode
                    ? 'Редактирование данных пользователя ' . htmlspecialchars($user['username'])
                    : 'Добавление нового пользователя в систему'; ?>
            </p>
        </div>

        <!-- Сообщения об ошибках -->
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($validation_errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Пожалуйста, исправьте следующие ошибки:</strong>
                <ul style="margin: 0.5rem 0 0 1.5rem;">
                    <?php foreach ($validation_errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Форма редактирования/создания -->
        <div class="card">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <!-- Левая колонка -->
                    <div>
                        <div class="form-group">
                            <label>Username <span class="required">*</span></label>
                            <input type="text" name="username" class="form-control" required
                                   value="<?php echo htmlspecialchars($user['username'] ?? $_POST['username'] ?? ''); ?>">
                            <?php if (isset($validation_errors['username'])): ?>
                                <small style="color: #dc3545;"><?php echo $validation_errors['username']; ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?php echo htmlspecialchars($user['email'] ?? $_POST['email'] ?? ''); ?>">
                            <?php if (isset($validation_errors['email'])): ?>
                                <small style="color: #dc3545;"><?php echo $validation_errors['email']; ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label>
                                Пароль
                                <?php if (!$is_edit_mode): ?>
                                    <span class="required">*</span>
                                <?php endif; ?>
                            </label>
                            <input type="password" name="password" class="form-control"
                                   <?php echo !$is_edit_mode ? 'required' : ''; ?>
                                   placeholder="<?php echo $is_edit_mode ? 'Оставьте пустым, если не хотите менять' : ''; ?>">
                            <?php if (isset($validation_errors['password'])): ?>
                                <small style="color: #dc3545;"><?php echo $validation_errors['password']; ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label>Имя <span class="required">*</span></label>
                            <input type="text" name="first_name" class="form-control" required
                                   value="<?php echo htmlspecialchars($user['first_name'] ?? $_POST['first_name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Фамилия <span class="required">*</span></label>
                            <input type="text" name="last_name" class="form-control" required
                                   value="<?php echo htmlspecialchars($user['last_name'] ?? $_POST['last_name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Дата рождения <span class="required">*</span></label>
                            <input type="date" name="date_of_birth" class="form-control" required
                                   value="<?php echo htmlspecialchars($user['date_of_birth'] ?? $_POST['date_of_birth'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Правая колонка -->
                    <div>
                        <div class="form-group">
                            <label>Пол <span class="required">*</span></label>
                            <select name="gender" class="form-control" required>
                                <option value="">Выберите пол</option>
                                <option value="мужской"
                                    <?php echo ($user['gender'] ?? $_POST['gender'] ?? '') === 'мужской' ? 'selected' : ''; ?>>
                                    Мужской
                                </option>
                                <option value="женский"
                                    <?php echo ($user['gender'] ?? $_POST['gender'] ?? '') === 'женский' ? 'selected' : ''; ?>>
                                    Женский
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Город</label>
                            <input type="text" name="city" class="form-control"
                                   value="<?php echo htmlspecialchars($user['city'] ?? $_POST['city'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Рейтинг (0.00 - 5.00)</label>
                            <input type="number" name="rating" class="form-control"
                                   min="0" max="5" step="0.01"
                                   value="<?php echo htmlspecialchars($user['rating'] ?? $_POST['rating'] ?? '0.00'); ?>">
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_active" class="checkbox"
                                       <?php echo ($user['is_active'] ?? true) ? 'checked' : ''; ?>>
                                Активен
                            </label>
                        </div>

                        <div class="form-group">
                            <label>Интересы (через запятую)</label>
                            <input type="text" name="interests" class="form-control"
                                   placeholder="Программирование, Путешествия, Спорт"
                                   value="<?php echo htmlspecialchars($user['interests_string'] ?? $_POST['interests'] ?? ''); ?>">
                            <small style="color: #666;">Введите интересы через запятую</small>
                        </div>
                    </div>
                </div>

                <!-- Биография - на всю ширину -->
                <div class="form-group">
                    <label>Биография</label>
                    <textarea name="bio" class="form-control" rows="5"
                              placeholder="Расскажите о себе..."><?php echo htmlspecialchars($user['bio'] ?? $_POST['bio'] ?? ''); ?></textarea>
                </div>

                <!-- Кнопки действий -->
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Отмена
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?php echo $is_edit_mode ? 'Сохранить изменения' : 'Создать пользователя'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Дополнительная информация при редактировании -->
        <?php if ($is_edit_mode): ?>
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-info-circle"></i> Дополнительная информация</h2>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <strong>ID пользователя:</strong> <?php echo $user['id']; ?>
                    </div>
                    <div>
                        <strong>Дата регистрации:</strong>
                        <?php echo date('d.m.Y H:i:s', strtotime($user['created_at'])); ?>
                    </div>
                    <div>
                        <strong>Последнее обновление:</strong>
                        <?php echo date('d.m.Y H:i:s', strtotime($user['updated_at'])); ?>
                    </div>
                    <div>
                        <strong>Фото профиля:</strong>
                        <?php echo $user['profile_photo'] ? htmlspecialchars($user['profile_photo']) : 'Не загружено'; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
