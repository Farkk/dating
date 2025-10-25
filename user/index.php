<?php
// Проверка авторизации
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: /auth/login.php');
    exit;
}

// Подключение к базе данных
require_once '../config/db.php';

// ID текущего авторизованного пользователя
$current_user_id = $_SESSION['user_id'];

// Функция для вычисления возраста
function calculateAge($birthdate) {
    $today = new DateTime();
    $diff = $today->diff(new DateTime($birthdate));
    return $diff->y;
}

// Функция для склонения слов
function pluralize($number, $one, $few, $many) {
    $n = abs($number) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) return $many;
    if ($n1 > 1 && $n1 < 5) return $few;
    if ($n1 == 1) return $one;
    return $many;
}

// Получаем ID пользователя из параметра URL (по умолчанию - текущий авторизованный пользователь)
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : $current_user_id;

// Получаем данные пользователя
$sql = "SELECT
    u.id,
    u.first_name,
    u.last_name,
    u.date_of_birth,
    u.gender,
    u.city,
    u.bio,
    u.profile_photo,
    u.rating,
    u.interests,
    COUNT(DISTINCT m.id) as meetings_count
FROM users u
LEFT JOIN meetings m ON (u.id = m.user1_id OR u.id = m.user2_id) AND m.status = 'completed'
WHERE u.id = :user_id AND u.is_active = TRUE
GROUP BY u.id, u.first_name, u.last_name, u.date_of_birth, u.gender, u.city, u.bio, u.profile_photo, u.rating, u.interests";

$user = executeQuery($sql, [':user_id' => $user_id])->fetch();

// Если пользователь не найден, показываем ошибку
if (!$user) {
    die('Пользователь не найден');
}

$age = calculateAge($user['date_of_birth']);
$rating_display = $user['rating'] > 0 ? number_format($user['rating'], 1) . '/5' : 'Нет оценок';

