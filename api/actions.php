<?php
// Включаем вывод ошибок для отладки (потом можно отключить)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Запускаем сессию
session_start();

// Подключаем файл с настройками базы данных
require_once '../config/db.php';

// Устанавливаем заголовки для работы с API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Получаем данные запроса
$action = isset($_POST['action']) ? $_POST['action'] : '';
$response = ['success' => false, 'message' => 'Действие не указано', 'data' => null];

// Получаем ID текущего пользователя из сессии
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Если пользователь не авторизован, возвращаем ошибку
if (!$current_user_id) {
    $response = ['success' => false, 'message' => 'Необходима авторизация', 'data' => null];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Обработка разных типов действий
switch ($action) {
    case 'like_user':
        $liked_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        
        if ($liked_user_id > 0) {
            // Проверяем, не ставил ли уже пользователь лайк
            $check_sql = "SELECT id FROM likes WHERE user_id = :user_id AND liked_user_id = :liked_user_id";
            $check_params = [
                ':user_id' => $current_user_id,
                ':liked_user_id' => $liked_user_id
            ];
            
            $stmt = executeQuery($check_sql, $check_params);
            $existing_like = $stmt->fetch();
            
            if ($existing_like) {
                // Если лайк уже существует, удаляем его (дизлайк)
                $delete_sql = "DELETE FROM likes WHERE user_id = :user_id AND liked_user_id = :liked_user_id";
                executeQuery($delete_sql, $check_params);
                
                $response = [
                    'success' => true,
                    'message' => 'Лайк удален',
                    'data' => ['liked' => false]
                ];
            } else {
                // Добавляем новый лайк
                $insert_sql = "INSERT INTO likes (user_id, liked_user_id) VALUES (:user_id, :liked_user_id)";
                executeQuery($insert_sql, $check_params);
                
                $response = [
                    'success' => true,
                    'message' => 'Лайк добавлен',
                    'data' => ['liked' => true]
                ];
                
                // Проверяем, есть ли взаимный лайк (мэтч)
                $match_sql = "SELECT id FROM likes WHERE user_id = :liked_user_id AND liked_user_id = :user_id";
                $match_params = [
                    ':liked_user_id' => $liked_user_id,
                    ':user_id' => $current_user_id
                ];
                
                $stmt = executeQuery($match_sql, $match_params);
                $mutual_like = $stmt->fetch();
                
                if ($mutual_like) {
                    $response['message'] = 'У вас взаимная симпатия!';
                    $response['data']['match'] = true;
                }
            }
        } else {
            $response['message'] = 'Некорректный ID пользователя';
        }
        break;
        
    case 'request_meeting':
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $date = isset($_POST['date']) ? $_POST['date'] : null;
        $location = isset($_POST['location']) ? $_POST['location'] : null;
        $notes = isset($_POST['notes']) ? $_POST['notes'] : null;
        
        if ($user_id > 0 && $date && $location) {
            // Создаем новую встречу
            $sql = "INSERT INTO meetings (user1_id, user2_id, status, meeting_date, location, notes) 
                    VALUES (:user1_id, :user2_id, 'pending', :date, :location, :notes)";
            
            $params = [
                ':user1_id' => $current_user_id,
                ':user2_id' => $user_id,
                ':date' => $date,
                ':location' => $location,
                ':notes' => $notes
            ];
            
            executeQuery($sql, $params);
            
            $response = [
                'success' => true,
                'message' => 'Приглашение на встречу отправлено',
                'data' => null
            ];
        } else {
            $response['message'] = 'Не все обязательные поля заполнены';
        }
        break;
        
    case 'get_user_info':
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        
        if ($user_id > 0) {
            // Получаем информацию о пользователе
            $sql = "SELECT id, first_name, last_name, date_of_birth, gender, city, bio, 
                           profile_photo, rating, interests 
                    FROM users 
                    WHERE id = :user_id AND is_active = TRUE";
            
            $params = [':user_id' => $user_id];
            $stmt = executeQuery($sql, $params);
            $user = $stmt->fetch();
            
            if ($user) {
                // Проверяем, поставил ли текущий пользователь лайк
                $like_sql = "SELECT id FROM likes WHERE user_id = :user_id AND liked_user_id = :liked_user_id";
                $like_params = [
                    ':user_id' => $current_user_id,
                    ':liked_user_id' => $user_id
                ];
                
                $stmt = executeQuery($like_sql, $like_params);
                $has_like = $stmt->fetch();
                
                // Проверяем историю встреч между пользователями
                $meetings_sql = "SELECT id, status, meeting_date, location 
                                 FROM meetings 
                                 WHERE (user1_id = :user_id1 AND user2_id = :user_id2) 
                                    OR (user1_id = :user_id2 AND user2_id = :user_id1)
                                 ORDER BY meeting_date DESC";
                
                $meetings_params = [
                    ':user_id1' => $current_user_id,
                    ':user_id2' => $user_id
                ];
                
                $stmt = executeQuery($meetings_sql, $meetings_params);
                $meetings = $stmt->fetchAll();
                
                $response = [
                    'success' => true,
                    'message' => 'Информация получена',
                    'data' => [
                        'user' => $user,
                        'liked' => !empty($has_like),
                        'meetings' => $meetings
                    ]
                ];
            } else {
                $response['message'] = 'Пользователь не найден';
            }
        } else {
            $response['message'] = 'Некорректный ID пользователя';
        }
        break;
        
    case 'get_my_likes':
        // Получаем пользователей, которым текущий пользователь поставил лайк
        $sql = "SELECT u.id, u.first_name, u.profile_photo, l.created_at
                FROM likes l
                JOIN users u ON l.liked_user_id = u.id
                WHERE l.user_id = :user_id
                ORDER BY l.created_at DESC";
        
        $params = [':user_id' => $current_user_id];
        $stmt = executeQuery($sql, $params);
        $likes = $stmt->fetchAll();
        
        $response = [
            'success' => true,
            'message' => 'Список лайков получен',
            'data' => ['likes' => $likes]
        ];
        break;
        
    case 'get_my_meetings':
        // Получаем список встреч текущего пользователя
        $sql = "SELECT m.id, m.status, m.meeting_date, m.location, m.notes,
                       u.id as other_user_id, u.first_name, u.profile_photo
                FROM meetings m
                JOIN users u ON (m.user1_id = u.id AND m.user2_id = :user_id) 
                             OR (m.user2_id = u.id AND m.user1_id = :user_id)
                WHERE m.user1_id = :user_id OR m.user2_id = :user_id
                ORDER BY m.meeting_date DESC";
        
        $params = [':user_id' => $current_user_id];
        $stmt = executeQuery($sql, $params);
        $meetings = $stmt->fetchAll();
        
        $response = [
            'success' => true,
            'message' => 'Список встреч получен',
            'data' => ['meetings' => $meetings]
        ];
        break;
        
    default:
        $response['message'] = 'Неизвестное действие';
        break;
}

// Возвращаем ответ в формате JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?> 