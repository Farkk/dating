<?php
session_start();

require_once '../config/db.php';
require_once '../auth/check_auth.php';

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Обработка загрузки фото
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $file = $_FILES['photo'];

    // Проверка на ошибки загрузки
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Ошибка при загрузке файла';
    } else {
        // Проверка типа файла
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            $error = 'Разрешены только изображения формата JPEG или PNG';
        } else {
            // Проверка размера (макс 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                $error = 'Размер файла не должен превышать 5MB';
            } else {
                // Создаем директорию для пользователя
                $upload_dir = '../uploads/photos/' . $user_id;
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Генерируем уникальное имя файла
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'photo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $filepath = $upload_dir . '/' . $filename;
                $db_path = 'uploads/photos/' . $user_id . '/' . $filename;

                // Перемещаем файл
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    // Определяем, это основное фото или дополнительное
                    if (isset($_POST['is_primary']) && $_POST['is_primary'] == '1') {
                        // Обновляем основное фото в таблице users
                        $stmt = $pdo->prepare("UPDATE users SET profile_photo = :photo WHERE id = :id");
                        $stmt->execute(['photo' => $db_path, 'id' => $user_id]);
                        $message = 'Фото профиля успешно обновлено!';
                    } else {
                        // Добавляем в галерею
                        $stmt = $pdo->prepare("
                            INSERT INTO user_photos (user_id, photo_path, is_primary)
                            VALUES (:user_id, :photo_path, FALSE)
                        ");
                        $stmt->execute(['user_id' => $user_id, 'photo_path' => $db_path]);
                        $message = 'Фото успешно добавлено в галерею!';
                    }
                } else {
                    $error = 'Ошибка при сохранении файла';
                }
            }
        }
    }
}

// Получаем текущие фото пользователя
$stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM user_photos WHERE user_id = :user_id ORDER BY uploaded_at DESC");
$stmt->execute(['user_id' => $user_id]);
$gallery_photos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Загрузка фото - Сайт знакомств</title>
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
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        h1 {
            color: #333;
            font-size: 28px;
        }

        .back-btn {
            padding: 10px 20px;
            background: #f0f0f0;
            color: #333;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #e0e0e0;
        }

        .message {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-size: 14px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .upload-section {
            margin-bottom: 40px;
        }

        h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 20px;
        }

        .upload-form {
            border: 2px dashed #ddd;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            background: #f9f9f9;
            transition: all 0.3s;
        }

        .upload-form:hover {
            border-color: #ff4081;
            background: #fff;
        }

        .upload-icon {
            font-size: 50px;
            color: #999;
            margin-bottom: 20px;
        }

        .upload-form input[type="file"] {
            display: none;
        }

        .file-label {
            display: inline-block;
            padding: 12px 30px;
            background: #ff4081;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .file-label:hover {
            background: #e91e63;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 64, 129, 0.4);
        }

        .photo-type {
            margin: 20px 0;
        }

        .photo-type label {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
            cursor: pointer;
        }

        .photo-type input[type="radio"] {
            width: 18px;
            height: 18px;
        }

        .submit-btn {
            padding: 12px 30px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            margin-top: 15px;
            transition: all 0.3s;
        }

        .submit-btn:hover {
            background: #45a049;
            transform: translateY(-2px);
        }

        .current-photo-section {
            margin-bottom: 40px;
            text-align: center;
        }

        .current-photo {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #ff4081;
            margin: 0 auto;
        }

        .photo-placeholder {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            border: 4px solid #e0e0e0;
        }

        .photo-placeholder i {
            font-size: 70px;
            color: #999;
        }

        .gallery-section {
            margin-top: 40px;
            padding-top: 40px;
            border-top: 2px solid #f0f0f0;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .gallery-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .delete-photo {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 0, 0, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .delete-photo:hover {
            background: rgba(255, 0, 0, 1);
            transform: scale(1.1);
        }

        .file-name {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-camera"></i> Управление фотографиями</h1>
            <a href="edit.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Назад к профилю
            </a>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="current-photo-section">
            <h2>Текущее фото профиля</h2>
            <?php if (!empty($user['profile_photo'])): ?>
                <img src="/<?= htmlspecialchars($user['profile_photo']) ?>" alt="Фото профиля" class="current-photo">
            <?php else: ?>
                <div class="photo-placeholder">
                    <i class="fas fa-user"></i>
                </div>
                <p style="margin-top: 15px; color: #666;">Фото не загружено</p>
            <?php endif; ?>
        </div>

        <div class="upload-section">
            <h2>Загрузить новое фото</h2>
            <form method="POST" enctype="multipart/form-data" class="upload-form" id="uploadForm">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>

                <label for="photo" class="file-label">
                    <i class="fas fa-folder-open"></i> Выбрать фото
                </label>
                <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/jpg" required onchange="showFileName()">

                <div class="file-name" id="fileName"></div>

                <div class="photo-type">
                    <label>
                        <input type="radio" name="is_primary" value="1" checked>
                        <span>Основное фото профиля</span>
                    </label>
                    <br><br>
                    <label>
                        <input type="radio" name="is_primary" value="0">
                        <span>Добавить в галерею</span>
                    </label>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-upload"></i> Загрузить
                </button>

                <p style="margin-top: 20px; font-size: 13px; color: #666;">
                    Максимальный размер: 5MB. Форматы: JPEG, PNG
                </p>
            </form>
        </div>

        <?php if (!empty($gallery_photos)): ?>
        <div class="gallery-section">
            <h2>Галерея фотографий</h2>
            <div class="gallery-grid">
                <?php foreach ($gallery_photos as $photo): ?>
                <div class="gallery-item">
                    <img src="/<?= htmlspecialchars($photo['photo_path']) ?>" alt="Фото галереи">
                    <form method="POST" action="delete_photo.php" style="display: inline;">
                        <input type="hidden" name="photo_id" value="<?= $photo['id'] ?>">
                        <button type="submit" class="delete-photo" onclick="return confirm('Удалить это фото?')">
                            <i class="fas fa-times"></i>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function showFileName() {
            const input = document.getElementById('photo');
            const fileNameDiv = document.getElementById('fileName');
            if (input.files.length > 0) {
                fileNameDiv.textContent = 'Выбран файл: ' + input.files[0].name;
            }
        }
    </script>
</body>
</html>
