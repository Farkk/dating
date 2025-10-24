-- Добавление полей для авторизации через ВКонтакте

-- Добавление vk_id для идентификации пользователя из ВК
ALTER TABLE users ADD COLUMN IF NOT EXISTS vk_id BIGINT UNIQUE;

-- Добавление access_token для хранения токена доступа ВК (опционально)
ALTER TABLE users ADD COLUMN IF NOT EXISTS vk_access_token TEXT;

-- Делаем поля email и password_hash необязательными для пользователей из ВК
ALTER TABLE users ALTER COLUMN email DROP NOT NULL;
ALTER TABLE users ALTER COLUMN password_hash DROP NOT NULL;

-- Создание индекса для быстрого поиска по vk_id
CREATE INDEX IF NOT EXISTS idx_users_vk_id ON users(vk_id);

-- Обновляем таблицу user_photos для корректного хранения путей
-- Пути будут храниться относительно корня проекта
COMMENT ON COLUMN user_photos.photo_path IS 'Относительный путь к фото от корня проекта, например: uploads/photos/user_123/photo1.jpg';

-- Создание таблицы для хранения сессий (опционально, для улучшенной безопасности)
CREATE TABLE IF NOT EXISTS user_sessions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_user_sessions_token ON user_sessions(session_token);
CREATE INDEX IF NOT EXISTS idx_user_sessions_user_id ON user_sessions(user_id);
