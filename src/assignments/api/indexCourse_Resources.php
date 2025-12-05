<?php
/**
 * Course Resources API
 * 
 * This is a RESTful API that handles all CRUD operations for course resources 
 * and their associated comments/discussions.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: resources
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(255))
 *   - description (TEXT)
 *   - link (VARCHAR(500))
 *   - created_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - resource_id (INT, FOREIGN KEY references resources.id)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve resource(s) or comment(s)
 *   - POST: Create a new resource or comment
 *   - PUT: Update an existing resource
 *   - DELETE: Delete a resource or comment
 * 
 * Response Format: JSON
 * 
 * API Endpoints:
 *   Resources:
 *     GET    /IndexCourse_Resources.php                    - Get all resources
 *     GET    /IndexCourse_Resources.php?id={id}           - Get single resource by ID
 *     POST   /IndexCourse_Resources.php                    - Create new resource
 *     PUT    /IndexCourse_Resources.php                    - Update resource
 *     DELETE /IndexCourse_Resources.php?id={id}           - Delete resource
 * 
 *   Comments:
 *     GET    /IndexCourse_Resources.php?resource_id={id}&action=comments  - Get comments for resource
 *     POST   /IndexCourse_Resources.php?action=comment                    - Create new comment
 *     DELETE /IndexCourse_Resources.php?comment_id={id}&action=delete_comment - Delete comment
 */

// ============================================================================
// HEADERS AND INITIALIZATION
// ============================================================================


// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Preflight OK']);
    exit;
}


require_once __DIR__ . '/../../admin/db.php'; 

// Initialize PDO
try {
    $db = getDBConnection();
} catch (PDOException $e) {
    sendError('Database connection failed: ' . $e->getMessage(), 500);
}

// Get Request Data
$method = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?? [];

// Parse Query Parameters
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$assignment_id = $_GET['assignment_id'] ?? null;

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode() with associative array parameter
$rawInput = file_get_contents('php://input');
$bodyData = $rawInput ? json_decode($rawInput, true) : [];
if (!is_array($bodyData)) {
    $bodyData = [];
}

// TODO: Parse query parameters
// Get 'action', 'id', 'resource_id', 'comment_id' from $_GET
$action      = $_GET['action']      ?? null;
$id          = $_GET['id']          ?? null;
$resource_id = $_GET['resource_id'] ?? null;
$comment_id  = $_GET['comment_id']  ?? null;


// ============================================================================
// ASSIGNMENT FUNCTIONS
// ============================================================================

function getAllAssignments($db) {
    $sql = "SELECT * FROM assignments ORDER BY due_date ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();

    // Decode files JSON
    foreach ($result as &$row) {
        $row['files'] = json_decode($row['files']);
    }

    sendResponse(['success' => true, 'data' => $result]);
}

function getAssignmentById($db, $id) {
    if (!$id) sendError('ID is required');

    $sql = "SELECT * FROM assignments WHERE id = ? LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row) {
        $row['files'] = json_decode($row['files']);
        sendResponse(['success' => true, 'data' => $row]);
    } else {
        sendError('Assignment not found', 404);
    }
}

function createAssignment($db, $data) {
    // Validation
    if (empty($data['title']) || empty($data['due_date'])) {
        sendError('Title and Due Date are required');
    }

    $title = trim($data['title']);
    $desc = trim($data['description'] ?? '');
    $due = $data['due_date'];
    $files = json_encode($data['files'] ?? []);

    $sql = "INSERT INTO assignments (title, description, due_date, files, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($sql);
    
    if ($stmt->execute([$title, $desc, $due, $files])) {
        sendResponse(['success' => true, 'message' => 'Assignment created', 'id' => $db->lastInsertId()], 201);
    } else {
        sendError('Failed to create assignment', 500);
    }
}

function updateAssignment($db, $data) {
    if (empty($data['id'])) sendError('ID is required');

    $id = $data['id'];
    $title = trim($data['title']);
    $desc = trim($data['description'] ?? '');
    $due = $data['due_date'];
    $files = json_encode($data['files'] ?? []);

    $sql = "UPDATE assignments SET title=?, description=?, due_date=?, files=? WHERE id=?";
    $stmt = $db->prepare($sql);

    if ($stmt->execute([$title, $desc, $due, $files, $id])) {
        sendResponse(['success' => true, 'message' => 'Assignment updated']);
    } else {
        sendError('Failed to update assignment', 500);
    }
}

function deleteAssignment($db, $id) {
    if (!$id) sendError('ID is required');

    // Use transaction to delete comments first
    $db->beginTransaction();
    try {
        $db->prepare("DELETE FROM comments WHERE assignment_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM assignments WHERE id = ?")->execute([$id]);
        $db->commit();
        sendResponse(['success' => true, 'message' => 'Assignment deleted']);
    } catch (Exception $e) {
        $db->rollBack();
        sendError('Failed to delete: ' . $e->getMessage(), 500);
    }
}

// ============================================================================
// COMMENT FUNCTIONS
// ============================================================================

function getCommentsByAssignmentId($db, $id) {
    if (!$id) sendError('Assignment ID is required');

    $sql = "SELECT * FROM comments WHERE assignment_id = ? ORDER BY created_at ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    $comments = $stmt->fetchAll();

    sendResponse(['success' => true, 'data' => $comments]);
}

function createComment($db, $data) {
    if (empty($data['assignment_id']) || empty($data['text'])) {
        sendError('Assignment ID and text are required');
    }

    $asgId = $data['assignment_id'];
    $author = $data['author'] ?? 'Student';
    $text = trim($data['text']);

    $sql = "INSERT INTO comments (assignment_id, author, text, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $db->prepare($sql);

    if ($stmt->execute([$asgId, $author, $text])) {
        sendResponse(['success' => true, 'message' => 'Comment added', 'id' => $db->lastInsertId()], 201);
    } else {
        sendError('Failed to add comment', 500);
    }
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function sendError($message, $code = 400) {
    sendResponse(['success' => false, 'message' => $message], $code);
}

// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    if ($method === 'GET') {
        if ($action === 'comments') {
            getCommentsByAssignmentId($db, $assignment_id);
        } elseif ($id) {
            getAssignmentById($db, $id);
        } else {
            getAllAssignments($db);
        }
    } 
    elseif ($method === 'POST') {
        if ($action === 'comment') {
            createComment($db, $data);
        } else {
            createAssignment($db, $data);
        }
    } 
    elseif ($method === 'PUT') {
        updateAssignment($db, $data);
    } 
    elseif ($method === 'DELETE') {
        // Handle ID from Query or Body
        $delId = $id ?? ($data['id'] ?? null);
        deleteAssignment($db, $delId);
    } 
    else {
        sendError('Method not allowed', 405);
    }

} catch (Exception $e) {
    sendError('Server Error: ' . $e->getMessage(), 500);
}
?>
