<?php
/**
 * Weekly Course Breakdown API
 * 
 * This is a RESTful API that handles all CRUD operations for weekly course content
 * and discussion comments. It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: weeks
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50), UNIQUE) - Unique identifier (e.g., "week_1")
 *   - title (VARCHAR(200))
 *   - start_date (DATE)
 *   - description (TEXT)
 *   - links (TEXT) - JSON encoded array of links
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50)) - Foreign key reference to weeks.week_id
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve week(s) or comment(s)
 *   - POST: Create a new week or comment
 *   - PUT: Update an existing week
 *   - DELETE: Delete a week or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
// Allow from any origin (adjust in production)
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    http_response_code(200);
    // No body needed
    exit;
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
// Example: require_once '../config/Database.php';
//
// (Keep the comment above — actual include below)
require_once __DIR__ . '/config/Database.php';

// TODO: Get the PDO database connection
// Example: $database = new Database();
//          $db = $database->getConnection();
$database = new Database();
$db = $database->getConnection(); // $db is a PDO instance

// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$rawInput = file_get_contents('php://input');
$requestData = json_decode($rawInput, true);

// TODO: Parse query parameters
// Get the 'resource' parameter to determine if request is for weeks or comments
// Example: ?resource=weeks or ?resource=comments
$resource = isset($_GET['resource']) ? trim($_GET['resource']) : 'weeks';

// ============================================================================
// WEEKS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all weeks or search for specific weeks
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, start_date)
 *   - order: Optional sort order (asc or desc, default: asc)
 */
function getAllWeeks($db) {
    // TODO: Initialize variables for search, sort, and order from query parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;
    $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'start_date';
    $order = isset($_GET['order']) ? strtolower(trim($_GET['order'])) : 'asc';

    // TODO: Start building the SQL query
    // Base query: SELECT week_id, title, start_date, description, links, created_at FROM weeks
    $baseQuery = "SELECT week_id, title, start_date, description, links, created_at, updated_at FROM weeks";
    $params = [];
    $clauses = [];

    // TODO: Check if search parameter exists
    // If yes, add WHERE clause using LIKE for title and description
    // Example: WHERE title LIKE ? OR description LIKE ?
    if ($search !== null && $search !== '') {
        $clauses[] = "(title LIKE :search OR description LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    // TODO: Check if sort parameter exists
    // Validate sort field to prevent SQL injection (only allow: title, start_date, created_at)
    // If invalid, use default sort field (start_date)
    $allowedSortFields = ['title', 'start_date', 'created_at', 'updated_at'];
    if (!isValidSortField($sort, $allowedSortFields)) {
        $sort = 'start_date';
    }

    // TODO: Check if order parameter exists
    // Validate order to prevent SQL injection (only allow: asc, desc)
    // If invalid, use default order (asc)
    $order = ($order === 'desc') ? 'DESC' : 'ASC';

    // TODO: Add ORDER BY clause to the query
    $sql = $baseQuery;
    if (!empty($clauses)) {
        $sql .= ' WHERE ' . implode(' AND ', $clauses);
    }
    $sql .= " ORDER BY {$sort} {$order}";

    // TODO: Prepare the SQL query using PDO
    $stmt = $db->prepare($sql);

    // TODO: Bind parameters if using search
    // Use wildcards for LIKE: "%{$searchTerm}%"
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }

    // TODO: Execute the query
    $stmt->execute();

    // TODO: Fetch all results as an associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Process each week's links field
    // Decode the JSON string back to an array using json_decode()
    foreach ($results as &$row) {
        $linksJson = $row['links'] ?? '[]';
        $decoded = json_decode($linksJson, true);
        $row['links'] = is_array($decoded) ? $decoded : [];
    }

    // TODO: Return JSON response with success status and data
    // Use sendResponse() helper function
    sendResponse(['success' => true, 'data' => $results]);
}


