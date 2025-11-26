<?php
// logout.php
// Destroys the current session and sends the user back to the login page.

session_start(); // Resume the session

// Only allow POST for logout (more secure than GET)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // If someone tries to access logout directly by URL, just send them to login
    header('Location: login.html');
    exit;
}

// Clear all session data
$_SESSION = [];
session_unset();

// Destroy the session
session_destroy();

// Optionally clear the session cookie as well
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// (Optional) Regenerate a new session ID
session_regenerate_id(true);

// Redirect back to login page
header('Location: login.html');
exit;