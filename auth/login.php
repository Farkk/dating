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

    .divider {
      display: flex;
      align-items: center;
      gap: 15px;
      margin: 30px 0;
    }

    .divider::before,
    .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: #ddd;
    }

    .divider span {
      color: #999;
      font-size: 14px;
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

    .features {
      margin-top: 30px;
    }

    .feature {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 15px;
      color: #555;
      font-size: 14px;
    }

    .feature i {
      color: #ff4081;
      font-size: 18px;
      width: 20px;
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
    require_once '../config/vk_config.php';

    // Формируем URL для авторизации ВКонтакте
    $vk_auth_url = 'https://oauth.vk.com/authorize?' . http_build_query([
      'client_id' => VK_APP_ID,
      'redirect_uri' => VK_REDIRECT_URI,
      'display' => 'page',
      'scope' => 'email,photos', // Запрашиваем доступ к email и фотографиям
      'response_type' => 'code',
      'v' => VK_API_VERSION
    ]);
    ?>

    <a href="<?= htmlspecialchars($vk_auth_url) ?>" class="vk-button">
      <i class="fab fa-vk"></i>
      Войти через ВКонтакте
    </a>

    <div class="features">
      <div class="feature">
        <i class="fas fa-shield-alt"></i>
        <span>Быстрая и безопасная авторизация</span>
      </div>
      <div class="feature">
        <i class="fas fa-user-check"></i>
        <span>Автоматическое заполнение профиля</span>
      </div>
      <div class="feature">
        <i class="fas fa-heart"></i>
        <span>Найди свою любовь уже сегодня</span>
      </div>
    </div>

    <div class="info-text">
      Нажимая "Войти через ВКонтакте", вы соглашаетесь с использованием данных из вашего профиля ВК для создания аккаунта на нашем сайте.
    </div>
  </div>
</body>

</html>
