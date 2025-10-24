<?php
/**
 * Выход из админ-панели
 */

require_once 'auth.php';

// Выходим из системы
logoutAdmin();

// Редирект на страницу входа
header('Location: login.php');
exit;
?>
