<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Optional: auto-logout after 12 hours
$timeout = 60 * 60 * 12;

if (isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
}

$_SESSION['last_activity'] = time();
?>
