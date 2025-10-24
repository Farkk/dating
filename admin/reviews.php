<?php
/**
 * Страница управления отзывами
 * Показывает список всех отзывов с возможностью фильтрации и удаления
 */

require_once '../config/db.php';
require_once 'auth.php';

// Проверка авторизации
requireAuth();

// Обработка POST-запросов
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF токена
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Ошибка валидации CSRF токена';
    } else {
        // Удаление отзыва
        if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['review_id'])) {
            $review_id = (int)$_POST['review_id'];
            try {
                // Получаем информацию о пользователе перед удалением отзыва для пересчета рейтинга
                $sql = "SELECT reviewed_user_id FROM meeting_reviews WHERE id = :id";
                $review = executeQuery($sql, ['id' => $review_id])->fetch();

                if ($review) {
                    // Удаляем отзыв
                    $sql = "DELETE FROM meeting_reviews WHERE id = :id";
                    executeQuery($sql, ['id' => $review_id]);

                    // Пересчитываем рейтинг пользователя
                    $sql = "UPDATE users SET rating = (
                                SELECT COALESCE(AVG(rating)::DECIMAL(3,2), 0.00)
                                FROM meeting_reviews
                                WHERE reviewed_user_id = :user_id
                            )
                            WHERE id = :user_id";
                    executeQuery($sql, ['user_id' => $review['reviewed_user_id']]);

                    $success_message = 'Отзыв успешно удален, рейтинг пользователя пересчитан';
                }
            } catch (Exception $e) {
                $error_message = 'Ошибка при удалении отзыва: ' . $e->getMessage();
            }
        }
    }
}

