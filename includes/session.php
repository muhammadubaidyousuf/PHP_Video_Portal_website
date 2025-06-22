<?php
// Start session
session_start();

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Check if user is not logged in and trying to access admin pages
if (!isset($_SESSION['user_id']) && !strpos($_SERVER['PHP_SELF'], 'login.php')) {
    header('Location: /video_portal/admin/login.php');
exit();
}
?>
