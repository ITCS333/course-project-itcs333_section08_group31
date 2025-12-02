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

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Preflight OK']);
    exit;
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
// Example: require_once '../config/Database.php';
require_once __DIR__ . '/Database.php';

// TODO: Get the PDO database connection
// Example: $database = new Database();
// Example: $db = $database->getConnection();
$database = new Database();
$db = $database->getConnection();

// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

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
// RESOURCE FUNCTIONS
// ============================================================================

/**
 * Function: Get all resources
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, created_at)
 *   - order: Optional sort order (asc or desc, default: desc)
 * 
 * Response:
 *   - success: true/false
 *   - data: Array of resource objects
 */
function getAllResources($db) {
    // TODO: Initialize the base SQL query
    // SELECT id, title, description, link, created_at FROM resources
    $sql = "SELECT id, title, description, link, created_at FROM resources";

    // Access query params
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $hasSearch = $search !== '';

    // TODO: Check if search parameter exists
    // If yes, add WHERE clause using LIKE to search title and description
    // Use OR to search both fields
    if ($hasSearch) {
        $sql .= " WHERE title LIKE :search OR description LIKE :search";
    }

    // TODO: Check if sort parameter exists and validate it
    // Only allow: title, created_at
    // Default to created_at if not provided or invalid
    $allowedSort = ['title', 'created_at'];
    $sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSort, true)
        ? $_GET['sort']
        : 'created_at';

    // TODO: Check if order parameter exists and validate it
    // Only allow: asc, desc
    // Default to desc if not provided or invalid
    $order = isset($_GET['order']) && in_array(strtolower($_GET['order']), ['asc', 'desc'], true)
        ? strtoupper($_GET['order'])
        : 'DESC';

    // TODO: Add ORDER BY clause to query
    $sql .= " ORDER BY {$sort} {$order}";

    // TODO: Prepare the SQL query using PDO
    $stmt = $db->prepare($sql);

    // TODO: If search parameter was used, bind the search parameter
    // Use % wildcards for LIKE search
    if ($hasSearch) {
        $like = '%' . $search . '%';
        $stmt->bindValue(':search', $like, PDO::PARAM_STR);
    }

    // TODO: Execute the query
    $stmt->execute();

    // TODO: Fetch all results as an associative array
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Return JSON response with success status and data
    // Use the helper function sendResponse()
    sendResponse(['success' => true, 'data' => $resources]);
}


/**
 * Function: Get a single resource by ID
 * Method: GET
 * 
 * Parameters:
 *   - $resourceId: The resource's database ID
 * 
 * Response:
 *   - success: true/false
 *   - data: Resource object or error message
 */
function getResourceById($db, $resourceId) {
    // TODO: Validate that resource ID is provided and is numeric
    // If not, return error response with 400 status
    if (!$resourceId || !ctype_digit((string) $resourceId)) {
        sendResponse(['success' => false, 'message' => 'Invalid or missing resource ID.'], 400);
    }

    // TODO: Prepare SQL query to select resource by id
    // SELECT id, title, description, link, created_at FROM resources WHERE id = ?
    $sql = "SELECT id, title, description, link, created_at FROM resources WHERE id = :id LIMIT 1";

    // TODO: Bind the resource_id parameter
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $resourceId, PDO::PARAM_INT);

    // TODO: Execute the query
    $stmt->execute();

    // TODO: Fetch the result as an associative array
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);

    // TODO: Check if resource exists
    // If yes, return success response with resource data
    // If no, return error response with 404 status
    if ($resource) {
        sendResponse(['success' => true, 'data' => $resource]);
    } else {
        sendResponse(['success' => false, 'message' => 'Resource not found.'], 404);
    }
}


/**
 * Function: Create a new resource
 * Method: POST
 * 
 * Required JSON Body:
 *   - title: Resource title (required)
 *   - description: Resource description (optional)
 *   - link: URL to the resource (required)
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 *   - id: ID of created resource (on success)
 */