// Получение параметров фильтрации
$rating_filter = $_GET['rating'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Построение SQL-запроса с фильтрами
$where_conditions = [];
$params = [];

if (!empty($rating_filter)) {
    $where_conditions[] = "mr.rating = :rating";
    $params['rating'] = (int)$rating_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Получение общего количества отзывов
$count_sql = "SELECT COUNT(*) as total FROM meeting_reviews mr $where_clause";
$total_reviews = executeQuery($count_sql, $params)->fetch()['total'];
$total_pages = ceil($total_reviews / $per_page);

// Получение списка отзывов
$sql = "SELECT mr.id, mr.meeting_id, mr.rating, mr.comments, mr.created_at,
               reviewer.id as reviewer_id,
               reviewer.first_name || ' ' || reviewer.last_name as reviewer_name,
               reviewer.username as reviewer_username,
               reviewed.id as reviewed_id,
               reviewed.first_name || ' ' || reviewed.last_name as reviewed_name,
               reviewed.username as reviewed_username,
               m.meeting_date, m.location
        FROM meeting_reviews mr
        JOIN users reviewer ON mr.reviewer_id = reviewer.id
        JOIN users reviewed ON mr.reviewed_user_id = reviewed.id
        JOIN meetings m ON mr.meeting_id = m.id
        $where_clause
        ORDER BY mr.created_at DESC
        LIMIT :limit OFFSET :offset";
$params['limit'] = $per_page;
$params['offset'] = $offset;
$reviews = executeQuery($sql, $params)->fetchAll();

// Статистика по рейтингам
$stats_sql = "SELECT
                COUNT(*) as total,
                AVG(rating)::DECIMAL(3,2) as avg_rating,
                COUNT(CASE WHEN rating = 5 THEN 1 END) as rating_5,
                COUNT(CASE WHEN rating = 4 THEN 1 END) as rating_4,
                COUNT(CASE WHEN rating = 3 THEN 1 END) as rating_3,
                COUNT(CASE WHEN rating = 2 THEN 1 END) as rating_2,
                COUNT(CASE WHEN rating = 1 THEN 1 END) as rating_1
              FROM meeting_reviews";
$stats = executeQuery($stats_sql)->fetch();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <title>Отзывы - Админ-панель</title>
    <style>
        .stars {
            color: #ffc107;
            font-size: 1.1rem;
        }
        .rating-bar {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 0.5rem 0;
        }
        .rating-bar-label {
            min-width: 60px;
            color: #666;
        }
        .rating-bar-progress {
            flex: 1;
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        .rating-bar-fill {
            height: 100%;
            background: linear-gradient(135deg, #ffc107, #ffb300);
            transition: width 0.3s ease;
        }
        .rating-bar-count {
            min-width: 50px;
            text-align: right;
            font-weight: 600;
            color: #333;
        }
    </style>
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
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i> Пользователи
                </a>
                <a href="meetings.php" class="nav-link">
                    <i class="fas fa-calendar"></i> Встречи
                </a>
                <a href="reviews.php" class="nav-link active">
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
            <h1><i class="fas fa-star"></i> Управление отзывами</h1>
            <p>Просмотр и модерация отзывов о встречах</p>
        </div>

        <!-- Сообщения об успехе/ошибках -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Статистика -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-chart-bar"></i> Статистика отзывов</h2>
            </div>

            <div style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem;">
                <!-- Общая статистика -->
                <div>
                    <div style="text-align: center; margin-bottom: 1rem;">
                        <div style="font-size: 3rem; font-weight: bold; color: #ff4081;">
                            <?php echo $stats['avg_rating'] ?? '0.00'; ?>
                        </div>
                        <div class="stars" style="font-size: 1.5rem;">
                            <?php
                            $avg = $stats['avg_rating'] ?? 0;
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= floor($avg)) {
                                    echo '<i class="fas fa-star"></i>';
                                } elseif ($i <= ceil($avg) && $avg - floor($avg) >= 0.5) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <div style="color: #666; margin-top: 0.5rem;">
                            Средний рейтинг
                        </div>
                        <div style="font-weight: 600; margin-top: 0.5rem; color: #333;">
                            Всего отзывов: <?php echo number_format($stats['total']); ?>
                        </div>
                    </div>
                </div>

                <!-- Распределение по рейтингам -->
                <div>
                    <?php
                    $total = $stats['total'] ?: 1; // Избегаем деления на ноль
                    for ($i = 5; $i >= 1; $i--):
                        $count = $stats["rating_$i"];
                        $percentage = ($count / $total) * 100;
                    ?>
                        <div class="rating-bar">
                            <div class="rating-bar-label">
                                <?php echo $i; ?> <i class="fas fa-star" style="color: #ffc107;"></i>
                            </div>
                            <div class="rating-bar-progress">
                                <div class="rating-bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <div class="rating-bar-count">
                                <?php echo number_format($count); ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-filter"></i> Фильтры</h2>
            </div>

            <form method="GET" action="reviews.php">
                <div class="filters">
                    <div class="filter-group">
                        <label>Рейтинг</label>
                        <select name="rating" class="form-control">
                            <option value="">Все рейтинги</option>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php echo $rating_filter == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?> <?php echo str_repeat('★', $i); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="filter-group" style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Применить
                        </button>
                        <a href="reviews.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Сбросить
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Таблица отзывов -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Список отзывов</h2>
                <span style="color: #666;">Всего: <strong><?php echo $total_reviews; ?></strong></span>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ID встречи</th>
                            <th>Автор отзыва</th>
                            <th>О пользователе</th>
                            <th>Рейтинг</th>
                            <th>Комментарий</th>
                            <th>Дата отзыва</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td><?php echo $review['id']; ?></td>
                                <td>
                                    <div>
                                        <strong>#<?php echo $review['meeting_id']; ?></strong>
                                        <?php if ($review['meeting_date']): ?>
                                            <br>
                                            <small style="color: #666;">
                                                <?php echo date('d.m.Y', strtotime($review['meeting_date'])); ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if ($review['location']): ?>
                                            <br>
                                            <small style="color: #666;">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?php echo htmlspecialchars(substr($review['location'], 0, 30)); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($review['reviewer_name']); ?></strong><br>
                                        <small style="color: #666;">
                                            @<?php echo htmlspecialchars($review['reviewer_username']); ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($review['reviewed_name']); ?></strong><br>
                                        <small style="color: #666;">
                                            @<?php echo htmlspecialchars($review['reviewed_username']); ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div class="stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $review['rating']): ?>
                                                <i class="fas fa-star"></i>
                                            <?php else: ?>
                                                <i class="far fa-star" style="color: #ddd;"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <div style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">
                                        <?php echo $review['rating']; ?>/5
                                    </div>
                                </td>
                                <td>
                                    <?php if ($review['comments']): ?>
                                        <div style="max-width: 300px;">
                                            <?php
                                            $comment = htmlspecialchars($review['comments']);
                                            if (mb_strlen($comment) > 100) {
                                                echo mb_substr($comment, 0, 100) . '...';
                                            } else {
                                                echo $comment;
                                            }
                                            ?>
                                        </div>
                                        <?php if (mb_strlen($review['comments']) > 100): ?>
                                            <button type="button"
                                                    class="btn btn-info btn-sm"
                                                    style="margin-top: 0.5rem;"
                                                    onclick="alert('<?php echo htmlspecialchars(str_replace(["\r", "\n", "'"], [' ', '\n', "\\'"], $review['comments'])); ?>')">
                                                <i class="fas fa-eye"></i> Читать полностью
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">Без комментария</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($review['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <button type="button"
                                                class="btn btn-danger btn-sm"
                                                title="Удалить"
                                                onclick="deleteReview(<?php echo $review['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($reviews)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: #999; padding: 2rem;">
                                    <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                    <p>Отзывы не найдены</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Пагинация -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&rating=<?php echo urlencode($rating_filter); ?>">
                        <i class="fas fa-chevron-left"></i> Назад
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&rating=<?php echo urlencode($rating_filter); ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&rating=<?php echo urlencode($rating_filter); ?>">
                        Вперед <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Форма для удаления отзыва (скрытая) -->
    <form id="delete-form" method="POST" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="review_id" id="delete-review-id">
    </form>

    <script>
        // Функция удаления отзыва
        function deleteReview(reviewId) {
            if (confirm('Вы уверены, что хотите удалить этот отзыв? Рейтинг пользователя будет пересчитан. Это действие нельзя отменить.')) {
                document.getElementById('delete-review-id').value = reviewId;
                document.getElementById('delete-form').submit();
            }
        }
    </script>
</body>
</html>
