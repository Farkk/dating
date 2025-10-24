<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - Сайт знакомств</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px 40px;
            max-width: 400px;
            width: 100%;
        }

        .logo {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo i {
            font-size: 60px;
            color: #ff4081;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 40px;
            font-size: 14px;
        }

        .vk-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            background: #0077FF;
            color: white;
            padding: 15px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 119, 255, 0.3);
        }

        .vk-button:hover {
            background: #0066DD;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 119, 255, 0.4);
        }

        .vk-button i {
            font-size: 24px;
        }

        .info-text {
            text-align: center;
            color: #666;
            font-size: 13px;
            line-height: 1.6;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }

        .error-box {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-heart"></i>
        </div>

        <h1>Добро пожаловать!</h1>
        <p class="subtitle">Найди свою вторую половинку</p>

        <?php
        // Проверяем, существует ли файл конфигурации
        $config_file = '../config/vk_config.php';
        if (!file_exists($config_file)) {
            echo '<div class="error-box">⚠️ Ошибка: Файл конфигурации ВК не найден. Проверьте, что файл <code>config/vk_config.php</code> загружен на сервер.</div>';
            $vk_auth_url = '#';
        } else {
            require_once $config_file;

            // Формируем URL для авторизации ВКонтакте
            $vk_auth_url = 'https://oauth.vk.com/authorize?' . http_build_query([
                'client_id' => VK_APP_ID,
                'redirect_uri' => VK_REDIRECT_URI,
                'display' => 'page',
                'scope' => 'email,photos',
                'response_type' => 'code',
                'v' => VK_API_VERSION
            ]);
        }
        ?>

        <a href="<?= htmlspecialchars($vk_auth_url) ?>" class="vk-button">
            <i class="fab fa-vk"></i>
            Войти через ВКонтакте
        </a>

        <div class="info-text">
            <p><strong>Статус проверки:</strong></p>
            <p style="margin-top: 10px;">
                PHP работает: ✅<br>
                Версия PHP: <?= phpversion() ?><br>
                <?php if (file_exists($config_file)): ?>
                    Конфигурация ВК: ✅
                <?php else: ?>
                    Конфигурация ВК: ❌ Файл не найден
                <?php endif; ?>
            </p>
        </div>
    </div>
</body>
</html>
