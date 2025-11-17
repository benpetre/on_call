<?php
require_once __DIR__ . '/session.php';

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /on_call_dev/auth/login.php");
        exit;
    }

    // Enforce profile completion for non-admin users
    $current_uri = $_SERVER['REQUEST_URI'] ?? '';

    if (
        isset($_SESSION['role']) &&
        $_SESSION['role'] !== 'admin' && 
        (empty($_SESSION['profile_complete']) || $_SESSION['profile_complete'] == 0) &&
        strpos($current_uri, '/on_call_dev/pages/user/edit_profile.php') === false
    ) {
        header("Location: /on_call_dev/pages/user/edit_profile.php");
        exit;
    }
}

function require_role($roles = []) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles)) {
        die("Access denied.");
    }
}
?>
