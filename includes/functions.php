<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: index.php");
        exit();
    }
}

// In includes/functions.php
function get_user_id() {
    if (!isset($_SESSION['user_id'])) {
        // Try to get from different session keys
        if (isset($_SESSION['id'])) {
            return $_SESSION['id'];
        }
        return null;
    }
    return $_SESSION['user_id'];
}

function base_url($path = '') {
    return '/' . ltrim($path, '/');
}
?>