/**
 * Function: Get a single week by week_id
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - week_id: The unique week identifier (e.g., "week_1")
 */
function getWeekById($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (empty($weekId)) {
        sendError('week_id is required', 400);
    }

    // TODO: Prepare SQL query to select week by week_id
    // SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = ?
    $sql = "SELECT week_id, title, start_date, description, links, created_at, updated_at FROM weeks WHERE week_id = :week_id LIMIT 1";
    $stmt = $db->prepare($sql);

    // TODO: Bind the week_id parameter
    $stmt->bindValue(':week_id', $weekId);

    // TODO: Execute the query
    $stmt->execute();

    // TODO: Fetch the result
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // TODO: Check if week exists
    // If yes, decode the links JSON and return success response with week data
    // If no, return error response with 404 status
    if ($row) {
        $linksJson = $row['links'] ?? '[]';
        $decoded = json_decode($linksJson, true);
        $row['links'] = is_array($decoded) ? $decoded : [];
        sendResponse(['success' => true, 'data' => $row]);
    } else {
        sendError('Week not found', 404);
    }
}


/**
 * Function: Create a new week
 * Method: POST
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: Unique week identifier (e.g., "week_1")
 *   - title: Week title (e.g., "Week 1: Introduction to HTML")
 *   - start_date: Start date in YYYY-MM-DD format
 *   - description: Week description
 *   - links: Array of resource links (will be JSON encoded)
 */
function createWeek($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, title, start_date, and description are provided
    // If any field is missing, return error response with 400 status
    $required = ['week_id', 'title', 'start_date', 'description'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            sendError("{$field} is required", 400);
        }
    }

    // TODO: Sanitize input data
    // Trim whitespace from title, description, and week_id
    $week_id = sanitizeInput($data['week_id']);
    $title = sanitizeInput($data['title']);
    $start_date = sanitizeInput($data['start_date']);
    $description = sanitizeInput($data['description']);

    // TODO: Validate start_date format
    // Use a regex or DateTime::createFromFormat() to verify YYYY-MM-DD format
    // If invalid, return error response with 400 status
    if (!validateDate($start_date)) {
        sendError('start_date must be in YYYY-MM-DD format', 400);
    }

    // TODO: Check if week_id already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    $checkSql = "SELECT COUNT(*) FROM weeks WHERE week_id = :week_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':week_id', $week_id);
    $checkStmt->execute();
    $count = (int)$checkStmt->fetchColumn();
    if ($count > 0) {
        sendError('week_id already exists', 409);
    }

    // TODO: Handle links array
    // If links is provided and is an array, encode it to JSON using json_encode()
    // If links is not provided, use an empty array []
    $linksArray = [];
    if (isset($data['links']) && is_array($data['links'])) {
        $linksArray = $data['links'];
    }
    $linksJson = json_encode(array_values($linksArray));

    // TODO: Prepare INSERT query
    // INSERT INTO weeks (week_id, title, start_date, description, links) VALUES (?, ?, ?, ?, ?)
    $insertSql = "INSERT INTO weeks (week_id, title, start_date, description, links, created_at, updated_at) VALUES (:week_id, :title, :start_date, :description, :links, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
    $insertStmt = $db->prepare($insertSql);

    // TODO: Bind parameters
    $insertStmt->bindValue(':week_id', $week_id);
    $insertStmt->bindValue(':title', $title);
    $insertStmt->bindValue(':start_date', $start_date);
    $insertStmt->bindValue(':description', $description);
    $insertStmt->bindValue(':links', $linksJson);

    // TODO: Execute the query
    $executed = $insertStmt->execute();

    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created) and the new week data
    // If no, return error response with 500 status
    if ($executed) {
        // Retrieve the newly created row to return
        $newSql = "SELECT week_id, title, start_date, description, links, created_at, updated_at FROM weeks WHERE week_id = :week_id LIMIT 1";
        $newStmt = $db->prepare($newSql);
        $newStmt->bindValue(':week_id', $week_id);
        $newStmt->execute();
        $newRow = $newStmt->fetch(PDO::FETCH_ASSOC);
        if ($newRow) {
            $newRow['links'] = json_decode($newRow['links'], true) ?? [];
            sendResponse(['success' => true, 'data' => $newRow], 201);
        } else {
            sendError('Created but unable to fetch the new week', 500);
        }
    } else {
        sendError('Failed to create week', 500);
    }
}