// Парсим массив интересов из JSON (MySQL)
$interests = [];
if ($user['interests']) {
    // MySQL хранит интересы в формате JSON
    $decoded = json_decode($user['interests'], true);
    if (is_array($decoded)) {
        $interests = $decoded;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans+Hebrew&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
    <title>Профиль - <?php echo htmlspecialchars($user['first_name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .header {
            background-color: #ff4081;
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
        }

        .nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .nav-logo {
            font-weight: bold;
            font-size: 1.5rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: white;
            bottom: -5px;
            left: 0;
            transition: width 0.3s ease;
        }

        .nav-link:hover:after {
            width: 100%;
        }

        .main {
            max-width: 1200px;
            margin: 6rem auto 2rem;
            padding: 0 2rem;
        }

        .profile {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .profile-sidebar {
            padding: 2rem;
            background: #f9f9f9;
        }

        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: block;
            object-fit: cover;
            border: 5px solid #ff4081;
            transition: transform 0.3s ease;
        }

        .profile-photo:hover {
            transform: scale(1.05);
        }

        .profile-name {
            text-align: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #ff4081;
        }

        .profile-stat {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            border-radius: 5px;
            background: white;
            transition: transform 0.3s ease;
        }

        .profile-stat:hover {
            transform: translateX(5px);
            background: #ffebf3;
        }

        .profile-stat-label {
            font-weight: 500;
            color: #666;
        }

        .profile-main {
            padding: 2rem;
        }

        .section-title {
            margin-bottom: 1.5rem;
            color: #ff4081;
            position: relative;
            display: inline-block;
        }

        .section-title:after {
            content: '';
            position: absolute;
            height: 3px;
            width: 50px;
            background: #ff4081;
            bottom: -5px;
            left: 0;
        }

        .bio {
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .interests {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .interest-tag {
            background: #ffebf3;
            color: #ff4081;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .interest-tag:hover {
            transform: translateY(-3px);
            background: #ff4081;
            color: white;
        }

        .btn {
            display: inline-block;
            background: #ff4081;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #e6366f;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #ff4081;
            color: #ff4081;
        }

        .btn-outline:hover {
            background: #ff4081;
            color: white;
        }

        .social-links {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f0f0f0;
            color: #ff4081;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: #ff4081;
            color: white;
            transform: translateY(-3px);
        }

        .footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 2rem 0;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .profile {
                grid-template-columns: 1fr;
            }

            .profile-photo {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="nav-logo animate__animated animate__fadeIn">Dating Site</div>
            <div class="nav-links">
                <a href="../index.php" class="nav-link animate__animated animate__fadeIn">Главная</a>
                <a href="../find/index.php" class="nav-link animate__animated animate__fadeIn">Найти пару</a>
                <a href="../rating/index.php" class="nav-link animate__animated animate__fadeIn">Рейтинг</a>
                <a href="index.php?id=<?php echo $current_user_id; ?>" class="nav-link animate__animated animate__fadeIn" style="font-weight: bold;">Профиль</a>
            </div>
        </nav>
    </header>

    <main class="main">
        <div class="profile animate__animated animate__fadeIn">
            <div class="profile-sidebar">
                <?php if (!empty($user['profile_photo'])): ?>
                    <img src="/<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="<?php echo htmlspecialchars($user['first_name']); ?>" class="profile-photo animate__animated animate__zoomIn">
                <?php else: ?>
                    <div class="profile-photo animate__animated animate__zoomIn" style="background: #e0e0e0; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user" style="font-size: 80px; color: #999;"></i>
                    </div>
                <?php endif; ?>
                <h1 class="profile-name animate__animated animate__fadeIn"><?php echo htmlspecialchars($user['first_name']); ?></h1>

                <div class="profile-stat animate__animated animate__fadeInLeft" style="animation-delay: 0.2s">
                    <span class="profile-stat-label">Возраст:</span>
                    <span class="profile-stat-value"><?php echo $age; ?></span>
                </div>

                <div class="profile-stat animate__animated animate__fadeInLeft" style="animation-delay: 0.3s">
                    <span class="profile-stat-label">Город:</span>
                    <span class="profile-stat-value"><?php echo htmlspecialchars($user['city']); ?></span>
                </div>

                <div class="profile-stat animate__animated animate__fadeInLeft" style="animation-delay: 0.4s">
                    <span class="profile-stat-label">Встреч:</span>
                    <span class="profile-stat-value"><?php echo $user['meetings_count']; ?></span>
                </div>

                <div class="profile-stat animate__animated animate__fadeInLeft" style="animation-delay: 0.5s">
                    <span class="profile-stat-label">Рейтинг:</span>
                    <span class="profile-stat-value"><?php echo $rating_display; ?></span>
                </div>

                <div class="social-links">
                    <a href="#" class="social-link animate__animated animate__bounceIn" style="animation-delay: 0.6s">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="social-link animate__animated animate__bounceIn" style="animation-delay: 0.7s">
                        <i class="fab fa-telegram"></i>
                    </a>
                    <a href="#" class="social-link animate__animated animate__bounceIn" style="animation-delay: 0.8s">
                        <i class="fab fa-vk"></i>
                    </a>
                </div>
            </div>

            <div class="profile-main">
                <h2 class="section-title animate__animated animate__fadeIn">О себе</h2>
                <p class="bio animate__animated animate__fadeIn" style="animation-delay: 0.2s">
                    <?php echo nl2br(htmlspecialchars($user['bio'])); ?>
                </p>

                <?php if (!empty($interests)): ?>
                <h2 class="section-title animate__animated animate__fadeIn" style="animation-delay: 0.3s">Интересы</h2>
                <div class="interests animate__animated animate__fadeIn" style="animation-delay: 0.4s">
                    <?php foreach ($interests as $interest): ?>
                        <span class="interest-tag"><?php echo htmlspecialchars($interest); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($user['id'] == $current_user_id): ?>
                    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                        <a href="/profile/edit.php" class="btn animate__animated animate__fadeIn" style="animation-delay: 0.7s">
                            <i class="fas fa-edit"></i> Редактировать профиль
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; 2025 Dating Site. Все права защищены.</p>
    </footer>
</body>
</html>
