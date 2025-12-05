<?php
/**
 * Assignments API
 * Handles CRUD for assignments and associated comments.
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// Adjust the path to your db.php as needed
require_once __DIR__ . '/../../admin/db.php'; 

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Parse Query Parameters
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$assignment_id = $_GET['assignment_id'] ?? null;

// ==========================================================
// ASSIGNMENT CRUD
// ==========================================================

if ($method === 'GET' && !$action) {
    if ($id) {
        // Get Single Assignment
        $stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        // Decode files JSON for the frontend
        if ($data) { $data['files'] = json_decode($data['files']); }
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        // Get All Assignments
        $stmt = $pdo->query("SELECT * FROM assignments ORDER BY due_date ASC");
        $all = $stmt->fetchAll();
        // Decode files JSON for each row
        foreach($all as &$row) { $row['files'] = json_decode($row['files']); }
        echo json_encode(['success' => true, 'data' => $all]);
    }
}
elseif ($method === 'POST' && !$action) {
    // Create Assignment
    $sql = "INSERT INTO assignments (title, description, due_date, files, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    // Encode files array to JSON for storage
    $filesJson = json_encode($input['files'] ?? []);
    $stmt->execute([$input['title'], $input['description'], $input['due_date'], $filesJson]);
    echo json_encode(['success' => true, 'message' => 'Assignment created']);
}
elseif ($method === 'PUT') {
    // Update Assignment
    $sql = "UPDATE assignments SET title=?, description=?, due_date=?, files=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $filesJson = json_encode($input['files'] ?? []);
    $stmt->execute([$input['title'], $input['description'], $input['due_date'], $filesJson, $input['id']]);
    echo json_encode(['success' => true, 'message' => 'Assignment updated']);
}
elseif ($method === 'DELETE' && !$action) {
    // Delete Assignment
    $delId = $_GET['id'] ?? $input['id'];
    $pdo->prepare("DELETE FROM comments WHERE assignment_id = ?")->execute([$delId]); // Clean up comments first
    $pdo->prepare("DELETE FROM assignments WHERE id = ?")->execute([$delId]);
    echo json_encode(['success' => true, 'message' => 'Assignment deleted']);
}

// ==========================================================
// COMMENT CRUD
// ==========================================================

elseif ($method === 'GET' && $action === 'comments') {
    // Get Comments for an Assignment
    $stmt = $pdo->prepare("SELECT * FROM comments WHERE assignment_id = ? ORDER BY created_at ASC");
    $stmt->execute([$assignment_id]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
}
elseif ($method === 'POST' && $action === 'comment') {
    // Add Comment
    $stmt = $pdo->prepare("INSERT INTO comments (assignment_id, author, text, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$input['assignment_id'], $input['author'], $input['text']]);
    echo json_encode(['success' => true, 'message' => 'Comment added']);
}

?>