/**
 * Function: Update an existing week
 * Method: PUT
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: The week identifier (to identify which week to update)
 *   - title: Updated week title (optional)
 *   - start_date: Updated start date (optional)
 *   - description: Updated description (optional)
 *   - links: Updated array of links (optional)
 */
function updateWeek($db, $data) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (!isset($data['week_id']) || trim($data['week_id']) === '') {
        sendError('week_id is required for update', 400);
    }
    $week_id = sanitizeInput($data['week_id']);

    // TODO: Check if week exists
    // Prepare and execute a SELECT query to find the week
    // If not found, return error response with 404 status
    $checkSql = "SELECT * FROM weeks WHERE week_id = :week_id LIMIT 1";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':week_id', $week_id);
    $checkStmt->execute();
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        sendError('Week not found', 404);
    }

    // TODO: Build UPDATE query dynamically based on provided fields
    // Initialize an array to hold SET clauses
    // Initialize an array to hold values for binding
    $setClauses = [];
    $values = [];

    // TODO: Check which fields are provided and add to SET clauses
    // If title is provided, add "title = ?"
    if (isset($data['title']) && trim($data['title']) !== '') {
        $setClauses[] = "title = :title";
        $values[':title'] = sanitizeInput($data['title']);
    }

    // If start_date is provided, validate format and add "start_date = ?"
    if (isset($data['start_date']) && trim($data['start_date']) !== '') {
        $start_date = sanitizeInput($data['start_date']);
        if (!validateDate($start_date)) {
            sendError('start_date must be in YYYY-MM-DD format', 400);
        }
        $setClauses[] = "start_date = :start_date";
        $values[':start_date'] = $start_date;
    }

    // If description is provided, add "description = ?"
    if (isset($data['description'])) {
        $setClauses[] = "description = :description";
        $values[':description'] = sanitizeInput($data['description']);
    }

    // If links is provided, encode to JSON and add "links = ?"
    if (isset($data['links'])) {
        $links = is_array($data['links']) ? array_values($data['links']) : [];
        $setClauses[] = "links = :links";
        $values[':links'] = json_encode($links);
    }

    // TODO: If no fields to update, return error response with 400 status
    if (empty($setClauses)) {
        sendError('No fields provided to update', 400);
    }

    // TODO: Add updated_at timestamp to SET clauses
    // Add "updated_at = CURRENT_TIMESTAMP"
    $setClauses[] = "updated_at = CURRENT_TIMESTAMP";

    // TODO: Build the complete UPDATE query
    // UPDATE weeks SET [clauses] WHERE week_id = ?
    $sql = "UPDATE weeks SET " . implode(', ', $setClauses) . " WHERE week_id = :week_id";

    // TODO: Prepare the query
    $stmt = $db->prepare($sql);

    // TODO: Bind parameters dynamically
    // Bind values array and then bind week_id at the end
    foreach ($values as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':week_id', $week_id);

    // TODO: Execute the query
    $executed = $stmt->execute();

    // TODO: Check if update was successful
    // If yes, return success response with updated week data
    // If no, return error response with 500 status
    if ($executed) {
        // Return the updated row
        $newSql = "SELECT week_id, title, start_date, description, links, created_at, updated_at FROM weeks WHERE week_id = :week_id LIMIT 1";
        $newStmt = $db->prepare($newSql);
        $newStmt->bindValue(':week_id', $week_id);
        $newStmt->execute();
        $newRow = $newStmt->fetch(PDO::FETCH_ASSOC);
        if ($newRow) {
            $newRow['links'] = json_decode($newRow['links'], true) ?? [];
            sendResponse(['success' => true, 'data' => $newRow]);
        } else {
            sendError('Update succeeded but failed to fetch updated week', 500);
        }
    } else {
        sendError('Failed to update week', 500);
    }
}


