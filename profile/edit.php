<?php
session_start();

require_once '../config/db.php';
require_once '../auth/check_auth.php';

// Получаем данные текущего пользователя
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: /auth/login.php');
    exit;
}

// Обработка отправки формы
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $city = trim($_POST['city'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $interests = isset($_POST['interests']) ? $_POST['interests'] : [];

    // Валидация
    if (empty($first_name) || empty($last_name)) {
        $error = 'Имя и фамилия обязательны для заполнения';
    } else {
        // Обновляем данные пользователя
        $stmt = $pdo->prepare("
            UPDATE users SET
                first_name = :first_name,
                last_name = :last_name,
                date_of_birth = :date_of_birth,
                gender = :gender,
                city = :city,
                bio = :bio,
                interests = :interests
            WHERE id = :id
        ");

        // Преобразуем интересы в JSON для MySQL
        $interests_json = !empty($interests) ? json_encode($interests, JSON_UNESCAPED_UNICODE) : null;

        $stmt->execute([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'date_of_birth' => $date_of_birth ?: null,
            'gender' => $gender ?: null,
            'city' => $city ?: null,
            'bio' => $bio ?: null,
            'interests' => $interests_json,
            'id' => $user_id
        ]);

        $message = 'Профиль успешно обновлен!';

        // Обновляем данные пользователя
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch();
    }
}

// Преобразуем интересы из JSON в PHP array
$user_interests = [];
if (!empty($user['interests'])) {
    $user_interests = json_decode($user['interests'], true);
    if (!is_array($user_interests)) {
        $user_interests = [];
    }
}

// Список популярных интересов
$available_interests = [
    'Программирование', 'Путешествия', 'Фотография', 'Музыка', 'Кино',
    'Спорт', 'Кулинария', 'Чтение', 'Искусство', 'Танцы',
    'Йога', 'Дизайн', 'IT', 'Экология', 'Саморазвитие',
    'Настольные игры', 'Велоспорт', 'Походы', 'Медицина'
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование профиля - Сайт знакомств</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        h1 {
            color: #333;
            font-size: 28px;
        }

        .logout-btn {
            padding: 10px 20px;
            background: #ff4081;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #e91e63;
        }

        .message {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-size: 14px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        input[type="text"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="date"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #ff4081;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .interests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .interest-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .interest-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .interest-checkbox label {
            margin: 0;
            font-weight: normal;
            cursor: pointer;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #ff4081;
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            background: #e91e63;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 64, 129, 0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .profile-photo-section {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }

        .current-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 4px solid #ff4081;
        }

        .photo-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            border: 4px solid #e0e0e0;
        }

        .photo-placeholder i {
            font-size: 50px;
            color: #999;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 20px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-edit"></i> Редактирование профиля</h1>
            <a href="/auth/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Выйти
            </a>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="profile-photo-section">
            <?php if (!empty($user['profile_photo'])): ?>
                <img src="/<?= htmlspecialchars($user['profile_photo']) ?>" alt="Фото профиля" class="current-photo">
            <?php else: ?>
                <div class="photo-placeholder">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
            <div>
                <a href="upload_photo.php" class="btn btn-secondary">
                    <i class="fas fa-camera"></i> Изменить фото
                </a>
            </div>
        </div>

        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label for="first_name">Имя *</label>
                    <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="last_name">Фамилия *</label>
                    <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="date_of_birth">Дата рождения</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($user['date_of_birth'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="gender">Пол</label>
                    <select id="gender" name="gender">
                        <option value="">Не указан</option>
                        <option value="мужской" <?= ($user['gender'] ?? '') === 'мужской' ? 'selected' : '' ?>>Мужской</option>
                        <option value="женский" <?= ($user['gender'] ?? '') === 'женский' ? 'selected' : '' ?>>Женский</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="city">Город</label>
                    <input type="text" id="city" name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>" placeholder="Например: Москва">
                </div>

                <div class="form-group full-width">
                    <label for="bio">О себе</label>
                    <textarea id="bio" name="bio" placeholder="Расскажите о себе..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label>Интересы</label>
                    <div class="interests-grid">
                        <?php foreach ($available_interests as $interest): ?>
                            <div class="interest-checkbox">
                                <input type="checkbox"
                                       id="interest_<?= md5($interest) ?>"
                                       name="interests[]"
                                       value="<?= htmlspecialchars($interest) ?>"
                                       <?= in_array($interest, $user_interests) ? 'checked' : '' ?>>
                                <label for="interest_<?= md5($interest) ?>"><?= htmlspecialchars($interest) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Сохранить изменения
                </button>
                <a href="/" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> На главную
                </a>
            </div>
        </form>
    </div>
</body>
</html>
