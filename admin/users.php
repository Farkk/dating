<?php
/**
 * Страница управления пользователями
 * Показывает список всех пользователей с возможностью фильтрации, поиска и массовых операций
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
        // Удаление пользователя
        if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['user_id'])) {
            $user_id = (int)$_POST['user_id'];
            try {
                $sql = "DELETE FROM users WHERE id = :id";
                executeQuery($sql, ['id' => $user_id]);
                $success_message = 'Пользователь успешно удален';
            } catch (Exception $e) {
                $error_message = 'Ошибка при удалении пользователя: ' . $e->getMessage();
            }
        }

        // Массовые операции
        if (isset($_POST['bulk_action']) && isset($_POST['selected_users']) && is_array($_POST['selected_users'])) {
            $selected_users = array_map('intval', $_POST['selected_users']);
            $bulk_action = $_POST['bulk_action'];

            if (!empty($selected_users)) {
                try {
                    $placeholders = implode(',', array_fill(0, count($selected_users), '?'));

                    if ($bulk_action === 'activate') {
                        $sql = "UPDATE users SET is_active = TRUE WHERE id IN ($placeholders)";
                        executeQuery($sql, $selected_users);
                        $success_message = 'Выбранные пользователи успешно активированы';
                    } elseif ($bulk_action === 'deactivate') {
                        $sql = "UPDATE users SET is_active = FALSE WHERE id IN ($placeholders)";
                        executeQuery($sql, $selected_users);
                        $success_message = 'Выбранные пользователи успешно деактивированы';
                    } elseif ($bulk_action === 'delete') {
                        $sql = "DELETE FROM users WHERE id IN ($placeholders)";
                        executeQuery($sql, $selected_users);
                        $success_message = 'Выбранные пользователи успешно удалены';
                    }
                } catch (Exception $e) {
                    $error_message = 'Ошибка при выполнении массовой операции: ' . $e->getMessage();
                }
            }
        }
    }
}

// Получение параметров фильтрации и поиска
$search = $_GET['search'] ?? '';
$gender_filter = $_GET['gender'] ?? '';
$city_filter = $_GET['city'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Построение SQL-запроса с фильтрами
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(first_name ILIKE :search OR last_name ILIKE :search OR email ILIKE :search OR username ILIKE :search)";
    $params['search'] = '%' . $search . '%';
}

if (!empty($gender_filter)) {
    $where_conditions[] = "gender = :gender";
    $params['gender'] = $gender_filter;
}

if (!empty($city_filter)) {
    $where_conditions[] = "city = :city";
    $params['city'] = $city_filter;
}

if ($status_filter !== '') {
    if ($status_filter === 'active') {
        $where_conditions[] = "is_active = TRUE";
    } elseif ($status_filter === 'inactive') {
        $where_conditions[] = "is_active = FALSE";
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Получение общего количества пользователей
$count_sql = "SELECT COUNT(*) as total FROM users $where_clause";
$total_users = executeQuery($count_sql, $params)->fetch()['total'];
$total_pages = ceil($total_users / $per_page);

// Получение списка пользователей
$sql = "SELECT id, username, email, first_name, last_name, date_of_birth, gender, city,
               rating, is_active, created_at
        FROM users
        $where_clause
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset";
$params['limit'] = $per_page;
$params['offset'] = $offset;
$users = executeQuery($sql, $params)->fetchAll();

// Получение списка городов для фильтра
$cities_sql = "SELECT DISTINCT city FROM users WHERE city IS NOT NULL AND city != '' ORDER BY city";
$cities = executeQuery($cities_sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <title>Пользователи - Админ-панель</title>
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
            <h1><i class="fas fa-users"></i> Управление пользователями</h1>
            <p>Просмотр, редактирование и управление пользователями сайта знакомств</p>
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

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-filter"></i> Фильтры и поиск</h2>
                <a href="user_edit.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить пользователя
                </a>
            </div>

            <!-- Форма фильтрации и поиска -->
            <form method="GET" action="users.php">
                <div class="filters">
                    <div class="filter-group">
                        <label>Поиск</label>
                        <input type="text" name="search" class="form-control"
                               placeholder="Имя, email, username..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="filter-group">
                        <label>Пол</label>
                        <select name="gender" class="form-control">
                            <option value="">Все</option>
                            <option value="мужской" <?php echo $gender_filter === 'мужской' ? 'selected' : ''; ?>>Мужской</option>
                            <option value="женский" <?php echo $gender_filter === 'женский' ? 'selected' : ''; ?>>Женский</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Город</label>
                        <select name="city" class="form-control">
                            <option value="">Все</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo htmlspecialchars($city['city']); ?>"
                                        <?php echo $city_filter === $city['city'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($city['city']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Статус</label>
                        <select name="status" class="form-control">
                            <option value="">Все</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Активен</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Неактивен</option>
                        </select>
                    </div>

                    <div class="filter-group" style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Применить
                        </button>
                        <a href="users.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Сбросить
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Массовые операции -->
        <div class="card">
            <form method="POST" id="bulk-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; align-items: center;">
                    <label style="margin: 0;">
                        <input type="checkbox" id="select-all" class="checkbox"> Выбрать все
                    </label>
                    <select name="bulk_action" class="form-control" style="max-width: 200px;">
                        <option value="">Массовые операции...</option>
                        <option value="activate">Активировать</option>
                        <option value="deactivate">Деактивировать</option>
                        <option value="delete">Удалить</option>
                    </select>
                    <button type="submit" class="btn btn-warning"
                            onclick="return confirm('Вы уверены, что хотите выполнить эту операцию?')">
                        <i class="fas fa-bolt"></i> Применить
                    </button>
                    <span style="color: #666; margin-left: auto;">
                        Всего пользователей: <strong><?php echo $total_users; ?></strong>
                    </span>
                </div>

                <!-- Таблица пользователей -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="select-all-header" class="checkbox">
                                </th>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Имя</th>
                                <th>Email</th>
                                <th>Пол</th>
                                <th>Город</th>
                                <th>Возраст</th>
                                <th>Рейтинг</th>
                                <th>Статус</th>
                                <th>Дата регистрации</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php
                                // Вычисление возраста
                                $birth_date = new DateTime($user['date_of_birth']);
                                $today = new DateTime();
                                $age = $today->diff($birth_date)->y;
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected_users[]"
                                               value="<?php echo $user['id']; ?>"
                                               class="checkbox user-checkbox">
                                    </td>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($user['city'] ?? '-'); ?></td>
                                    <td><?php echo $age; ?> лет</td>
                                    <td>
                                        <span style="color: #ffc107;">
                                            <i class="fas fa-star"></i>
                                            <?php echo number_format($user['rating'], 1); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <span class="badge badge-active">Активен</span>
                                        <?php else: ?>
                                            <span class="badge badge-inactive">Неактивен</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="user_edit.php?id=<?php echo $user['id']; ?>"
                                               class="btn btn-info btn-sm" title="Редактировать">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-danger btn-sm"
                                                    title="Удалить"
                                                    onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="12" style="text-align: center; color: #999; padding: 2rem;">
                                        <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                        <p>Пользователи не найдены</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>

        <!-- Пагинация -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&gender=<?php echo urlencode($gender_filter); ?>&city=<?php echo urlencode($city_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                        <i class="fas fa-chevron-left"></i> Назад
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&gender=<?php echo urlencode($gender_filter); ?>&city=<?php echo urlencode($city_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&gender=<?php echo urlencode($gender_filter); ?>&city=<?php echo urlencode($city_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                        Вперед <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Форма для удаления пользователя (скрытая) -->
    <form id="delete-form" method="POST" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" id="delete-user-id">
    </form>

    <script>
        // Функция удаления пользователя
        function deleteUser(userId) {
            if (confirm('Вы уверены, что хотите удалить этого пользователя? Это действие нельзя отменить.')) {
                document.getElementById('delete-user-id').value = userId;
                document.getElementById('delete-form').submit();
            }
        }

        // Выбрать все чекбоксы
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });

        document.getElementById('select-all-header').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
            document.getElementById('select-all').checked = this.checked;
        });
    </script>
</body>
</html>
