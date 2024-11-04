<?php
// Application configuration
define('BASE_URL', 'http://localhost/tour-guide');
define('SITE_NAME', 'Tourists Guide Hub');

// Session configuration
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone
date_default_timezone_set('UTC');
?> 