function createResource($db, $data) {
    // TODO: Validate required fields
    // Check if title and link are provided and not empty
    // If any required field is missing, return error response with 400 status
    $validation = validateRequiredFields($data, ['title', 'link']);
    if (!$validation['valid']) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields.',
            'missing' => $validation['missing']
        ], 400);
    }

    // TODO: Sanitize input data
    // Trim whitespace from all fields
    // Validate URL format for link using filter_var with FILTER_VALIDATE_URL
    // If URL is invalid, return error response with 400 status
    $title = sanitizeInput($data['title']);
    $description = isset($data['description']) ? sanitizeInput($data['description']) : '';
    $link = trim($data['link']);

    if (!validateUrl($link)) {
        sendResponse(['success' => false, 'message' => 'Invalid URL format.'], 400);
    }

    // TODO: Set default value for description if not provided
    // Use empty string as default (already done above)

    // TODO: Prepare INSERT query
    // INSERT INTO resources (title, description, link) VALUES (?, ?, ?)
    $sql = "INSERT INTO resources (title, description, link, created_at) 
            VALUES (:title, :description, :link, NOW())";

    // TODO: Bind parameters
    // Bind title, description, and link
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
    $stmt->bindValue(':description', $description, PDO::PARAM_STR);
    $stmt->bindValue(':link', $link, PDO::PARAM_STR);

    // TODO: Execute the query
    $success = $stmt->execute();

    // TODO: Check if insert was successful
    // If yes, get the last inserted ID using $db->lastInsertId()
    // Return success response with 201 status and the new resource ID
    // If no, return error response with 500 status
    if ($success) {
        $newId = $db->lastInsertId();
        sendResponse([
            'success' => true,
            'message' => 'Resource created successfully.',
            'id'      => $newId
        ], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to create resource.'], 500);
    }
}


/**
 * Function: Update an existing resource
 * Method: PUT
 * 
 * Required JSON Body:
 *   - id: The resource's database ID (required)
 *   - title: Updated resource title (optional)
 *   - description: Updated description (optional)
 *   - link: Updated URL (optional)
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 */
function updateResource($db, $data) {
    // TODO: Validate that resource ID is provided
    // If not, return error response with 400 status
    if (empty($data['id']) || !ctype_digit((string) $data['id'])) {
        sendResponse(['success' => false, 'message' => 'Missing or invalid resource ID.'], 400);
    }
    $resourceId = (int) $data['id'];

    // TODO: Check if resource exists
    // Prepare and execute a SELECT query to find the resource by id
    // If not found, return error response with 404 status
    $checkSql = "SELECT id FROM resources WHERE id = :id LIMIT 1";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':id', $resourceId, PDO::PARAM_INT);
    $checkStmt->execute();
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        sendResponse(['success' => false, 'message' => 'Resource not found.'], 404);
    }

    // TODO: Build UPDATE query dynamically based on provided fields
    // Initialize empty arrays for fields to update and values
    // Check which fields are provided (title, description, link)
    // Add each provided field to the update arrays
    $fields = [];
    $params = [];

    if (isset($data['title']) && $data['title'] !== '') {
        $fields[] = 'title = :title';
        $params[':title'] = sanitizeInput($data['title']);
    }
    if (isset($data['description'])) {
        $fields[] = 'description = :description';
        $params[':description'] = sanitizeInput($data['description']);
    }
    if (isset($data['link']) && $data['link'] !== '') {
        $link = trim($data['link']);
        // TODO: If link is being updated, validate URL format
        // Use filter_var with FILTER_VALIDATE_URL
        // If invalid, return error response with 400 status
        if (!validateUrl($link)) {
            sendResponse(['success' => false, 'message' => 'Invalid URL format.'], 400);
        }
        $fields[] = 'link = :link';
        $params[':link'] = $link;
    }

    // TODO: If no fields to update, return error response with 400 status
    if (empty($fields)) {
        sendResponse(['success' => false, 'message' => 'No fields to update.'], 400);
    }

    // TODO: Build the complete UPDATE SQL query
    // UPDATE resources SET field1 = ?, field2 = ? WHERE id = ?
    $updateSql = "UPDATE resources SET " . implode(', ', $fields) . " WHERE id = :id";

    // TODO: Prepare the query
    $stmt = $db->prepare($updateSql);

    // TODO: Bind parameters dynamically
    // Bind all update values, then bind the resource ID at the end
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmt->bindValue(':id', $resourceId, PDO::PARAM_INT);

    // TODO: Execute the query
    $success = $stmt->execute();

    // TODO: Check if update was successful
    // If yes, return success response with 200 status
    // If no, return error response with 500 status
    if ($success) {
        sendResponse(['success' => true, 'message' => 'Resource updated successfully.'], 200);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to update resource.'], 500);
    }
}


