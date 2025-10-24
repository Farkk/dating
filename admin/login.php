<?php
/**
 * Страница входа в админ-панель
 */

// Подключаем конфигурацию базы данных и хелпер авторизации
require_once '../config/db.php';
require_once 'auth.php';

// Если администратор уже авторизован, редирект на главную страницу админки
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } else {
        // Проверяем наличие администратора в базе данных
        $sql = "SELECT id, username, password_hash FROM admins WHERE username = :username";
        $stmt = executeQuery($sql, [':username' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Авторизация успешна
            loginAdmin($admin['id'], $admin['username']);

            // Редирект на сохраненную страницу или на главную страницу админки
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);

            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Неверное имя пользователя или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Вход в админ-панель - Dating Site</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #f6e6ea 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .login-container {
            max-width: 450px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, #ff4081, #ff87b2);
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo i {
            font-size: 4rem;
            color: #ff4081;
            margin-bottom: 1rem;
        }

        h1 {
            text-align: center;
            color: #ff4081;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #ff4081;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #ff4081;
        }

        .btn {
            width: 100%;
            padding: 1rem;
            background: #ff4081;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
            font-family: 'Inter', sans-serif;
        }

        .btn:hover {
            background: #e6366f;
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .alert-error {
            background: #ffe5e5;
            color: #cc0000;
            border: 1px solid #ffcccc;
        }

        .alert-success {
            background: #e5ffe5;
            color: #006600;
            border: 1px solid #ccffcc;
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-link a {
            color: #ff4081;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #e6366f;
        }

        .info-box {
            background: #f9f9f9;
            border-left: 4px solid #ff4081;
            padding: 1rem;
            margin-top: 1.5rem;
            border-radius: 5px;
            font-size: 0.85rem;
            color: #666;
        }

        .info-box strong {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-shield-alt"></i>
        </div>

        <h1>Админ-панель</h1>
        <p class="subtitle">Dating Site</p>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Имя пользователя</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" required autofocus
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Пароль</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required>
                </div>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i> Войти
            </button>
        </form>

        <div class="back-link">
            <a href="../index.html">
                <i class="fas fa-arrow-left"></i> Вернуться на главную
            </a>
        </div>

        <div class="info-box">
            <strong>Тестовые данные для входа:</strong><br>
            Логин: admin<br>
            Пароль: admin123
        </div>
    </div>
</body>
</html>
