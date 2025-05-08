<?php
// session configuration
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

session_set_cookie_params([
    'lifetime' => 1800,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();


if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    session_create_id(); // Create a new session ID
    $_SESSION['last_regeneration'] = time();
} else {
    $interval = 60 * 30; // 30 minutes
    // Regenerate session ID every 30 minutes
    // This is a simple way to prevent session fixation attacks
    if (time() - $_SESSION['last_regeneration'] > $interval) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}
