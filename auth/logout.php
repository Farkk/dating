<?php
session_start();

require_once '../config/db.php';

// Удаляем сессию из базы данных
if (isset($_SESSION['session_token'])) {
    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_token = :token");
    $stmt->execute(['token' => $_SESSION['session_token']]);
}

// Уничтожаем PHP сессию
session_destroy();

// Перенаправляем на страницу входа
header('Location: /auth/login.php');
exit;
