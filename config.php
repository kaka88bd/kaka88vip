<?php
// config.php

define('BASE_URL', 'https://kaka88bd.free.nf');
define('SITE_NAME', 'KAKA88BD');
define('DEBUG', true);

date_default_timezone_set('Asia/Dhaka');

if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>