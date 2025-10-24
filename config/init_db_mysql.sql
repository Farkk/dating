-- Создание базы данных (выполняется отдельно)
-- CREATE DATABASE dating_site CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE dating_site;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255),
    vk_id BIGINT UNIQUE,
    vk_access_token TEXT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth DATE,
    gender VARCHAR(10),
    city VARCHAR(100),
    bio TEXT,
    profile_photo VARCHAR(255),
    rating DECIMAL(3, 2) DEFAULT 0.0,
    interests JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_vk_id (vk_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица фотографий пользователей
CREATE TABLE IF NOT EXISTS user_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    description TEXT,
    is_primary BOOLEAN DEFAULT FALSE,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица встреч
CREATE TABLE IF NOT EXISTS meetings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    meeting_date DATETIME,
    location VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица сообщений
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message_text TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица лайков
CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    liked_user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (user_id, liked_user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (liked_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица отзывов о встречах
CREATE TABLE IF NOT EXISTS meeting_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewed_user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица интересов пользователей (для более сложного поиска)
CREATE TABLE IF NOT EXISTS user_interests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    interest_name VARCHAR(100) NOT NULL,
    UNIQUE KEY unique_interest (user_id, interest_name),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица администраторов
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица сессий пользователей
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    INDEX idx_session_token (session_token),
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Тестовые данные для таблицы пользователей
INSERT INTO users (username, email, password_hash, first_name, last_name, date_of_birth, gender, city, bio, profile_photo, interests)
VALUES
('veronika', 'veronika@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'Вероника', 'Смирнова', '2000-05-15', 'женский', 'Москва', 'Привет! Я начинающий инженер, увлекаюсь разработкой мобильных приложений. В свободное время люблю читать книги по истории и играть в настольные игры.', 'find/images/v1248_985.png', JSON_ARRAY('Мобильная разработка', 'История', 'Настольные игры')),

('ekaterina', 'ekaterina@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'Екатерина', 'Иванова', '1998-08-22', 'женский', 'Санкт-Петербург', 'Я эколог, мечтаю сделать мир чище! Люблю проводить время на природе, заниматься йогой и читать книги о саморазвитии.', 'find/images/v1248_1060.png', JSON_ARRAY('Экология', 'Йога', 'Саморазвитие')),

('ksenia', 'ksenia@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'Ксения', 'Петрова', '1996-03-10', 'женский', 'Москва', 'Творческая личность с опытом работы в графическом дизайне. Люблю путешествовать, фотографировать и открывать новые места.', 'find/images/v1248_1080.png', JSON_ARRAY('Дизайн', 'Путешествия', 'Фотография')),

('nikolay', 'nikolay@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'Николай', 'Сидоров', '1994-07-05', 'мужской', 'Казань', 'Программист с опытом работы более 5 лет. Увлекаюсь велоспортом и горными походами.', 'rating/images/v1248_1200.png', JSON_ARRAY('Программирование', 'Велоспорт', 'Походы')),

('maria', 'maria@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'Мария', 'Козлова', '1999-12-18', 'женский', 'Москва', 'Студентка медицинского университета. Люблю готовить, играть на гитаре и смотреть документальные фильмы.', 'rating/images/v1248_1212.png', JSON_ARRAY('Медицина', 'Кулинария', 'Музыка')),

('dmitriy', 'dmitriy@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'Дмитрий', 'Новиков', '1992-02-28', 'мужской', 'Санкт-Петербург', 'Фотограф и видеограф. Больше всего люблю снимать природу и городские пейзажи.', 'rating/images/v1248_1049.png', JSON_ARRAY('Фотография', 'Видеосъемка', 'Путешествия')),

('anastasia', 'anastasia@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'Анастасия', 'Морозова', '2000-09-03', 'женский', 'Москва', 'Привет! Я начинающий инженер, увлекаюсь разработкой мобильных приложений. В свободное время люблю читать книги по истории и играть в настольные игры.', 'user/images/v1249_1281.png', JSON_ARRAY('IT', 'Мобильная разработка', 'Книги', 'История', 'Настольные игры', 'Путешествия', 'Кино'));

-- Добавление встреч для пользователей
INSERT INTO meetings (user1_id, user2_id, status, meeting_date, location)
VALUES
(1, 4, 'completed', '2025-02-15 18:00:00', 'Кафе "Центральное", Москва'),
(1, 5, 'completed', '2025-02-20 19:00:00', 'Парк Горького, Москва'),
(2, 4, 'completed', '2025-02-10 18:30:00', 'Ресторан "Панорама", Санкт-Петербург'),
(3, 6, 'completed', '2025-02-25 19:30:00', 'Кафе "Артист", Москва'),
(1, 6, 'pending', '2025-03-05 20:00:00', 'Ботанический сад, Москва'),
(7, 4, 'confirmed', '2025-03-10 18:00:00', 'Технопарк, Москва');

-- Добавление отзывов о встречах
INSERT INTO meeting_reviews (meeting_id, reviewer_id, reviewed_user_id, rating, comments)
VALUES
(1, 1, 4, 5, 'Отличная встреча! Очень интересный собеседник.'),
(1, 4, 1, 4, 'Приятная девушка, хорошо провели время.'),
(2, 1, 5, 5, 'Замечательная прогулка в парке!'),
(2, 5, 1, 5, 'Вероника очень интересная и разносторонняя личность.'),
(3, 2, 4, 4, 'Хороший ресторан и приятная компания.'),
(3, 4, 2, 5, 'Екатерина удивительная девушка с интересными взглядами.'),
(4, 3, 6, 4, 'Хорошая встреча, много общих интересов.'),
(4, 6, 3, 4, 'Ксения очень творческая натура, было приятно пообщаться.');

-- Обновление рейтинга пользователей на основе отзывов
UPDATE users u
SET rating = (
    SELECT AVG(mr.rating)
    FROM meeting_reviews mr
    WHERE mr.reviewed_user_id = u.id
)
WHERE id IN (
    SELECT DISTINCT reviewed_user_id
    FROM meeting_reviews
);

-- Обновление рейтингов для примера
UPDATE users SET rating = 4.5 WHERE username = 'veronika';
UPDATE users SET rating = 4.3 WHERE username = 'ekaterina';
UPDATE users SET rating = 4.2 WHERE username = 'ksenia';
UPDATE users SET rating = 4.0 WHERE username = 'nikolay';
UPDATE users SET rating = 3.9 WHERE username = 'maria';
UPDATE users SET rating = 3.8 WHERE username = 'dmitriy';
UPDATE users SET rating = 4.8 WHERE username = 'anastasia';

-- Добавление тестового администратора
-- Пароль: admin123 (хешированный с помощью password_hash в PHP)
INSERT INTO admins (username, email, password_hash)
VALUES ('admin', 'admin@dating-site.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
