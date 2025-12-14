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
 *     GET    /IndexCourse_Resources.php
 *     GET    /IndexCourse_Resources.php?id={id}
 *     POST   /IndexCourse_Resources.php
 *     PUT    /IndexCourse_Resources.php
 *     DELETE /IndexCourse_Resources.php?id={id}
 * 
 *   Comments:
 *     GET    /IndexCourse_Resources.php?resource_id={id}&action=comments
 *     POST   /IndexCourse_Resources.php?action=comment
 *     DELETE /IndexCourse_Resources.php?comment_id={id}&action=delete_comment
 */

// ============================================================================
// SESSION START — REQUIRED BY AUTOGRADER
// ============================================================================

// TODO: Start a PHP session
session_start();

// ============================================================================
// HEADERS AND INITIALIZATION
// ============================================================================

// TODO: Set headers for JSON response and CORS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Preflight OK']);
    exit;
}

// TODO: Include the database connection class
require_once __DIR__ . '/Database.php';

// TODO: Get the PDO database connection
$database = new Database();
$db = $database->getConnection();

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// TODO: Get the request body for POST and PUT requests
$rawInput = file_get_contents('php://input');
$bodyData = $rawInput ? json_decode($rawInput, true) : [];
if (!is_array($bodyData)) {
    $bodyData = [];
}

// TODO: Parse query parameters
$action      = $_GET['action']      ?? null;
$id          = $_GET['id']          ?? null;
$resource_id = $_GET['resource_id'] ?? null;
$comment_id  = $_GET['comment_id']  ?? null;


// ============================================================================
// RESOURCE FUNCTIONS
// ============================================================================

function getAllResources($db) {
    $sql = "SELECT id, title, description, link, created_at FROM resources";

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $hasSearch = $search !== '';

    if ($hasSearch) {
        $sql .= " WHERE title LIKE :search OR description LIKE :search";
    }

    $allowedSort = ['title', 'created_at'];
    $sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSort, true)
        ? $_GET['sort']
        : 'created_at';

    $order = isset($_GET['order']) && in_array(strtolower($_GET['order']), ['asc', 'desc'], true)
        ? strtoupper($_GET['order'])
        : 'DESC';

    $sql .= " ORDER BY {$sort} {$order}";

    $stmt = $db->prepare($sql);

    if ($hasSearch) {
        $like = "%" . $search . "%";
        $stmt->bindValue(':search', $like, PDO::PARAM_STR);
    }

    $stmt->execute();
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(['success' => true, 'data' => $resources]);
}

function getResourceById($db, $resourceId) {
    if (!$resourceId || !ctype_digit((string)$resourceId)) {
        sendResponse(['success' => false, 'message' => 'Invalid or missing resource ID.'], 400);
    }

    $sql = "SELECT id, title, description, link, created_at FROM resources WHERE id = :id LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $resourceId, PDO::PARAM_INT);
    $stmt->execute();

    $resource = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resource) {
        sendResponse(['success' => true, 'data' => $resource]);
    } else {
        sendResponse(['success' => false, 'message' => 'Resource not found.'], 404);
    }
}

function createResource($db, $data) {
    $validation = validateRequiredFields($data, ['title', 'link']);
    if (!$validation['valid']) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields.',
            'missing' => $validation['missing']
        ], 400);
    }

    $title = sanitizeInput($data['title']);
    $description = isset($data['description']) ? sanitizeInput($data['description']) : '';
    $link = trim($data['link']);

    if (!validateUrl($link)) {
        sendResponse(['success' => false, 'message' => 'Invalid URL format.'], 400);
    }

    $sql = "INSERT INTO resources (title, description, link, created_at)
            VALUES (:title, :description, :link, NOW())";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
    $stmt->bindValue(':description', $description, PDO::PARAM_STR);
    $stmt->bindValue(':link', $link, PDO::PARAM_STR);

    $success = $stmt->execute();

    if ($success) {
        sendResponse([
            'success' => true,
            'message' => 'Resource created successfully.',
            'id' => $db->lastInsertId()
        ], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to create resource.'], 500);
    }
}

function updateResource($db, $data) {
    if (empty($data['id']) || !ctype_digit((string)$data['id'])) {
        sendResponse(['success' => false, 'message' => 'Missing or invalid resource ID.'], 400);
    }

    $resourceId = (int)$data['id'];

    $check = $db->prepare("SELECT id FROM resources WHERE id = :id LIMIT 1");
    $check->bindValue(':id', $resourceId, PDO::PARAM_INT);
    $check->execute();

    if (!$check->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(['success' => false, 'message' => 'Resource not found.'], 404);
    }

    $fields = [];
    $params = [];

    if (isset($data['title']) && $data['title'] !== '') {
        $fields[] = "title = :title";
        $params[':title'] = sanitizeInput($data['title']);
    }

    if (isset($data['description'])) {
        $fields[] = "description = :description";
        $params[':description'] = sanitizeInput($data['description']);
    }

    if (isset($data['link']) && $data['link'] !== '') {
        if (!validateUrl($data['link'])) {
            sendResponse(['success' => false, 'message' => 'Invalid URL format.'], 400);
        }
        $fields[] = "link = :link";
        $params[':link'] = trim($data['link']);
    }

    if (empty($fields)) {
        sendResponse(['success' => false, 'message' => 'No fields to update.'], 400);
    }

    $sql = "UPDATE resources SET " . implode(", ", $fields) . " WHERE id = :id";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(':id', $resourceId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        sendResponse(['success' => true, 'message' => 'Resource updated successfully.']);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to update resource.'], 500);
    }
}

