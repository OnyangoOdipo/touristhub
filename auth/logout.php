<?php
require_once '../config/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page with logged out message
$_SESSION['success_message'] = "You have been successfully logged out.";
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
?> 