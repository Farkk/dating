<?php
session_start();

// Если пользователь не авторизован, перенаправляем на страницу входа
if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: /auth/login.php');
    exit;
}

// Если авторизован, показываем главную страницу
require_once 'config/db.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT first_name, last_name, profile_photo FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Dating Site - Главная страница</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #f6e6ea 100%);
            color: #333;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: 700;
            color: #ff4081;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ff4081;
        }

        .user-name {
            font-weight: 600;
            color: #333;
        }

        .logout-btn {
            padding: 8px 16px;
            background: #f0f0f0;
            color: #333;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #e0e0e0;
        }

        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 2rem;
        }

        .welcome-section {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-bottom: 40px;
        }

        h1 {
            font-size: 2.5rem;
            color: #ff4081;
            margin-bottom: 1rem;
        }

        .subtitle {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
        }

        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .nav-card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .nav-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(255, 64, 129, 0.3);
        }

        .nav-icon {
            font-size: 60px;
            color: #ff4081;
        }

        .nav-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
        }

        .nav-description {
            font-size: 1rem;
            color: #666;
            text-align: center;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                padding: 1rem;
            }

            h1 {
                font-size: 2rem;
            }

            .nav-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <i class="fas fa-heart"></i>
            <span>Dating Site</span>
        </div>
        <div class="user-menu">
            <div class="user-info">
                <?php if (!empty($user['profile_photo'])): ?>
                    <img src="/<?= htmlspecialchars($user['profile_photo']) ?>" alt="Аватар" class="user-avatar">
                <?php else: ?>
                    <div class="user-avatar" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user" style="color: #999;"></i>
                    </div>
                <?php endif; ?>
                <span class="user-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span>
            </div>
            <a href="/profile/edit.php" class="logout-btn">
                <i class="fas fa-user-edit"></i> Профиль
            </a>
            <a href="/auth/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Выйти
            </a>
        </div>
    </div>

    <div class="container">
        <div class="welcome-section animate__animated animate__fadeIn">
            <h1><i class="fas fa-heart"></i> Добро пожаловать!</h1>
            <p class="subtitle">Найди свою вторую половинку прямо сейчас</p>
        </div>

        <div class="nav-grid">
            <a href="/find/index.php" class="nav-card animate__animated animate__fadeInUp">
                <div class="nav-icon">
                    <i class="fas fa-search"></i>
                </div>
                <div class="nav-title">Поиск анкет</div>
                <div class="nav-description">
                    Просматривайте профили других пользователей и находите тех, кто вам интересен
                </div>
            </a>

            <a href="/rating/index.php" class="nav-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
                <div class="nav-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="nav-title">Рейтинг</div>
                <div class="nav-description">
                    Посмотрите топ пользователей с самыми высокими рейтингами
                </div>
            </a>

            <a href="/user/index.php?id=<?= $user_id ?>" class="nav-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
                <div class="nav-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="nav-title">Мой профиль</div>
                <div class="nav-description">
                    Просмотрите свой профиль так, как его видят другие пользователи
                </div>
            </a>
        </div>
    </div>
</body>
</html>
