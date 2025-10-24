<?php
// Подключение к базе данных
require_once '../config/db.php';

// Получение списка пользователей для отображения
$sql = "SELECT id, first_name, date_of_birth, gender, city, bio, profile_photo, rating, interests 
        FROM users 
        WHERE is_active = TRUE 
        ORDER BY rating DESC";
$users = executeQuery($sql)->fetchAll();

// Вычисление возраста на основе даты рождения
function calculateAge($birthdate) {
    $today = new DateTime();
    $diff = $today->diff(new DateTime($birthdate));
    return $diff->y;
}

// Если был отправлен поисковый запрос
$search_gender = isset($_GET['gender']) ? $_GET['gender'] : null;
$search_age_min = isset($_GET['age_min']) ? (int)$_GET['age_min'] : 18;
$search_age_max = isset($_GET['age_max']) ? (int)$_GET['age_max'] : 100;
$search_city = isset($_GET['city']) ? $_GET['city'] : null;
$search_interest = isset($_GET['interest']) ? $_GET['interest'] : null;

// Если есть поисковые параметры, обновляем запрос
if ($search_gender || $search_city || $search_interest) {
    $sql = "SELECT id, first_name, date_of_birth, gender, city, bio, profile_photo, rating, interests 
            FROM users 
            WHERE is_active = TRUE";
    
    $params = [];
    
    // Фильтрация по полу
    if ($search_gender) {
        $sql .= " AND gender = :gender";
        $params[':gender'] = $search_gender;
    }
    
    // Фильтрация по городу
    if ($search_city) {
        $sql .= " AND city LIKE :city";
        $params[':city'] = '%' . $search_city . '%';
    }

    // Фильтрация по интересам (MySQL JSON)
    if ($search_interest) {
        $sql .= " AND JSON_CONTAINS(interests, :interest)";
        $params[':interest'] = json_encode($search_interest);
    }
    
    // Фильтрация по возрасту делается в PHP, так как возраст вычисляется
    
    $sql .= " ORDER BY rating DESC";
    $stmt = executeQuery($sql, $params);
    $users = $stmt->fetchAll();
    
    // Фильтрация по возрасту (выполняется в PHP)
    $users = array_filter($users, function($user) use ($search_age_min, $search_age_max) {
        $age = calculateAge($user['date_of_birth']);
        return $age >= $search_age_min && $age <= $search_age_max;
    });
}

// Получение уникальных городов для фильтра
$sql = "SELECT DISTINCT city FROM users WHERE city IS NOT NULL ORDER BY city";
$cities = executeQuery($sql)->fetchAll(PDO::FETCH_COLUMN);

