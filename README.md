# Dating Site - Сайт знакомств

Современный сайт знакомств с авторизацией через VK ID и системой оценок.

## 🚀 Особенности

- ✅ Авторизация через VK ID SDK (официальный виджет)
- ✅ Профили пользователей с фотографиями
- ✅ Поиск анкет с фильтрами (пол, возраст, город, интересы)
- ✅ Система рейтинга на основе отзывов
- ✅ Встречи и сообщения между пользователями
- ✅ Загрузка фотографий
- ✅ Адаптивный дизайн

## 📋 Требования

- PHP 7.4+
- MySQL 5.7+
- Расширения PHP:
  - pdo_mysql
  - json
  - gd (для работы с изображениями)
  - session
  - fileinfo

## ⚙️ Установка

### 1. Клонирование репозитория

```bash
git clone https://github.com/Farkk/dating.git
cd dating
```

### 2. Настройка базы данных

Создайте базу данных и импортируйте схему:

```bash
# Через phpMyAdmin:
# 1. Создайте базу данных
# 2. Импортируйте файл config/init_db_mysql.sql

# Или через командную строку:
mysql -u your_user -p your_database < config/init_db_mysql.sql
```

### 3. Настройка конфигурации

Отредактируйте `config/db.php`:

```php
$host = 'localhost';
$dbname = 'your_database_name';
$user = 'your_username';
$password = 'your_password';
```

### 4. Настройка VK ID приложения

#### Создание приложения VK:

1. Перейдите на https://id.vk.com/about/business/go
2. Создайте новое приложение
3. В настройках укажите:
   - **Redirect URI**: `https://your-domain.com/auth/vk_callback.php`
   - **Тип**: Веб-сайт

#### Обновите `config/vk_config.php`:

```php
define('VK_APP_ID', 'YOUR_APP_ID');
define('VK_APP_SECRET', 'YOUR_APP_SECRET');
define('VK_REDIRECT_URI', 'https://your-domain.com/auth/vk_callback.php');
```

### 5. Создайте папку для загрузок

```bash
mkdir -p uploads/photos
chmod 755 uploads/photos
```

### 6. Настройте .htaccess

Убедитесь, что файл `.htaccess` существует в корне проекта:

```apache
DirectoryIndex index.php index.html index.htm
AddDefaultCharset UTF-8
AddCharset UTF-8 .html .php .css .js
```

## 🔧 Конфигурация VK ID SDK

Виджет VK ID настроен в файле `auth/login.php` со следующими параметрами:

```javascript
VKID.Config.init({
  app: YOUR_APP_ID,
  redirectUrl: 'https://your-domain.com/auth/vk_callback.php',
  responseMode: VKID.ConfigResponseMode.Callback,
  source: VKID.ConfigSource.LOWCODE,
  scope: 'email phone',
});
```

### Доступные scope:

- `email` - доступ к email пользователя
- `phone` - доступ к номеру телефона
- `vkid.personal_info` - персональная информация

## 📁 Структура проекта

```
/
├── auth/                    # Авторизация
│   ├── login.php           # Страница входа с VK ID SDK виджетом
│   ├── vk_callback.php     # Обработчик OAuth callback
│   ├── logout.php          # Выход из системы
│   └── check_auth.php      # Проверка авторизации
├── config/                  # Конфигурация
│   ├── db.php              # Настройки базы данных
│   ├── vk_config.php       # Настройки VK приложения
│   └── init_db_mysql.sql   # SQL схема и тестовые данные
├── profile/                 # Профиль пользователя
│   ├── edit.php            # Редактирование профиля
│   ├── upload_photo.php    # Загрузка фотографий
│   └── delete_photo.php    # Удаление фотографий
├── find/                    # Поиск анкет
│   └── index.php           # Страница поиска с фильтрами
├── rating/                  # Рейтинг пользователей
│   └── index.php           # Топ пользователей
├── user/                    # Просмотр профиля
│   └── index.php           # Публичный профиль пользователя
├── api/                     # API endpoints
│   └── actions.php         # Лайки, встречи, сообщения
├── uploads/                 # Загруженные файлы
│   └── photos/             # Фотографии пользователей
├── index.php               # Главная страница
└── .htaccess               # Apache конфигурация
```

## 🗄️ База данных

### Основные таблицы:

- **users** - пользователи (профили, рейтинг, интересы в JSON)
- **user_photos** - дополнительные фотографии
- **meetings** - встречи между пользователями
- **meeting_reviews** - отзывы о встречах (1-5 звезд)
- **likes** - взаимные лайки
- **messages** - личные сообщения
- **user_sessions** - активные сессии пользователей

## 🎨 Технологии

- **Backend**: PHP (vanilla)
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript (vanilla)
- **Authentication**: VK ID SDK 3.0
- **Icons**: Font Awesome 6.0
- **Animations**: Animate.css

## 🔒 Безопасность

- ✅ Prepared statements (защита от SQL-инъекций)
- ✅ Проверка типов файлов при загрузке
- ✅ Ограничение размера файлов (5MB)
- ✅ Валидация сессий в базе данных
- ✅ Защита личных файлов (проверка владельца)
- ✅ HTTPS для production

## 🚦 Запуск

### Локальная разработка:

```bash
php -S localhost:8000
```

Откройте http://localhost:8000 в браузере.

### Production:

Загрузите файлы на хостинг через FTP/SFTP или используйте:

```bash
git pull origin main
```

## 📝 Использование

1. Откройте сайт в браузере
2. Нажмите "Войти через ВКонтакте"
3. Разрешите доступ к вашим данным VK
4. Заполните профиль (имя, дата рождения, город, интересы)
5. Загрузите фотографии
6. Начните поиск!

## 🐛 Решение проблем

### Ошибка "Selected sign-in method not available for app"

Проверьте:
- VK приложение активно (опубликовано)
- Redirect URI совпадает с указанным в настройках
- Используете правильный App ID

### Ошибка 403 на главной странице

Проверьте:
- `.htaccess` загружен на сервер
- `DirectoryIndex index.php` в начале списка
- Права на файлы: 644 для PHP файлов, 755 для папок

### Фотографии не загружаются

Проверьте:
- Папка `uploads/photos` существует
- Права на папку: 755 или 777
- PHP имеет права на запись

## 📄 Лицензия

MIT License

## 🤝 Контрибьюция

Pull requests приветствуются! Для крупных изменений создайте issue для обсуждения.

## 🔗 Ссылки

- **Production**: https://dating.asmart-test-dev.ru/
- **VK ID Documentation**: https://id.vk.com/about/business/go/docs/en/vkid/latest/vk-id/connection/api-description
- **VK Dev Portal**: https://dev.vk.com/

---

**Создано с ❤️ и [Claude Code](https://claude.com/claude-code)**
