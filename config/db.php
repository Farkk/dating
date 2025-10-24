<?php
// Конфигурация подключения к MySQL
$host = 'localhost';           // Хост базы данных
$port = '3306';                // Порт MySQL (стандартный 3306)
$dbname = 'j27119254_dite';    // Имя базы данных
$user = 'j27119254_dite';      // Имя пользователя
$password = 'j27119254_dite';  // Пароль

// Строка подключения к MySQL
$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

try {
  // Создаем экземпляр PDO для работы с базой данных
  $pdo = new PDO($dsn, $user, $password);

  // Устанавливаем режим обработки ошибок
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Устанавливаем режим выборки по умолчанию
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

  // Кодировка UTF-8
  $pdo->exec("SET NAMES 'utf8mb4'");
} catch (PDOException $e) {
  // В случае ошибки подключения выводим сообщение
  die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Функция для безопасного выполнения SQL-запросов
function executeQuery($sql, $params = [])
{
  global $pdo;

  try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
  } catch (PDOException $e) {
    // Обработка ошибки запроса
    die("Ошибка выполнения запроса: " . $e->getMessage());
  }
}