// Получение уникальных интересов для фильтра (MySQL JSON)
$sql = "SELECT interests FROM users WHERE interests IS NOT NULL";
$all_interests_rows = executeQuery($sql)->fetchAll();
$interests = [];
foreach ($all_interests_rows as $row) {
    if (!empty($row['interests'])) {
        $user_interests = json_decode($row['interests'], true);
        if (is_array($user_interests)) {
            $interests = array_merge($interests, $user_interests);
        }
    }
}
$interests = array_unique($interests);
sort($interests);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css?family=Inter&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans+Hebrew&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <title>Поиск пары</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .header {
            background-color: #ff4081;
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
        }
        
        .nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }
        
        .nav-logo {
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav-link:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: white;
            bottom: -5px;
            left: 0;
            transition: width 0.3s ease;
        }
        
        .nav-link:hover:after {
            width: 100%;
        }
        
        .main {
            max-width: 1200px;
            margin: 6rem auto 2rem;
            padding: 0 2rem;
        }
        
        .section-title {
            text-align: center;
            margin: 2rem 0;
            font-size: 2rem;
            color: #ff4081;
        }
        
        .search-form {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .form-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        .card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .card-content {
            padding: 1.5rem;
        }
        
        .card-title {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #ff4081;
        }
        
        .card-text {
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .card-stats {
            display: flex;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #666;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .card-stat {
            background: #f5f5f5;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
        }
        
        .interests-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .interest-tag {
            background: #ffebf3;
            color: #ff4081;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.85rem;
        }
        
        .btn {
            display: inline-block;
            background: #ff4081;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #e6366f;
        }
        
        .rating {
            color: #ff4081;
            font-weight: bold;
            margin-right: 0.5rem;
        }
        
        .text-center {
            text-align: center;
        }
        
        .footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 2rem 0;
            margin-top: 2rem;
        }
        
        .no-results {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="nav-logo animate__animated animate__fadeIn">Dating Site</div>
            <div class="nav-links">
                <a href="../index.html" class="nav-link animate__animated animate__fadeIn">Главная</a>
                <a href="#" class="nav-link animate__animated animate__fadeIn" style="font-weight: bold;">Найти пару</a>
                <a href="../rating/index.html" class="nav-link animate__animated animate__fadeIn">Рейтинг</a>
                <a href="../user/index.html" class="nav-link animate__animated animate__fadeIn">Профиль</a>
            </div>
        </nav>
    </header>

    <main class="main">
        <h1 class="section-title animate__animated animate__bounceIn">Найдите свою идеальную пару</h1>
        
        <form class="search-form animate__animated animate__fadeIn" method="GET" action="">
            <div class="form-group">
                <div>
                    <label class="form-label" for="gender">Пол</label>
                    <select class="form-control" id="gender" name="gender">
                        <option value="">Любой</option>
                        <option value="мужской" <?= $search_gender === 'мужской' ? 'selected' : '' ?>>Мужской</option>
                        <option value="женский" <?= $search_gender === 'женский' ? 'selected' : '' ?>>Женский</option>
                    </select>
                </div>
                
                <div>
                    <label class="form-label" for="age_min">Возраст от</label>
                    <input type="number" class="form-control" id="age_min" name="age_min" min="18" max="100" value="<?= $search_age_min ?>">
                </div>
                
                <div>
                    <label class="form-label" for="age_max">Возраст до</label>
                    <input type="number" class="form-control" id="age_max" name="age_max" min="18" max="100" value="<?= $search_age_max ?>">
                </div>
                
                <div>
                    <label class="form-label" for="city">Город</label>
                    <select class="form-control" id="city" name="city">
                        <option value="">Любой</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= htmlspecialchars($city) ?>" <?= $search_city === $city ? 'selected' : '' ?>>
                                <?= htmlspecialchars($city) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="form-label" for="interest">Интерес</label>
                    <select class="form-control" id="interest" name="interest">
                        <option value="">Любой</option>
                        <?php foreach ($interests as $interest): ?>
                            <option value="<?= htmlspecialchars($interest) ?>" <?= $search_interest === $interest ? 'selected' : '' ?>>
                                <?= htmlspecialchars($interest) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn">Поиск</button>
                </div>
            </div>
        </form>
        
        <div class="card-container">
            <?php if (empty($users)): ?>
                <div class="no-results">
                    <h2>Нет результатов</h2>
                    <p>К сожалению, по вашему запросу ничего не найдено. Попробуйте изменить параметры поиска.</p>
                </div>
            <?php else: ?>
                <?php foreach ($users as $index => $user): ?>
                    <div class="card animate__animated animate__fadeInUp" style="animation-delay: <?= 0.1 * $index ?>s">
                        <img src="<?= htmlspecialchars($user['profile_photo']) ?>" alt="<?= htmlspecialchars($user['first_name']) ?>" class="card-image">
                        <div class="card-content">
                            <h2 class="card-title"><?= htmlspecialchars($user['first_name']) ?></h2>
                            <div class="card-stats">
                                <span class="card-stat">
                                    <span class="rating"><?= number_format($user['rating'], 1) ?> ★</span>
                                </span>
                                <span class="card-stat">
                                    <?= calculateAge($user['date_of_birth']) ?> лет
                                </span>
                                <span class="card-stat">
                                    <?= htmlspecialchars($user['city']) ?>
                                </span>
                            </div>
                            <p class="card-text">
                                <?= nl2br(htmlspecialchars($user['bio'])) ?>
                            </p>
                            <?php if (!empty($user['interests'])):
                                $user_interests_array = is_string($user['interests']) ? json_decode($user['interests'], true) : $user['interests'];
                                if (is_array($user_interests_array) && count($user_interests_array) > 0):
                            ?>
                                <div class="interests-list">
                                    <?php foreach ($user_interests_array as $interest): ?>
                                        <span class="interest-tag"><?= htmlspecialchars($interest) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; endif; ?>
                            <a href="#" class="btn" data-user-id="<?= $user['id'] ?>">Подробнее</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($users)): ?>
            <div class="text-center" style="margin-top: 2rem; text-align: center;">
                <a href="#" class="btn animate__animated animate__pulse animate__infinite">Показать больше</a>
            </div>
        <?php endif; ?>
    </main>
    
    <!-- Модальное окно для просмотра профиля пользователя -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <div class="modal-body">
                <div class="modal-loading">Загрузка...</div>
                <div class="modal-user-info" style="display: none;">
                    <div class="modal-user-header">
                        <img src="" alt="" class="modal-user-photo">
                        <div class="modal-user-details">
                            <h2 class="modal-user-name"></h2>
                            <div class="modal-user-stats">
                                <span class="modal-user-age"></span>
                                <span class="modal-user-city"></span>
                                <span class="modal-user-rating"></span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-section">
                        <h3>О себе</h3>
                        <p class="modal-user-bio"></p>
                    </div>
                    <div class="modal-section modal-interests">
                        <h3>Интересы</h3>
                        <div class="modal-interests-list"></div>
                    </div>
                    <div class="modal-actions">
                        <button class="btn btn-like" data-user-id="">
                            <i class="fas fa-heart"></i> <span>Нравится</span>
                        </button>
                        <button class="btn btn-meeting" data-user-id="">
                            <i class="fas fa-calendar-alt"></i> Пригласить на встречу
                        </button>
                    </div>
                    <div class="modal-meeting-form" style="display: none;">
                        <h3>Приглашение на встречу</h3>
                        <form id="meetingForm">
                            <div class="form-group">
                                <label for="meetingDate">Дата и время</label>
                                <input type="datetime-local" id="meetingDate" name="date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="meetingLocation">Место</label>
                                <input type="text" id="meetingLocation" name="location" class="form-control" placeholder="Например: Кафе 'Центральное', Москва" required>
                            </div>
                            <div class="form-group">
                                <label for="meetingNotes">Примечания</label>
                                <textarea id="meetingNotes" name="notes" class="form-control" placeholder="Дополнительная информация"></textarea>
                            </div>
                            <div class="form-buttons">
                                <button type="submit" class="btn">Отправить</button>
                                <button type="button" class="btn btn-cancel">Отмена</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <p>&copy; 2025 Dating Site. Все права защищены.</p>
    </footer>

    <!-- Font Awesome для иконок -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Стили для модального окна */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow: auto;
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            max-width: 700px;
            width: 90%;
            position: relative;
            animation: modalFadeIn 0.3s;
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #999;
            cursor: pointer;
            z-index: 10;
        }
        
        .modal-close:hover {
            color: #ff4081;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-loading {
            text-align: center;
            padding: 30px;
            font-size: 18px;
            color: #666;
        }
        
        .modal-user-header {
            display: flex;
            margin-bottom: 20px;
        }
        
        .modal-user-photo {
            width: 120px;
            height: 120px;
            border-radius: 10px;
            object-fit: cover;
            margin-right: 20px;
        }
        
        .modal-user-details {
            flex: 1;
        }
        
        .modal-user-name {
            font-size: 24px;
            margin-bottom: 10px;
            color: #ff4081;
        }
        
        .modal-user-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .modal-user-stats span {
            background: #f5f5f5;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            color: #666;
        }
        
        .modal-section {
            margin-bottom: 20px;
        }
        
        .modal-section h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .modal-interests-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .modal-interest-tag {
            background: #ffebf3;
            color: #ff4081;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-like {
            background-color: #f5f5f5;
            color: #ff4081;
        }
        
        .btn-like.active {
            background-color: #ff4081;
            color: white;
        }
        
        .btn-meeting {
            background-color: #4caf50;
            color: white;
        }
        
        .btn-cancel {
            background-color: #f5f5f5;
            color: #666;
        }
        
        .modal-meeting-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 1001;
            animation: fadeIn 0.3s, fadeOut 0.3s 2.7s;
            opacity: 0;
            animation-fill-mode: forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(20px); }
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Элементы модального окна
            const modal = document.getElementById('userModal');
            const modalClose = document.querySelector('.modal-close');
            const modalLoading = document.querySelector('.modal-loading');
            const modalUserInfo = document.querySelector('.modal-user-info');
            const btnLike = document.querySelector('.btn-like');
            const btnMeeting = document.querySelector('.btn-meeting');
            const meetingForm = document.querySelector('.modal-meeting-form');
            const btnCancel = document.querySelector('.btn-cancel');
            
            // Кнопки "Подробнее" на карточках пользователей
            const detailButtons = document.querySelectorAll('.card .btn');
            
            // Открытие модального окна при клике на кнопку "Подробнее"
            detailButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const userId = this.getAttribute('data-user-id');
                    openUserModal(userId);
                });
            });
            
            // Закрытие модального окна
            modalClose.addEventListener('click', closeModal);
            
            // Закрытие модального окна при клике вне его содержимого
            window.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });
            
            // Обработка кнопки "Нравится"
            btnLike.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                likeUser(userId);
            });
            
            // Открытие формы для приглашения на встречу
            btnMeeting.addEventListener('click', function() {
                meetingForm.style.display = 'block';
                this.style.display = 'none';
            });
            
            // Отмена создания встречи
            btnCancel.addEventListener('click', function() {
                meetingForm.style.display = 'none';
                btnMeeting.style.display = 'inline-block';
            });
            
            // Отправка формы встречи
            document.getElementById('meetingForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const userId = btnMeeting.getAttribute('data-user-id');
                requestMeeting(userId, this);
            });
            
            // Функция открытия модального окна с информацией о пользователе
            function openUserModal(userId) {
                modal.style.display = 'block';
                modalLoading.style.display = 'block';
                modalUserInfo.style.display = 'none';
                
                // Отправка запроса к API
                fetchUserInfo(userId);
            }
            
            // Закрытие модального окна
            function closeModal() {
                modal.style.display = 'none';
                meetingForm.style.display = 'none';
                btnMeeting.style.display = 'inline-block';
                document.getElementById('meetingForm').reset();
            }
            
            // Получение информации о пользователе через API
            function fetchUserInfo(userId) {
                const formData = new FormData();
                formData.append('action', 'get_user_info');
                formData.append('user_id', userId);
                
                fetch('../api/actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayUserInfo(data.data);
                    } else {
                        showNotification(data.message || 'Ошибка при получении данных');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Произошла ошибка при загрузке данных');
                })
                .finally(() => {
                    modalLoading.style.display = 'none';
                    modalUserInfo.style.display = 'block';
                });
            }
            
            // Отображение информации о пользователе в модальном окне
            function displayUserInfo(data) {
                const user = data.user;
                
                // Заполняем информацию о пользователе
                document.querySelector('.modal-user-photo').src = user.profile_photo;
                document.querySelector('.modal-user-photo').alt = user.first_name;
                document.querySelector('.modal-user-name').textContent = `${user.first_name} ${user.last_name}`;
                
                // Вычисляем возраст
                const birthDate = new Date(user.date_of_birth);
                const ageDiff = Date.now() - birthDate.getTime();
                const ageDate = new Date(ageDiff);
                const age = Math.abs(ageDate.getUTCFullYear() - 1970);
                
                document.querySelector('.modal-user-age').textContent = `${age} лет`;
                document.querySelector('.modal-user-city').textContent = user.city;
                document.querySelector('.modal-user-rating').textContent = `${user.rating} ★`;
                document.querySelector('.modal-user-bio').textContent = user.bio;
                
                // Заполняем список интересов
                const interestsList = document.querySelector('.modal-interests-list');
                interestsList.innerHTML = '';
                
                if (user.interests) {
                    let interestsArray = user.interests;

                    // Если interests - это JSON строка, парсим её
                    if (typeof user.interests === 'string') {
                        try {
                            interestsArray = JSON.parse(user.interests);
                        } catch (e) {
                            interestsArray = [user.interests];
                        }
                    }

                    if (Array.isArray(interestsArray) && interestsArray.length > 0) {
                        interestsArray.forEach(interest => {
                            const interestTag = document.createElement('span');
                            interestTag.className = 'modal-interest-tag';
                            interestTag.textContent = interest;
                            interestsList.appendChild(interestTag);
                        });
                    } else {
                        interestsList.innerHTML = '<p>Интересы не указаны</p>';
                    }
                } else {
                    interestsList.innerHTML = '<p>Интересы не указаны</p>';
                }
                
                // Устанавливаем ID пользователя для кнопок действий
                btnLike.setAttribute('data-user-id', user.id);
                btnMeeting.setAttribute('data-user-id', user.id);
                
                // Отмечаем кнопку лайка, если уже лайкнули пользователя
                if (data.liked) {
                    btnLike.classList.add('active');
                    btnLike.querySelector('span').textContent = 'Нравится';
                } else {
                    btnLike.classList.remove('active');
                    btnLike.querySelector('span').textContent = 'Нравится';
                }
            }
            
            // Лайк/дизлайк пользователя
            function likeUser(userId) {
                const formData = new FormData();
                formData.append('action', 'like_user');
                formData.append('user_id', userId);
                
                fetch('../api/actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.data.liked) {
                            btnLike.classList.add('active');
                            btnLike.querySelector('span').textContent = 'Нравится';
                        } else {
                            btnLike.classList.remove('active');
                            btnLike.querySelector('span').textContent = 'Нравится';
                        }
                        
                        showNotification(data.message);
                        
                        // Если это взаимный лайк (мэтч)
                        if (data.data.match) {
                            // Можно добавить дополнительные действия при мэтче
                        }
                    } else {
                        showNotification(data.message || 'Ошибка при выполнении действия');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Произошла ошибка при обработке запроса');
                });
            }
            
            // Отправка приглашения на встречу
            function requestMeeting(userId, form) {
                const formData = new FormData(form);
                formData.append('action', 'request_meeting');
                formData.append('user_id', userId);
                
                fetch('../api/actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message);
                        meetingForm.style.display = 'none';
                        btnMeeting.style.display = 'inline-block';
                        form.reset();
                    } else {
                        showNotification(data.message || 'Ошибка при отправке приглашения');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Произошла ошибка при обработке запроса');
                });
            }
            
            // Отображение уведомления
            function showNotification(message) {
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.textContent = message;
                document.body.appendChild(notification);
                
                // Удаление уведомления через 3 секунды
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }
        });
    </script>
</body>
</html> 