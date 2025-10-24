<?php
session_start();

require_once '../config/db.php';
require_once '../auth/check_auth.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['photo_id'])) {
    $photo_id = (int)$_POST['photo_id'];

    // Проверяем, что фото принадлежит текущему пользователю
    $stmt = $pdo->prepare("SELECT * FROM user_photos WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $photo_id, 'user_id' => $user_id]);
    $photo = $stmt->fetch();

    if ($photo) {
        // Удаляем файл с диска
        $filepath = '../' . $photo['photo_path'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        // Удаляем запись из базы
        $stmt = $pdo->prepare("DELETE FROM user_photos WHERE id = :id");
        $stmt->execute(['id' => $photo_id]);
    }
}

header('Location: upload_photo.php');
exit;