/**
 * Function: Delete a week
 * Method: DELETE
 * Resource: weeks
 * 
 * Query Parameters or JSON Body:
 *   - week_id: The week identifier
 */
function deleteWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (empty($weekId)) {
        sendError('week_id is required', 400);
    }

    // TODO: Check if week exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $checkSql = "SELECT * FROM weeks WHERE week_id = :week_id LIMIT 1";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':week_id', $weekId);
    $checkStmt->execute();
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        sendError('Week not found', 404);
    }

    try {
        $db->beginTransaction();

        // TODO: Delete associated comments first (to maintain referential integrity)
        // Prepare DELETE query for comments table
        // DELETE FROM comments WHERE week_id = ?
        $delCommentsSql = "DELETE FROM comments WHERE week_id = :week_id";
        $delCommentsStmt = $db->prepare($delCommentsSql);
        $delCommentsStmt->bindValue(':week_id', $weekId);
        $delCommentsStmt->execute();

        // TODO: Prepare DELETE query for week
        // DELETE FROM weeks WHERE week_id = ?
        $delWeekSql = "DELETE FROM weeks WHERE week_id = :week_id";
        $delWeekStmt = $db->prepare($delWeekSql);

        // TODO: Bind the week_id parameter
        $delWeekStmt->bindValue(':week_id', $weekId);

        // TODO: Execute the query
        $delWeekStmt->execute();

        $db->commit();

        // TODO: Check if delete was successful
        // If yes, return success response with message indicating week and comments deleted
        sendResponse(['success' => true, 'message' => 'Week and associated comments deleted']);
    } catch (PDOException $e) {
        $db->rollBack();
        // Do not expose $e->getMessage() in production
        sendError('Failed to delete week', 500);
    }
}


// ============================================================================
// COMMENTS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all comments for a specific week
 * Method: GET
 * Resource: comments
 * 
 * Query Parameters:
 *   - week_id: The week identifier to get comments for
 */
function getCommentsByWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (empty($weekId)) {
        sendError('week_id is required', 400);
    }

    // TODO: Prepare SQL query to select comments for the week
    // SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = ? ORDER BY created_at ASC
    $sql = "SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = :week_id ORDER BY created_at ASC";
    $stmt = $db->prepare($sql);

    // TODO: Bind the week_id parameter
    $stmt->bindValue(':week_id', $weekId);

    // TODO: Execute the query
    $stmt->execute();

    // TODO: Fetch all results as an associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Return JSON response with success status and data
    // Even if no comments exist, return an empty array
    sendResponse(['success' => true, 'data' => $results]);
}


