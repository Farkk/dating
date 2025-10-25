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

// Получаем фильтр по полу (если есть)
$gender_filter = isset($_GET['gender']) ? $_GET['gender'] : null;

// SQL-запрос для получения пользователей с количеством встреч и их рейтингом
$sql = "SELECT
    u.id,
    u.first_name,
    u.date_of_birth,
    u.gender,
    u.city,
    u.profile_photo,
    u.rating,
    COUNT(DISTINCT m.id) as meetings_count
FROM users u
LEFT JOIN meetings m ON (u.id = m.user1_id OR u.id = m.user2_id) AND m.status = 'completed'
WHERE u.is_active = TRUE";

$params = [];

// Фильтрация по полу
if ($gender_filter) {
    $sql .= " AND u.gender = :gender";
    $params[':gender'] = $gender_filter;
}

$sql .= " GROUP BY u.id, u.first_name, u.date_of_birth, u.gender, u.city, u.profile_photo, u.rating
ORDER BY u.rating DESC, meetings_count DESC";

$users = executeQuery($sql, $params)->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css?family=Inter&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans+Hebrew&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
    <title>Рейтинг пользователей</title>
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

        .section-title {
            text-align: center;
            margin: 2rem 0;
            font-size: 2rem;
            color: #ff4081;
        }

        .leaderboard {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .leaderboard-header {
            background: #ff4081;
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .leaderboard-title {
            margin: 0;
            font-size: 1.5rem;
        }

        .leaderboard-description {
            margin-top: 0.5rem;
            opacity: 0.9;
        }

        .leaderboard-filter {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin: 1rem 0;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: white;
            border: 2px solid #ff4081;
            color: #ff4081;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            text-decoration: none;
        }

        .filter-btn:hover {
            background: #ff4081;
            color: white;
        }

        .filter-btn.active {
            background: #ff4081;
            color: white;
        }

        .ranking-container {
            padding: 0 1rem;
        }

        .ranking-item {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .ranking-item:hover {
            transform: translateX(5px);
            background: #ffebf3;
        }

        .ranking-item:last-child {
            border-bottom: none;
        }

        .ranking-position {
            font-size: 1.5rem;
            font-weight: bold;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .ranking-position-1 {
            background: gold;
            color: #333;
        }

        .ranking-position-2 {
            background: silver;
            color: #333;
        }

        .ranking-position-3 {
            background: #cd7f32; /* бронза */
            color: white;
        }

        .ranking-user {
            display: flex;
            flex: 1;
            align-items: center;
        }

        .ranking-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 1rem;
            object-fit: cover;
            border: 3px solid #ff4081;
        }

        .ranking-details {
            flex: 1;
        }

        .ranking-name {
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 0.25rem;
            color: #333;
        }

        .ranking-stats {
            display: flex;
            font-size: 0.9rem;
            color: #666;
        }

        .ranking-stat {
            margin-right: 1rem;
            display: flex;
            align-items: center;
        }

        .ranking-stat i {
            margin-right: 0.5rem;
            color: #ff4081;
        }

        .ranking-score {
            margin-left: auto;
            font-size: 1.2rem;
            font-weight: bold;
            color: #ff4081;
            background: #ffebf3;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-align: center;
        }

        .rating-stars {
            color: #ffd700;
            font-size: 0.9rem;
            margin-top: 0.25rem;
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

        .text-center {
            text-align: center;
        }

        .footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 2rem 0;
            margin-top: 2rem;
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
                <a href="index.php" class="nav-link animate__animated animate__fadeIn" style="font-weight: bold;">Рейтинг</a>
                <a href="../user/index.php?id=<?php echo $current_user_id; ?>" class="nav-link animate__animated animate__fadeIn">Профиль</a>
            </div>
        </nav>
    </header>

    <main class="main">
        <h1 class="section-title animate__animated animate__bounceIn">Рейтинг пользователей</h1>

        <div class="leaderboard animate__animated animate__fadeIn">
            <div class="leaderboard-header">
                <h2 class="leaderboard-title">Топ пользователей по рейтингу</h2>
                <p class="leaderboard-description">Пользователи с самым высоким рейтингом от других участников</p>
            </div>

            <div class="leaderboard-filter">
                <a href="index.php" class="filter-btn <?php echo !$gender_filter ? 'active' : ''; ?> animate__animated animate__fadeIn" style="animation-delay: 0.2s">Все</a>
                <a href="index.php?gender=женский" class="filter-btn <?php echo $gender_filter === 'женский' ? 'active' : ''; ?> animate__animated animate__fadeIn" style="animation-delay: 0.3s">Женщины</a>
                <a href="index.php?gender=мужской" class="filter-btn <?php echo $gender_filter === 'мужской' ? 'active' : ''; ?> animate__animated animate__fadeIn" style="animation-delay: 0.4s">Мужчины</a>
            </div>

            <div class="ranking-container">
                <?php
                $position = 1;
                $delay = 0.2;
                foreach ($users as $user):
                    $age = calculateAge($user['date_of_birth']);
                    $meetings_text = $user['meetings_count'] . ' ' . pluralize($user['meetings_count'], 'встреча', 'встречи', 'встреч');
                    $rating_display = $user['rating'] > 0 ? number_format($user['rating'], 1) . '/5' : 'Нет оценок';

                    // Класс для позиции
                    $position_class = '';
                    if ($position == 1) $position_class = 'ranking-position-1';
                    elseif ($position == 2) $position_class = 'ranking-position-2';
                    elseif ($position == 3) $position_class = 'ranking-position-3';

                    // Звёзды рейтинга
                    $full_stars = floor($user['rating']);
                    $half_star = ($user['rating'] - $full_stars) >= 0.5;
                ?>
                <div class="ranking-item animate__animated animate__fadeInUp" style="animation-delay: <?php echo $delay; ?>s">
                    <div class="ranking-position <?php echo $position_class; ?>"><?php echo $position; ?></div>
                    <div class="ranking-user">
                        <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="<?php echo htmlspecialchars($user['first_name']); ?>" class="ranking-avatar">
                        <div class="ranking-details">
                            <div class="ranking-name"><?php echo htmlspecialchars($user['first_name']); ?></div>
                            <div class="ranking-stats">
                                <div class="ranking-stat"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($user['city']); ?></div>
                                <div class="ranking-stat"><i class="fas fa-birthday-cake"></i> <?php echo $age; ?> <?php echo pluralize($age, 'год', 'года', 'лет'); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="ranking-score">
                        <div><?php echo $meetings_text; ?></div>
                        <?php if ($user['rating'] > 0): ?>
                        <div class="rating-stars">
                            <?php
                            for ($i = 0; $i < $full_stars; $i++) echo '<i class="fas fa-star"></i>';
                            if ($half_star) echo '<i class="fas fa-star-half-alt"></i>';
                            for ($i = 0; $i < (5 - $full_stars - ($half_star ? 1 : 0)); $i++) echo '<i class="far fa-star"></i>';
                            ?>
                            <span style="color: #333; margin-left: 0.25rem;"><?php echo $rating_display; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                    $position++;
                    $delay += 0.1;
                endforeach;

                if (empty($users)):
                ?>
                <div style="text-align: center; padding: 2rem; color: #666;">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>Пользователи не найдены</p>
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
