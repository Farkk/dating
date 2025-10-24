<?php
session_start();

require_once '../config/db.php';
require_once '../config/vk_config.php';

// Проверяем, получили ли мы данные напрямую от VK ID SDK
if (isset($_GET['access_token']) && isset($_GET['user_id'])) {
    $access_token = $_GET['access_token'];
    $vk_user_id = $_GET['user_id'];
    $email = $_GET['email'] ?? null;

    // Если получили готовые данные пользователя от VK ID SDK, используем их
    $vk_user_from_sdk = null;
    if (isset($_GET['first_name']) && isset($_GET['last_name'])) {
        $vk_user_from_sdk = [
            'id' => $vk_user_id,
            'first_name' => $_GET['first_name'],
            'last_name' => $_GET['last_name'],
            'photo_max_orig' => $_GET['photo'] ?? null
        ];
    }
}
// Или получили код для обмена на токен (стандартный OAuth)
elseif (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Обмениваем код на access token
    $token_url = 'https://oauth.vk.com/access_token?' . http_build_query([
        'client_id' => VK_APP_ID,
        'client_secret' => VK_APP_SECRET,
        'redirect_uri' => VK_REDIRECT_URI,
        'code' => $code
    ]);

    $token_response = file_get_contents($token_url);
    $token_data = json_decode($token_response, true);

    if (!isset($token_data['access_token'])) {
        die('Ошибка получения токена доступа: ' . ($token_data['error_description'] ?? 'Неизвестная ошибка'));
    }

    $access_token = $token_data['access_token'];
    $vk_user_id = $token_data['user_id'];
    $email = $token_data['email'] ?? null;
} else {
    die('Ошибка авторизации: не получены данные для входа');
}

// Получаем информацию о пользователе
// Если данные уже пришли от VK ID SDK, используем их
if (isset($vk_user_from_sdk) && $vk_user_from_sdk) {
    $vk_user = $vk_user_from_sdk;
    error_log("Using user data from VK ID SDK: " . print_r($vk_user, true));
}
// Иначе запрашиваем через VK API
else {
    $api_url = 'https://api.vk.com/method/users.get?' . http_build_query([
        'user_ids' => $vk_user_id,
        'fields' => 'photo_max_orig,bdate,city,sex,about',
        'access_token' => $access_token,
        'v' => VK_API_VERSION
    ]);

    $api_response = file_get_contents($api_url);
    $api_data = json_decode($api_response, true);

    // Отладочная информация
    error_log("VK API Response: " . print_r($api_data, true));

    if (!isset($api_data['response'][0])) {
        // Выводим детальную ошибку для отладки
        $error_msg = 'Ошибка получения данных пользователя из ВК.<br>';
        if (isset($api_data['error'])) {
            $error_msg .= 'Код ошибки: ' . $api_data['error']['error_code'] . '<br>';
            $error_msg .= 'Описание: ' . $api_data['error']['error_msg'] . '<br>';
        }
        $error_msg .= '<br>Отладка:<br>';
        $error_msg .= 'User ID: ' . htmlspecialchars($vk_user_id) . '<br>';
        $error_msg .= 'Token (первые 20 символов): ' . htmlspecialchars(substr($access_token, 0, 20)) . '...<br>';
        $error_msg .= '<br><a href="/auth/login.php">Вернуться к авторизации</a>';
        die($error_msg);
    }

    $vk_user = $api_data['response'][0];
}

// Проверяем, существует ли пользователь в базе
$stmt = $pdo->prepare("SELECT * FROM users WHERE vk_id = :vk_id LIMIT 1");
$stmt->execute(['vk_id' => $vk_user_id]);
$user = $stmt->fetch();

