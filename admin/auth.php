<?php
/**
 * Хелпер для проверки авторизации администратора
 * Этот файл подключается на всех страницах админки (кроме login.php)
 */

// Запускаем сессию, если она еще не запущена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Проверка авторизации администратора
 * Если администратор не авторизован, редирект на страницу входа
 */
function requireAuth() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
        // Сохраняем текущий URL для возврата после авторизации
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit;
    }
}

/**
 * Проверка, авторизован ли администратор
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

/**
 * Получить ID текущего администратора
 * @return int|null
 */
function getCurrentAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

/**
 * Получить username текущего администратора
 * @return string|null
 */
function getCurrentAdminUsername() {
    return $_SESSION['admin_username'] ?? null;
}

/**
 * Авторизация администратора
 * @param int $admin_id
 * @param string $username
 */
function loginAdmin($admin_id, $username) {
    $_SESSION['admin_id'] = $admin_id;
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_login_time'] = time();
}

/**
 * Выход из системы
 */
function logoutAdmin() {
    // Удаляем все переменные сессии
    $_SESSION = array();

    // Уничтожаем сессию
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-42000, '/');
    }

    session_destroy();
}

/**
 * Генерация CSRF токена
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Проверка CSRF токена
 * @param string $token
 * @return bool
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