function deleteResource($db, $resourceId) {
    if (!$resourceId || !ctype_digit((string)$resourceId)) {
        sendResponse(['success' => false, 'message' => 'Missing or invalid resource ID.'], 400);
    }

    $resourceId = (int)$resourceId;

    $check = $db->prepare("SELECT id FROM resources WHERE id = :id LIMIT 1");
    $check->bindValue(':id', $resourceId, PDO::PARAM_INT);
    $check->execute();

    if (!$check->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(['success' => false, 'message' => 'Resource not found.'], 404);
    }

    $db->beginTransaction();

    try {
        $delComments = $db->prepare("DELETE FROM comments WHERE resource_id = :rid");
        $delComments->bindValue(':rid', $resourceId, PDO::PARAM_INT);
        $delComments->execute();

        $delResource = $db->prepare("DELETE FROM resources WHERE id = :id");
        $delResource->bindValue(':id', $resourceId, PDO::PARAM_INT);
        $delResource->execute();

        $db->commit();

        sendResponse(['success' => true, 'message' => 'Resource and comments deleted.']);
    } catch (Exception $e) {
        $db->rollBack();
        sendResponse(['success' => false, 'message' => 'Failed to delete resource.'], 500);
    }
}


// ============================================================================
// COMMENT FUNCTIONS
// ============================================================================

function getCommentsByResourceId($db, $resourceId) {
    if (!$resourceId || !ctype_digit((string)$resourceId)) {
        sendResponse(['success' => false, 'message' => 'Invalid resource_id'], 400);
    }

    $stmt = $db->prepare("
        SELECT id, resource_id, author, text, created_at
        FROM comments
        WHERE resource_id = :rid
        ORDER BY created_at ASC
    ");

    $stmt->bindValue(':rid', (int)$resourceId, PDO::PARAM_INT);
    $stmt->execute();

    sendResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function createComment($db, $data) {
    $validation = validateRequiredFields($data, ['resource_id', 'author', 'text']);
    if (!$validation['valid']) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields.',
            'missing' => $validation['missing']
        ], 400);
    }

    if (!ctype_digit((string)$data['resource_id'])) {
        sendResponse(['success' => false, 'message' => 'resource_id must be numeric'], 400);
    }

    $resourceId = (int)$data['resource_id'];

    $check = $db->prepare("SELECT id FROM resources WHERE id = :id LIMIT 1");
    $check->bindValue(':id', $resourceId, PDO::PARAM_INT);
    $check->execute();

    if (!$check->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }

    $author = sanitizeInput($data['author']);
    $text   = sanitizeInput($data['text']);

    $stmt = $db->prepare("
        INSERT INTO comments (resource_id, author, text, created_at)
        VALUES (:rid, :author, :text, NOW())
    ");

    $stmt->bindValue(':rid', $resourceId, PDO::PARAM_INT);
    $stmt->bindValue(':author', $author, PDO::PARAM_STR);
    $stmt->bindValue(':text', $text, PDO::PARAM_STR);

    if ($stmt->execute()) {
        sendResponse([
            'success' => true,
            'message' => 'Comment created successfully.',
            'id' => $db->lastInsertId()
        ], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to create comment'], 500);
    }
}

function deleteComment($db, $commentId) {
    if (!$commentId || !ctype_digit((string)$commentId)) {
        sendResponse(['success' => false, 'message' => 'Invalid comment_id'], 400);
    }

    $commentId = (int)$commentId;

    $check = $db->prepare("SELECT id FROM comments WHERE id = :id LIMIT 1");
    $check->bindValue(':id', $commentId, PDO::PARAM_INT);
    $check->execute();

    if (!$check->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(['success' => false, 'message' => 'Comment not found'], 404);
    }

    $stmt = $db->prepare("DELETE FROM comments WHERE id = :id");
    $stmt->bindValue(':id', $commentId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        sendResponse(['success' => true, 'message' => 'Comment deleted successfully']);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to delete comment'], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {

    if ($method === 'GET') {
        if ($action === 'comments') {
            getCommentsByResourceId($db, $resource_id);
        } elseif (!empty($id)) {
            getResourceById($db, $id);
        } else {
            getAllResources($db);
        }

    } elseif ($method === 'POST') {
        if ($action === 'comment') {
            createComment($db, $bodyData);
        } else {
            createResource($db, $bodyData);
        }

    } elseif ($method === 'PUT') {
        updateResource($db, $bodyData);

    } elseif ($method === 'DELETE') {
        if ($action === 'delete_comment') {
            $cid = $comment_id ?? ($bodyData['comment_id'] ?? null);
            deleteComment($db, $cid);
        } else {
            $rid = $id ?? ($bodyData['id'] ?? null);
            deleteResource($db, $rid);
        }

    } else {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }

} catch (PDOException $e) {
    error_log("Database error in Course Resources API: " . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Internal server error'], 500);

} catch (Exception $e) {
    error_log("General error in Course Resources API: " . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Internal server error'], 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);

    if (!is_array($data)) {
        $data = ['data' => $data];
    }

    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validateRequiredFields($data, $requiredFields) {
    $missing = [];

    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            $missing[] = $field;
        }
    }

    return [
        'valid' => count($missing) === 0,
        'missing' => $missing
    ];
}

?>