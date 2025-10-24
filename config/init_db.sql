-- Создание базы данных (выполняется отдельно)
-- CREATE DATABASE "dating-site";

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
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
    interests TEXT[],
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица фотографий пользователей
CREATE TABLE IF NOT EXISTS user_photos (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    photo_path VARCHAR(255) NOT NULL,
    description TEXT,
    is_primary BOOLEAN DEFAULT FALSE,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица встреч
CREATE TABLE IF NOT EXISTS meetings (
    id SERIAL PRIMARY KEY,
    user1_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    user2_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    status VARCHAR(20) DEFAULT 'pending', -- pending, confirmed, completed, cancelled
    meeting_date TIMESTAMP,
    location VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица сообщений
CREATE TABLE IF NOT EXISTS messages (
    id SERIAL PRIMARY KEY,
    sender_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    receiver_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    message_text TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица лайков
CREATE TABLE IF NOT EXISTS likes (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    liked_user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, liked_user_id)
);

-- Таблица отзывов о встречах
CREATE TABLE IF NOT EXISTS meeting_reviews (
    id SERIAL PRIMARY KEY,
    meeting_id INTEGER REFERENCES meetings(id) ON DELETE CASCADE,
    reviewer_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    reviewed_user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица интересов пользователей (для более сложного поиска)
CREATE TABLE IF NOT EXISTS user_interests (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    interest_name VARCHAR(100) NOT NULL,
    UNIQUE(user_id, interest_name)
);

-- Таблица администраторов
CREATE TABLE IF NOT EXISTS admins (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица сессий пользователей
CREATE TABLE IF NOT EXISTS user_sessions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL
);

-- Индексы для оптимизации
CREATE INDEX IF NOT EXISTS idx_users_vk_id ON users(vk_id);
CREATE INDEX IF NOT EXISTS idx_user_sessions_token ON user_sessions(session_token);
CREATE INDEX IF NOT EXISTS idx_user_sessions_user_id ON user_sessions(user_id);

-- Тестовые данные для таблицы пользователей
INSERT INTO users (username, email, password_hash, first_name, last_name, date_of_birth, gender, city, bio, profile_photo, interests)
VALUES 
('veronika', 'veronika@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'Вероника', 'Смирнова', '2000-05-15', 'женский', 'Москва', 'Привет! Я начинающий инженер, увлекаюсь разработкой мобильных приложений. В свободное время люблю читать книги по истории и играть в настольные игры.', 'find/images/v1248_985.png', ARRAY['Мобильная разработка', 'История', 'Настольные игры']),

('ekaterina', 'ekaterina@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'Екатерина', 'Иванова', '1998-08-22', 'женский', 'Санкт-Петербург', 'Я эколог, мечтаю сделать мир чище! Люблю проводить время на природе, заниматься йогой и читать книги о саморазвитии.', 'find/images/v1248_1060.png', ARRAY['Экология', 'Йога', 'Саморазвитие']),

('ksenia', 'ksenia@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'Ксения', 'Петрова', '1996-03-10', 'женский', 'Москва', 'Творческая личность с опытом работы в графическом дизайне. Люблю путешествовать, фотографировать и открывать новые места.', 'find/images/v1248_1080.png', ARRAY['Дизайн', 'Путешествия', 'Фотография']),

('nikolay', 'nikolay@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'Николай', 'Сидоров', '1994-07-05', 'мужской', 'Казань', 'Программист с опытом работы более 5 лет. Увлекаюсь велоспортом и горными походами.', 'rating/images/v1248_1200.png', ARRAY['Программирование', 'Велоспорт', 'Походы']),

('maria', 'maria@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'Мария', 'Козлова', '1999-12-18', 'женский', 'Москва', 'Студентка медицинского университета. Люблю готовить, играть на гитаре и смотреть документальные фильмы.', 'rating/images/v1248_1212.png', ARRAY['Медицина', 'Кулинария', 'Музыка']),

('dmitriy', 'dmitriy@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'Дмитрий', 'Новиков', '1992-02-28', 'мужской', 'Санкт-Петербург', 'Фотограф и видеограф. Больше всего люблю снимать природу и городские пейзажи.', 'rating/images/v1248_1049.png', ARRAY['Фотография', 'Видеосъемка', 'Путешествия']),

('anastasia', 'anastasia@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'Анастасия', 'Морозова', '2000-09-03', 'женский', 'Москва', 'Привет! Я начинающий инженер, увлекаюсь разработкой мобильных приложений. В свободное время люблю читать книги по истории и играть в настольные игры.', 'user/images/v1249_1281.png', ARRAY['IT', 'Мобильная разработка', 'Книги', 'История', 'Настольные игры', 'Путешествия', 'Кино']);

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
    SELECT AVG(mr.rating)::DECIMAL(3,2)
    FROM meeting_reviews mr
    WHERE mr.reviewed_user_id = u.id
)
WHERE id IN (
    SELECT DISTINCT reviewed_user_id
    FROM meeting_reviews
);

-- Обновление количества встреч для первых мест в рейтинге
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