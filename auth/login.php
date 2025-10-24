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

    #vk-auth-widget {
      display: flex;
      justify-content: center;
      margin: 20px 0;
      min-height: 48px;
    }

    #vk-auth-widget iframe {
      border-radius: 8px !important;
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

    <?php require_once '../config/vk_config.php'; ?>

    <!-- VK ID SDK Widget Container -->
    <div id="vk-auth-widget"></div>

    <script src="https://unpkg.com/@vkid/sdk@<3.0.0/dist-sdk/umd/index.js"></script>
    <script type="text/javascript">
      if ('VKIDSDK' in window) {
        const VKID = window.VKIDSDK;

        VKID.Config.init({
          app: <?= VK_APP_ID ?>,
          redirectUrl: '<?= VK_REDIRECT_URI ?>',
          responseMode: VKID.ConfigResponseMode.Callback,
          source: VKID.ConfigSource.LOWCODE,
          scope: 'email phone',
        });

        const oneTap = new VKID.OneTap();

        oneTap.render({
          container: document.getElementById('vk-auth-widget'),
          showAlternativeLogin: true,
          styles: {
            width: 320,
            height: 48,
            borderRadius: 8
          }
        })
        .on(VKID.WidgetEvents.ERROR, vkidOnError)
        .on(VKID.OneTapInternalEvents.LOGIN_SUCCESS, function (payload) {
          const code = payload.code;
          const deviceId = payload.device_id;

          // Обмениваем код на токен через VK ID SDK
          VKID.Auth.exchangeCode(code, deviceId)
            .then(vkidOnSuccess)
            .catch(vkidOnError);
        });

        function vkidOnSuccess(data) {
          console.log('VK ID Success:', data);

          // VK ID SDK возвращает данные пользователя и токен
          if (data.token && data.user) {
            // Формируем параметры для передачи в callback
            const params = new URLSearchParams({
              access_token: data.token,
              user_id: data.user.id,
              first_name: data.user.first_name || '',
              last_name: data.user.last_name || '',
              email: data.user.email || '',
              photo: data.user.avatar || ''
            });

            window.location.href = 'vk_callback.php?' + params.toString();
          }
          // Fallback на старый формат
          else if (data.access_token) {
            window.location.href = 'vk_callback.php?access_token=' + data.access_token + '&user_id=' + data.user_id;
          }
          else {
            console.error('Unexpected data format:', data);
            alert('Ошибка: неожиданный формат данных от VK ID');
          }
        }

        function vkidOnError(error) {
          console.error('VK ID Error:', error);
          alert('Ошибка авторизации через VK ID. Попробуйте еще раз.');
        }
      }
    </script>

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