/**
 * Function: Delete a resource
 * Method: DELETE
 * 
 * Parameters:
 *   - $resourceId: The resource's database ID
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 * 
 * Note: This should also delete all associated comments
 */
function deleteResource($db, $resourceId) {
    // TODO: Validate that resource ID is provided and is numeric
    // If not, return error response with 400 status
    if (!$resourceId || !ctype_digit((string) $resourceId)) {
        sendResponse(['success' => false, 'message' => 'Missing or invalid resource ID.'], 400);
    }
    $resourceId = (int) $resourceId;

    // TODO: Check if resource exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $checkSql = "SELECT id FROM resources WHERE id = :id LIMIT 1";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':id', $resourceId, PDO::PARAM_INT);
    $checkStmt->execute();
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        sendResponse(['success' => false, 'message' => 'Resource not found.'], 404);
    }

    // TODO: Begin a transaction (for data integrity)
    // Use $db->beginTransaction()
    $db->beginTransaction();

    try {
        // TODO: First, delete all associated comments
        // Prepare DELETE query for comments table
        // DELETE FROM comments WHERE resource_id = ?
        $delCommentsSql = "DELETE FROM comments WHERE resource_id = :rid";
        $delCommentsStmt = $db->prepare($delCommentsSql);
        $delCommentsStmt->bindValue(':rid', $resourceId, PDO::PARAM_INT);
        $delCommentsStmt->execute();

        // TODO: Then, delete the resource
        // Prepare DELETE query for resources table
        // DELETE FROM resources WHERE id = ?
        $delResourceSql = "DELETE FROM resources WHERE id = :id";
        $delResourceStmt = $db->prepare($delResourceSql);
        $delResourceStmt->bindValue(':id', $resourceId, PDO::PARAM_INT);
        $delResourceStmt->execute();

        // TODO: Commit the transaction
        // Use $db->commit()
        $db->commit();

        // TODO: Return success response with 200 status
        sendResponse(['success' => true, 'message' => 'Resource and associated comments deleted.'], 200);
    } catch (Exception $e) {
        // TODO: Rollback the transaction on error
        // Use $db->rollBack()
        $db->rollBack();

        // TODO: Return error response with 500 status
        error_log("Error deleting resource: " . $e->getMessage());
        sendResponse(['success' => false, 'message' => 'Failed to delete resource.'], 500);
    }
}


// ============================================================================
// COMMENT FUNCTIONS
// ============================================================================

/**
 * Function: Get all comments for a specific resource
 * Method: GET with action=comments
 * 
 * Query Parameters:
 *   - resource_id: The resource's database ID (required)
 * 
 * Response:
 *   - success: true/false
 *   - data: Array of comment objects
 */
function getCommentsByResourceId($db, $resourceId) {
    // TODO: Validate that resource_id is provided and is numeric
    // If not, return error response with 400 status
    if (!$resourceId || !ctype_digit((string) $resourceId)) {
        sendResponse(['success' => false, 'message' => 'Missing or invalid resource_id.'], 400);
    }
    $resourceId = (int) $resourceId;

    // TODO: Prepare SQL query to select comments for the resource
    // SELECT id, resource_id, author, text, created_at 
    // FROM comments 
    // WHERE resource_id = ? 
    // ORDER BY created_at ASC
    $sql = "SELECT id, resource_id, author, text, created_at
            FROM comments
            WHERE resource_id = :rid
            ORDER BY created_at ASC";

    // TODO: Bind the resource_id parameter
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':rid', $resourceId, PDO::PARAM_INT);

    // TODO: Execute the query
    $stmt->execute();

    // TODO: Fetch all results as an associative array
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Return success response with comments data
    // Even if no comments exist, return empty array (not an error)
    sendResponse(['success' => true, 'data' => $comments]);
}


