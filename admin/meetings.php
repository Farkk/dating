<?php
/**
 * Страница управления встречами
 * Показывает список всех встреч с возможностью изменения статуса и удаления
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
        // Удаление встречи
        if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['meeting_id'])) {
            $meeting_id = (int)$_POST['meeting_id'];
            try {
                $sql = "DELETE FROM meetings WHERE id = :id";
                executeQuery($sql, ['id' => $meeting_id]);
                $success_message = 'Встреча успешно удалена';
            } catch (Exception $e) {
                $error_message = 'Ошибка при удалении встречи: ' . $e->getMessage();
            }
        }

        // Изменение статуса встречи
        if (isset($_POST['action']) && $_POST['action'] === 'change_status' &&
            isset($_POST['meeting_id']) && isset($_POST['new_status'])) {
            $meeting_id = (int)$_POST['meeting_id'];
            $new_status = $_POST['new_status'];

            // Проверка допустимых статусов
            $allowed_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
            if (in_array($new_status, $allowed_statuses)) {
                try {
                    $sql = "UPDATE meetings SET status = :status, updated_at = CURRENT_TIMESTAMP
                            WHERE id = :id";
                    executeQuery($sql, ['status' => $new_status, 'id' => $meeting_id]);
                    $success_message = 'Статус встречи успешно изменен';
                } catch (Exception $e) {
                    $error_message = 'Ошибка при изменении статуса: ' . $e->getMessage();
                }
            } else {
                $error_message = 'Недопустимый статус';
            }
        }
    }
}

// Получение параметров фильтрации
$status_filter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Построение SQL-запроса с фильтрами
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "m.status = :status";
    $params['status'] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Получение общего количества встреч
$count_sql = "SELECT COUNT(*) as total FROM meetings m $where_clause";
$total_meetings = executeQuery($count_sql, $params)->fetch()['total'];
$total_pages = ceil($total_meetings / $per_page);

// Получение списка встреч
$sql = "SELECT m.id, m.user1_id, m.user2_id, m.status, m.meeting_date, m.location, m.notes, m.created_at,
               u1.first_name || ' ' || u1.last_name as user1_name,
               u1.username as user1_username,
               u2.first_name || ' ' || u2.last_name as user2_name,
               u2.username as user2_username
        FROM meetings m
        JOIN users u1 ON m.user1_id = u1.id
        JOIN users u2 ON m.user2_id = u2.id
        $where_clause
        ORDER BY m.meeting_date DESC
        LIMIT :limit OFFSET :offset";
$params['limit'] = $per_page;
$params['offset'] = $offset;
$meetings = executeQuery($sql, $params)->fetchAll();

// Статистика по статусам
$stats_sql = "SELECT
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled
              FROM meetings";
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
    <title>Встречи - Админ-панель</title>
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
                <a href="meetings.php" class="nav-link active">
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
            <h1><i class="fas fa-calendar"></i> Управление встречами</h1>
            <p>Просмотр и управление встречами пользователей</p>
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
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                    <div class="stat-label">Всего встреч</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['pending']); ?></div>
                    <div class="stat-label">Ожидают</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['confirmed']); ?></div>
                    <div class="stat-label">Подтверждены</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['completed']); ?></div>
                    <div class="stat-label">Завершены</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon pink">
                    <i class="fas fa-times"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['cancelled']); ?></div>
                    <div class="stat-label">Отменены</div>
                </div>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-filter"></i> Фильтры</h2>
            </div>

            <form method="GET" action="meetings.php">
                <div class="filters">
                    <div class="filter-group">
                        <label>Статус</label>
                        <select name="status" class="form-control">
                            <option value="">Все статусы</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>
                                Ожидает
                            </option>
                            <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>
                                Подтверждена
                            </option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>
                                Завершена
                            </option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>
                                Отменена
                            </option>
                        </select>
                    </div>

                    <div class="filter-group" style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Применить
                        </button>
                        <a href="meetings.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Сбросить
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Таблица встреч -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Список встреч</h2>
                <span style="color: #666;">Всего: <strong><?php echo $total_meetings; ?></strong></span>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Пользователь 1</th>
                            <th>Пользователь 2</th>
                            <th>Дата встречи</th>
                            <th>Место</th>
                            <th>Статус</th>
                            <th>Создана</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meetings as $meeting): ?>
                            <tr>
                                <td><?php echo $meeting['id']; ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($meeting['user1_name']); ?></strong><br>
                                        <small style="color: #666;">
                                            @<?php echo htmlspecialchars($meeting['user1_username']); ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($meeting['user2_name']); ?></strong><br>
                                        <small style="color: #666;">
                                            @<?php echo htmlspecialchars($meeting['user2_username']); ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($meeting['meeting_date']): ?>
                                        <?php echo date('d.m.Y H:i', strtotime($meeting['meeting_date'])); ?>
                                    <?php else: ?>
                                        <span style="color: #999;">Не назначена</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($meeting['location'] ?? '-'); ?></td>
                                <td>
                                    <form method="POST" style="margin: 0;" onchange="this.submit()">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="change_status">
                                        <input type="hidden" name="meeting_id" value="<?php echo $meeting['id']; ?>">
                                        <select name="new_status" class="form-control" style="padding: 0.35rem 0.65rem; font-size: 0.85rem;">
                                            <option value="pending" <?php echo $meeting['status'] === 'pending' ? 'selected' : ''; ?>>
                                                Ожидает
                                            </option>
                                            <option value="confirmed" <?php echo $meeting['status'] === 'confirmed' ? 'selected' : ''; ?>>
                                                Подтверждена
                                            </option>
                                            <option value="completed" <?php echo $meeting['status'] === 'completed' ? 'selected' : ''; ?>>
                                                Завершена
                                            </option>
                                            <option value="cancelled" <?php echo $meeting['status'] === 'cancelled' ? 'selected' : ''; ?>>
                                                Отменена
                                            </option>
                                        </select>
                                    </form>
                                </td>
                                <td><?php echo date('d.m.Y', strtotime($meeting['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <?php if ($meeting['notes']): ?>
                                            <button type="button"
                                                    class="btn btn-info btn-sm"
                                                    title="<?php echo htmlspecialchars($meeting['notes']); ?>"
                                                    onclick="alert('Заметки:\n\n<?php echo htmlspecialchars(str_replace(["\r", "\n"], [' ', '\n'], $meeting['notes'])); ?>')">
                                                <i class="fas fa-sticky-note"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button"
                                                class="btn btn-danger btn-sm"
                                                title="Удалить"
                                                onclick="deleteMeeting(<?php echo $meeting['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($meetings)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: #999; padding: 2rem;">
                                    <i class="fas fa-calendar-times" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                    <p>Встречи не найдены</p>
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
                    <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>">
                        <i class="fas fa-chevron-left"></i> Назад
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>">
                        Вперед <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Форма для удаления встречи (скрытая) -->
    <form id="delete-form" method="POST" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="meeting_id" id="delete-meeting-id">
    </form>

    <script>
        // Функция удаления встречи
        function deleteMeeting(meetingId) {
            if (confirm('Вы уверены, что хотите удалить эту встречу? Это действие нельзя отменить.')) {
                document.getElementById('delete-meeting-id').value = meetingId;
                document.getElementById('delete-form').submit();
            }
        }
    </script>
</body>
</html>