if ($user) {
    // Пользователь уже существует - обновляем токен
    $stmt = $pdo->prepare("UPDATE users SET vk_access_token = :token, updated_at = NOW() WHERE vk_id = :vk_id");
    $stmt->execute([
        'token' => $access_token,
        'vk_id' => $vk_user_id
    ]);

    $user_id = $user['id'];
} else {
    // Создаем нового пользователя
    $first_name = $vk_user['first_name'] ?? '';
    $last_name = $vk_user['last_name'] ?? '';

    // Обрабатываем дату рождения
    $date_of_birth = null;
    if (isset($vk_user['bdate'])) {
        $bdate_parts = explode('.', $vk_user['bdate']);
        if (count($bdate_parts) === 3) {
            $date_of_birth = $bdate_parts[2] . '-' . $bdate_parts[1] . '-' . $bdate_parts[0];
        }
    }

    // Определяем пол (1 - женский, 2 - мужской в ВК)
    $gender = null;
    if (isset($vk_user['sex'])) {
        $gender = ($vk_user['sex'] == 1) ? 'женский' : (($vk_user['sex'] == 2) ? 'мужской' : null);
    }

    // Город
    $city = $vk_user['city']['title'] ?? null;

    // Описание
    $bio = $vk_user['about'] ?? null;

    // Генерируем username из имени и vk_id
    $username = strtolower(transliterate($first_name)) . '_' . $vk_user_id;

    // Скачиваем фотографию профиля
    $profile_photo = null;
    if (isset($vk_user['photo_max_orig'])) {
        $profile_photo = downloadVkPhoto($vk_user['photo_max_orig'], $vk_user_id);
    }

    // Вставляем пользователя в базу
    $stmt = $pdo->prepare("
        INSERT INTO users (
            username, email, vk_id, vk_access_token,
            first_name, last_name, date_of_birth,
            gender, city, bio, profile_photo
        ) VALUES (
            :username, :email, :vk_id, :vk_access_token,
            :first_name, :last_name, :date_of_birth,
            :gender, :city, :bio, :profile_photo
        )
    ");

    $stmt->execute([
        'username' => $username,
        'email' => $email,
        'vk_id' => $vk_user_id,
        'vk_access_token' => $access_token,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'date_of_birth' => $date_of_birth,
        'gender' => $gender,
        'city' => $city,
        'bio' => $bio,
        'profile_photo' => $profile_photo
    ]);

    $user_id = $pdo->lastInsertId();
}

// Создаем сессию для пользователя
$session_token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

$stmt = $pdo->prepare("
    INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at)
    VALUES (:user_id, :session_token, :ip_address, :user_agent, :expires_at)
");

$stmt->execute([
    'user_id' => $user_id,
    'session_token' => $session_token,
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    'expires_at' => $expires_at
]);

// Сохраняем информацию в сессии PHP
$_SESSION['user_id'] = $user_id;
$_SESSION['session_token'] = $session_token;
$_SESSION['authenticated'] = true;

// Перенаправляем на страницу профиля
header('Location: /profile/edit.php');
exit;

/**
 * Функция транслитерации русских символов в латиницу
 */
function transliterate($text) {
    $converter = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ь' => '', 'ы' => 'y', 'ъ' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',

        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
        'Е' => 'E', 'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
        'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
        'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
        'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'Ch',
        'Ш' => 'Sh', 'Щ' => 'Sch', 'Ь' => '', 'Ы' => 'Y', 'Ъ' => '',
        'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
    ];

    return strtr($text, $converter);
}

/**
 * Функция скачивания фото из ВК и сохранения локально
 */
function downloadVkPhoto($photo_url, $vk_user_id) {
    // Создаем директорию для фото пользователя
    $upload_dir = '../uploads/photos/' . $vk_user_id;
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Получаем расширение файла
    $ext = pathinfo(parse_url($photo_url, PHP_URL_PATH), PATHINFO_EXTENSION);
    if (empty($ext)) {
        $ext = 'jpg';
    }

    $filename = 'profile_' . time() . '.' . $ext;
    $filepath = $upload_dir . '/' . $filename;

    // Скачиваем фото
    $photo_content = @file_get_contents($photo_url);
    if ($photo_content !== false) {
        file_put_contents($filepath, $photo_content);
        return 'uploads/photos/' . $vk_user_id . '/' . $filename;
    }

    return null;
}