/**
 * Function: Create a new comment
 * Method: POST with action=comment
 * 
 * Required JSON Body:
 *   - resource_id: The resource's database ID (required)
 *   - author: Name of the comment author (required)
 *   - text: Comment text content (required)
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 *   - id: ID of created comment (on success)
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    // Check if resource_id, author, and text are provided and not empty
    // If any required field is missing, return error response with 400 status
    $validation = validateRequiredFields($data, ['resource_id', 'author', 'text']);
    if (!$validation['valid']) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields.',
            'missing' => $validation['missing']
        ], 400);
    }

    // TODO: Validate that resource_id is numeric
    // If not, return error response with 400 status
    if (!ctype_digit((string) $data['resource_id'])) {
        sendResponse(['success' => false, 'message' => 'resource_id must be numeric.'], 400);
    }
    $resourceId = (int) $data['resource_id'];

    // TODO: Check if the resource exists
    // Prepare and execute SELECT query on resources table
    // If resource not found, return error response with 404 status
    $checkSql = "SELECT id FROM resources WHERE id = :id LIMIT 1";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':id', $resourceId, PDO::PARAM_INT);
    $checkStmt->execute();
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        sendResponse(['success' => false, 'message' => 'Resource not found for this comment.'], 404);
    }

    // TODO: Sanitize input data
    // Trim whitespace from author and text
    $author = sanitizeInput($data['author']);
    $text   = sanitizeInput($data['text']);

    // TODO: Prepare INSERT query
    // INSERT INTO comments (resource_id, author, text) VALUES (?, ?, ?)
    $sql = "INSERT INTO comments (resource_id, author, text, created_at)
            VALUES (:rid, :author, :text, NOW())";

    // TODO: Bind parameters
    // Bind resource_id, author, and text
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':rid', $resourceId, PDO::PARAM_INT);
    $stmt->bindValue(':author', $author, PDO::PARAM_STR);
    $stmt->bindValue(':text', $text, PDO::PARAM_STR);

    // TODO: Execute the query
    $success = $stmt->execute();

    // TODO: Check if insert was successful
    // If yes, get the last inserted ID using $db->lastInsertId()
    // Return success response with 201 status and the new comment ID
    // If no, return error response with 500 status
    if ($success) {
        $commentId = $db->lastInsertId();
        sendResponse([
            'success' => true,
            'message' => 'Comment created successfully.',
            'id'      => $commentId
        ], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to create comment.'], 500);
    }
}


/**
 * Function: Delete a comment
 * Method: DELETE with action=delete_comment
 * 
 * Query Parameters or JSON Body:
 *   - comment_id: The comment's database ID (required)
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that comment_id is provided and is numeric
    // If not, return error response with 400 status
    if (!$commentId || !ctype_digit((string) $commentId)) {
        sendResponse(['success' => false, 'message' => 'Missing or invalid comment_id.'], 400);
    }
    $commentId = (int) $commentId;

    // TODO: Check if comment exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $checkSql = "SELECT id FROM comments WHERE id = :id LIMIT 1";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':id', $commentId, PDO::PARAM_INT);
    $checkStmt->execute();
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        sendResponse(['success' => false, 'message' => 'Comment not found.'], 404);
    }

    // TODO: Prepare DELETE query
    // DELETE FROM comments WHERE id = ?
    $sql = "DELETE FROM comments WHERE id = :id";

    // TODO: Bind the comment_id parameter
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $commentId, PDO::PARAM_INT);

    // TODO: Execute the query
    $success = $stmt->execute();

    // TODO: Check if delete was successful
    // If yes, return success response with 200 status
    // If no, return error response with 500 status
    if ($success) {
        sendResponse(['success' => true, 'message' => 'Comment deleted successfully.'], 200);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to delete comment.'], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on HTTP method and action parameter
    
    if ($method === 'GET') {
        // TODO: Check the action parameter to determine which function to call
        
        // If action is 'comments', get comments for a resource
        // TODO: Check if action === 'comments'
        // Get resource_id from query parameters
        // Call getCommentsByResourceId()
        if ($action === 'comments') {
            getCommentsByResourceId($db, $resource_id);
        }
        // If id parameter exists, get single resource
        // TODO: Check if 'id' parameter exists in $_GET
        // Call getResourceById()
        elseif (!empty($id)) {
            getResourceById($db, $id);
        }
        // Otherwise, get all resources
        // TODO: Call getAllResources()
        else {
            getAllResources($db);
        }

    } elseif ($method === 'POST') {
        // TODO: Check the action parameter to determine which function to call
        
        // If action is 'comment', create a new comment
        // TODO: Check if action === 'comment'
        // Call createComment()
        if ($action === 'comment') {
            createComment($db, $bodyData);
        }
        // Otherwise, create a new resource
        // TODO: Call createResource()
        else {
            createResource($db, $bodyData);
        }

    } elseif ($method === 'PUT') {
        // TODO: Update a resource
        // Call updateResource()
        updateResource($db, $bodyData);

    } elseif ($method === 'DELETE') {
        // TODO: Check the action parameter to determine which function to call
        
        // If action is 'delete_comment', delete a comment
        // TODO: Check if action === 'delete_comment'
        // Get comment_id from query parameters or request body
        // Call deleteComment()
        if ($action === 'delete_comment') {
            $cid = $comment_id ?? ($bodyData['comment_id'] ?? null);
            deleteComment($db, $cid);
        }
        // Otherwise, delete a resource
        // TODO: Get resource id from query parameter or request body
        // Call deleteResource()
        else {
            $rid = $id ?? ($bodyData['id'] ?? null);
            deleteResource($db, $rid);
        }

    } else {
        // TODO: Return error for unsupported methods
        // Set HTTP status to 405 (Method Not Allowed)
        // Return JSON error message using sendResponse()
        sendResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional, use error_log())
    // Return generic error response with 500 status
    // Do NOT expose detailed error messages to the client in production
    error_log("Database error in Course Resources API: " . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Internal server error.'], 500);

} catch (Exception $e) {
    // TODO: Handle general errors
    // Log the error message (optional)
    // Return error response with 500 status
    error_log("General error in Course Resources API: " . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Internal server error.'], 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param array $data - Data to send (should include 'success' key)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code using http_response_code()
    http_response_code($statusCode);
    
    // TODO: Ensure data is an array
    // If not, wrap it in an array
    if (!is_array($data)) {
        $data = ['data' => $data];
    }
    
    // TODO: Echo JSON encoded data
    // Use JSON_PRETTY_PRINT for readability (optional)
    echo json_encode($data, JSON_PRETTY_PRINT);
    
    // TODO: Exit to prevent further execution
    exit;
}


/**
 * Helper function to validate URL format
 * 
 * @param string $url - URL to validate
 * @return bool - True if valid, false otherwise
 */
function validateUrl($url) {
    // TODO: Use filter_var with FILTER_VALIDATE_URL
    // Return true if valid, false otherwise
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace using trim()
    $data = trim($data);
    
    // TODO: Strip HTML tags using strip_tags()
    $data = strip_tags($data);
    
    // TODO: Convert special characters using htmlspecialchars()
    // Use ENT_QUOTES to escape both double and single quotes
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    // TODO: Return sanitized data
    return $data;
}


/**
 * Helper function to validate required fields
 * 
 * @param array $data - Data array to validate
 * @param array $requiredFields - Array of required field names
 * @return array - Array with 'valid' (bool) and 'missing' (array of missing fields)
 */
function validateRequiredFields($data, $requiredFields) {
    // TODO: Initialize empty array for missing fields
    $missing = [];
    
    // TODO: Loop through required fields
    // Check if each field exists in data and is not empty
    // If missing or empty, add to missing fields array
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            $missing[] = $field;
        }
    }
    
    // TODO: Return result array
    // ['valid' => (count($missing) === 0), 'missing' => $missing]
    return [
        'valid'   => count($missing) === 0,
        'missing' => $missing,
    ];
}
?>