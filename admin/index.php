<?php
/**
 * Главная страница админ-панели
 * Показывает статистику и общую информацию о сайте
 */

require_once '../config/db.php';
require_once 'auth.php';

// Проверка авторизации
requireAuth();

// Получение статистики
$stats = [];

// Общее количество пользователей
$sql = "SELECT COUNT(*) as total,
               COUNT(CASE WHEN is_active = TRUE THEN 1 END) as active,
               COUNT(CASE WHEN is_active = FALSE THEN 1 END) as inactive
        FROM users";
$stmt = executeQuery($sql);
$stats['users'] = $stmt->fetch();

// Статистика встреч
$sql = "SELECT COUNT(*) as total,
               COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
               COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed,
               COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
               COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled
        FROM meetings";
$stmt = executeQuery($sql);
$stats['meetings'] = $stmt->fetch();

// Статистика лайков
$sql = "SELECT COUNT(*) as total FROM likes";
$stmt = executeQuery($sql);
$stats['likes'] = $stmt->fetch();

// Статистика отзывов
$sql = "SELECT COUNT(*) as total, CAST(AVG(rating) AS DECIMAL(3,2)) as avg_rating FROM meeting_reviews";
$stmt = executeQuery($sql);
$stats['reviews'] = $stmt->fetch();

// Последние зарегистрированные пользователи
$sql = "SELECT id, username, first_name, last_name, email, city, created_at, is_active
        FROM users
        ORDER BY created_at DESC
        LIMIT 10";
$recent_users = executeQuery($sql)->fetchAll();

// Ближайшие встречи
$sql = "SELECT m.id, m.meeting_date, m.location, m.status,
               CONCAT(u1.first_name, ' ', u1.last_name) as user1_name,
               CONCAT(u2.first_name, ' ', u2.last_name) as user2_name
        FROM meetings m
        JOIN users u1 ON m.user1_id = u1.id
        JOIN users u2 ON m.user2_id = u2.id
        WHERE m.meeting_date >= NOW()
        ORDER BY m.meeting_date ASC
        LIMIT 10";
$upcoming_meetings = executeQuery($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <title>Админ-панель - Dating Site</title>
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
                <a href="index.php" class="nav-link active">
                    <i class="fas fa-home"></i> Главная
                </a>
                <a href="users.php" class="nav-link">
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
            <h1><i class="fas fa-chart-line"></i> Панель управления</h1>
            <p>Добро пожаловать в админ-панель Dating Site. Здесь вы можете управлять всеми аспектами сайта.</p>
        </div>

        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon pink">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['users']['total']); ?></div>
                    <div class="stat-label">Всего пользователей</div>
                    <div style="font-size: 0.8rem; color: #999; margin-top: 0.25rem;">
                        Активных: <?php echo $stats['users']['active']; ?> |
                        Неактивных: <?php echo $stats['users']['inactive']; ?>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['meetings']['total']); ?></div>
                    <div class="stat-label">Всего встреч</div>
                    <div style="font-size: 0.8rem; color: #999; margin-top: 0.25rem;">
                        Завершено: <?php echo $stats['meetings']['completed']; ?> |
                        Ожидают: <?php echo $stats['meetings']['pending']; ?>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['likes']['total']); ?></div>
                    <div class="stat-label">Всего лайков</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['reviews']['total']); ?></div>
                    <div class="stat-label">Всего отзывов</div>
                    <div style="font-size: 0.8rem; color: #999; margin-top: 0.25rem;">
                        Средний рейтинг: <?php echo $stats['reviews']['avg_rating'] ?? '0.00'; ?> / 5.00
                    </div>
                </div>
            </div>
        </div>

        <!-- Последние пользователи -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-user-plus"></i> Последние зарегистрированные пользователи</h2>
                <a href="users.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-arrow-right"></i> Все пользователи
                </a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя пользователя</th>
                            <th>ФИО</th>
                            <th>Email</th>
                            <th>Город</th>
                            <th>Дата регистрации</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['city'] ?? '-'); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge badge-active">Активен</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">Неактивен</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="user_edit.php?id=<?php echo $user['id']; ?>"
                                           class="btn btn-info btn-sm" title="Редактировать">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_users)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: #999;">
                                    Пользователей пока нет
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ближайшие встречи -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-calendar-check"></i> Ближайшие встречи</h2>
                <a href="meetings.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-arrow-right"></i> Все встречи
                </a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Пользователь 1</th>
                            <th>Пользователь 2</th>
                            <th>Дата</th>
                            <th>Место</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_meetings as $meeting): ?>
                            <tr>
                                <td><?php echo $meeting['id']; ?></td>
                                <td><?php echo htmlspecialchars($meeting['user1_name']); ?></td>
                                <td><?php echo htmlspecialchars($meeting['user2_name']); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($meeting['meeting_date'])); ?></td>
                                <td><?php echo htmlspecialchars($meeting['location']); ?></td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'pending' => 'badge-pending',
                                        'confirmed' => 'badge-confirmed',
                                        'completed' => 'badge-completed',
                                        'cancelled' => 'badge-cancelled'
                                    ];
                                    $status_text = [
                                        'pending' => 'Ожидает',
                                        'confirmed' => 'Подтверждена',
                                        'completed' => 'Завершена',
                                        'cancelled' => 'Отменена'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $status_class[$meeting['status']]; ?>">
                                        <?php echo $status_text[$meeting['status']]; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="meetings.php?id=<?php echo $meeting['id']; ?>"
                                           class="btn btn-info btn-sm" title="Подробнее">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($upcoming_meetings)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #999;">
                                    Ближайших встреч нет
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