/**
 * Function: Create a new comment
 * Method: POST
 * Resource: comments
 * 
 * Required JSON Body:
 *   - week_id: The week identifier this comment belongs to
 *   - author: Comment author name
 *   - text: Comment text content
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, author, and text are provided
    // If any field is missing, return error response with 400 status
    $required = ['week_id', 'author', 'text'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            sendError("{$field} is required", 400);
        }
    }

    // TODO: Sanitize input data
    // Trim whitespace from all fields
    $week_id = sanitizeInput($data['week_id']);
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);

    // TODO: Validate that text is not empty after trimming
    // If empty, return error response with 400 status
    if ($text === '') {
        sendError('text cannot be empty', 400);
    }

    // TODO: Check if the week exists
    // Prepare and execute a SELECT query on weeks table
    // If week not found, return error response with 404 status
    $checkSql = "SELECT COUNT(*) FROM weeks WHERE week_id = :week_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':week_id', $week_id);
    $checkStmt->execute();
    $count = (int)$checkStmt->fetchColumn();
    if ($count === 0) {
        sendError('Associated week not found', 404);
    }

    // TODO: Prepare INSERT query
    // INSERT INTO comments (week_id, author, text) VALUES (?, ?, ?)
    $insertSql = "INSERT INTO comments (week_id, author, text, created_at) VALUES (:week_id, :author, :text, CURRENT_TIMESTAMP)";
    $insertStmt = $db->prepare($insertSql);

    // TODO: Bind parameters
    $insertStmt->bindValue(':week_id', $week_id);
    $insertStmt->bindValue(':author', $author);
    $insertStmt->bindValue(':text', $text);

    // TODO: Execute the query
    $executed = $insertStmt->execute();

    // TODO: Check if insert was successful
    // If yes, get the last insert ID and return success response with 201 status
    // Include the new comment data in the response
    // If no, return error response with 500 status
    if ($executed) {
        $commentId = (int)$db->lastInsertId();
        $selectSql = "SELECT id, week_id, author, text, created_at FROM comments WHERE id = :id LIMIT 1";
        $selectStmt = $db->prepare($selectSql);
        $selectStmt->bindValue(':id', $commentId, PDO::PARAM_INT);
        $selectStmt->execute();
        $comment = $selectStmt->fetch(PDO::FETCH_ASSOC);
        sendResponse(['success' => true, 'data' => $comment], 201);
    } else {
        sendError('Failed to create comment', 500);
    }
}


/**
 * Function: Delete a comment
 * Method: DELETE
 * Resource: comments
 * 
 * Query Parameters or JSON Body:
 *   - id: The comment ID to delete
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that id is provided
    // If not, return error response with 400 status
    if (empty($commentId)) {
        sendError('id is required', 400);
    }

    // TODO: Check if comment exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $checkSql = "SELECT * FROM comments WHERE id = :id LIMIT 1";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':id', $commentId, PDO::PARAM_INT);
    $checkStmt->execute();
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        sendError('Comment not found', 404);
    }

    // TODO: Prepare DELETE query
    // DELETE FROM comments WHERE id = ?
    $delSql = "DELETE FROM comments WHERE id = :id";
    $delStmt = $db->prepare($delSql);

    // TODO: Bind the id parameter
    $delStmt->bindValue(':id', $commentId, PDO::PARAM_INT);

    // TODO: Execute the query
    $executed = $delStmt->execute();

    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($executed) {
        sendResponse(['success' => true, 'message' => 'Comment deleted']);
    } else {
        sendError('Failed to delete comment', 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Determine the resource type from query parameters
    // Get 'resource' parameter (?resource=weeks or ?resource=comments)
    // If not provided, default to 'weeks'
    $resource = isset($_GET['resource']) ? trim($_GET['resource']) : 'weeks';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Route based on resource type and HTTP method

    // ========== WEEKS ROUTES ==========
    if ($resource === 'weeks') {
        
        if ($method === 'GET') {
            // TODO: Check if week_id is provided in query parameters
            // If yes, call getWeekById()
            // If no, call getAllWeeks() to get all weeks (with optional search/sort)
            if (isset($_GET['week_id']) && trim($_GET['week_id']) !== '') {
                getWeekById($db, trim($_GET['week_id']));
            } else {
                getAllWeeks($db);
            }
            
        } elseif ($method === 'POST') {
            // TODO: Call createWeek() with the decoded request body
            if (!is_array($requestData)) {
                sendError('Invalid JSON body', 400);
            }
            createWeek($db, $requestData);
            
        } elseif ($method === 'PUT') {
            // TODO: Call updateWeek() with the decoded request body
            if (!is_array($requestData)) {
                sendError('Invalid JSON body', 400);
            }
            updateWeek($db, $requestData);
            
        } elseif ($method === 'DELETE') {
            // TODO: Get week_id from query parameter or request body
            // Call deleteWeek()
            $weekId = null;
            if (isset($_GET['week_id']) && trim($_GET['week_id']) !== '') {
                $weekId = trim($_GET['week_id']);
            } elseif (is_array($requestData) && isset($requestData['week_id'])) {
                $weekId = $requestData['week_id'];
            }
            deleteWeek($db, $weekId);
            
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
            sendError('Method not allowed for weeks', 405);
        }
    }
    
    // ========== COMMENTS ROUTES ==========
    elseif ($resource === 'comments') {
        
        if ($method === 'GET') {
            // TODO: Get week_id from query parameters
            // Call getCommentsByWeek()
            if (!isset($_GET['week_id']) || trim($_GET['week_id']) === '') {
                sendError('week_id query parameter is required', 400);
            }
            getCommentsByWeek($db, trim($_GET['week_id']));
            
        } elseif ($method === 'POST') {
            // TODO: Call createComment() with the decoded request body
            if (!is_array($requestData)) {
                sendError('Invalid JSON body', 400);
            }
            createComment($db, $requestData);
            
        } elseif ($method === 'DELETE') {
            // TODO: Get comment id from query parameter or request body
            // Call deleteComment()
            $commentId = null;
            if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                $commentId = (int)$_GET['id'];
            } elseif (is_array($requestData) && isset($requestData['id'])) {
                $commentId = (int)$requestData['id'];
            }
            deleteComment($db, $commentId);
            
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
            sendError('Method not allowed for comments', 405);
        }
    }
    
    // ========== INVALID RESOURCE ==========
    else {
        // TODO: Return error for invalid resource
        // Set HTTP status to 400 (Bad Request)
        // Return JSON error message: "Invalid resource. Use 'weeks' or 'comments'"
        sendError("Invalid resource. Use 'weeks' or 'comments'", 400);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional, for debugging)
    // error_log($e->getMessage());
    
    // TODO: Return generic error response with 500 status
    // Do NOT expose database error details to the client
    // Return message: "Database error occurred"
    error_log('Database error: ' . $e->getMessage());
    sendError('Database error occurred', 500);
    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Log the error message (optional)
    // Return error response with 500 status
    error_log('General error: ' . $e->getMessage());
    sendError('An internal error occurred', 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send (will be JSON encoded)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    // Use http_response_code($statusCode)
    http_response_code($statusCode);

    // TODO: Echo JSON encoded data
    // Use json_encode($data)
    echo json_encode($data, JSON_UNESCAPED_UNICODE);

    // TODO: Exit to prevent further execution
    exit;
}


/**
 * Helper function to send error response
 * 
 * @param string $message - Error message
 * @param int $statusCode - HTTP status code
 */
function sendError($message, $statusCode = 400) {
    // TODO: Create error response array
    // Structure: ['success' => false, 'error' => $message]
    $payload = ['success' => false, 'error' => $message];

    // TODO: Call sendResponse() with the error array and status code
    sendResponse($payload, $statusCode);
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat() to validate
    // Format: 'Y-m-d'
    // Check that the created date matches the input string
    // Return true if valid, false otherwise
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    $data = trim($data ?? '');

    // TODO: Strip HTML tags using strip_tags()
    $data = strip_tags($data);

    // TODO: Convert special characters using htmlspecialchars()
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    // TODO: Return sanitized data
    return $data;
}


/**
 * Helper function to validate allowed sort fields
 * 
 * @param string $field - Field name to validate
 * @param array $allowedFields - Array of allowed field names
 * @return bool - True if valid, false otherwise
 */
function isValidSortField($field, $allowedFields) {
    // TODO: Check if $field exists in $allowedFields array
    // Use in_array()
    // Return true if valid, false otherwise
    return in_array($field, $allowedFields, true);
}

?>