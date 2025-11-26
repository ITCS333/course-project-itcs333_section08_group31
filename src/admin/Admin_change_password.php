<?php
/**
 * Admin Password Change API
 * 
 * Allows the currently logged-in admin to change **their own** password.
 * Expects JSON: { "current_password": "...", "new_password": "..." }
 * Uses the `users` table (same as IndexLogin.php).
 */

session_start();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Preflight OK']);
    exit;
}

// Must be POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method. POST required.']);
    exit;
}

// Must be logged in
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authorized. Please log in again.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = $rawInput ? json_decode($rawInput, true) : null;

if (!is_array($data) || empty($data['current_password']) || empty($data['new_password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'current_password and new_password are required.']);
    exit;
}

$currentPassword = $data['current_password'];
$newPassword     = $data['new_password'];

// simple validation
if (strlen($newPassword) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long.']);
    exit;
}

require_once __DIR__ . '/db.php'; // same DB helper used by IndexLogin.php
$pdo = getDBConnection();

try {
    $userId = $_SESSION['user_id'];

    // Get current hash from DB
    $sql = "SELECT id, password FROM users WHERE id = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    // Check current password
    if (!password_verify($currentPassword, $user['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit;
    }

    // Hash new password
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update DB
    $updateSql = "UPDATE users SET password = :password WHERE id = :id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->bindValue(':password', $newHash, PDO::PARAM_STR);
    $updateStmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $ok = $updateStmt->execute();

    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
    }
    exit;

} catch (PDOException $e) {
    error_log("Error changing admin password: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error.']);
    exit;
}
