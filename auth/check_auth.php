<?php
// Проверка авторизации пользователя

if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    header('Location: /auth/login.php');
    exit;
}

// Проверяем валидность сессии в базе данных
if (isset($_SESSION['session_token'])) {
    $stmt = $pdo->prepare("
        SELECT * FROM user_sessions
        WHERE session_token = :token
        AND user_id = :user_id
        AND expires_at > NOW()
        LIMIT 1
    ");

    $stmt->execute([
        'token' => $_SESSION['session_token'],
        'user_id' => $_SESSION['user_id']
    ]);

    $session = $stmt->fetch();

    if (!$session) {
        // Сессия истекла или недействительна
        session_destroy();
        header('Location: /auth/login.php');
        exit;
    }
}
