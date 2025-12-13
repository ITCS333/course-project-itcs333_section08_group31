<?php
/**
 * Discussion Board API
 * 
 * This is a RESTful API that handles all CRUD operations for the discussion board.
 * It manages both discussion topics and their replies.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: topics
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - topic_id (VARCHAR(50), UNIQUE) - The topic's unique identifier (e.g., "topic_1234567890")
 *   - subject (VARCHAR(255)) - The topic subject/title
 *   - message (TEXT) - The main topic message
 *   - author (VARCHAR(100)) - The author's name
 *   - created_at (TIMESTAMP) - When the topic was created
 * 
 * Table: replies
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - reply_id (VARCHAR(50), UNIQUE) - The reply's unique identifier (e.g., "reply_1234567890")
 *   - topic_id (VARCHAR(50)) - Foreign key to topics.topic_id
 *   - text (TEXT) - The reply message
 *   - author (VARCHAR(100)) - The reply author's name
 *   - created_at (TIMESTAMP) - When the reply was created
 * 
 * API Endpoints:
 * 
 * Topics:
 *   GET    /api/discussion.php?resource=topics              - Get all topics (with optional search)
 *   GET    /api/discussion.php?resource=topics&id={id}      - Get single topic
 *   POST   /api/discussion.php?resource=topics              - Create new topic
 *   PUT    /api/discussion.php?resource=topics              - Update a topic
 *   DELETE /api/discussion.php?resource=topics&id={id}      - Delete a topic
 * 
 * Replies:
 *   GET    /api/discussion.php?resource=replies&topic_id={id} - Get all replies for a topic
 *   POST   /api/discussion.php?resource=replies              - Create new reply
 *   DELETE /api/discussion.php?resource=replies&id={id}      - Delete a reply
 * 
 * Response Format: JSON
 */

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}


// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
require_once "db.php";


// TODO: Get the PDO database connection
// $db = $database->getConnection();
$db = getDBConnection();


// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER["REQUEST_METHOD"];


// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$raw = file_get_contents("php://input");
$data = json_decode($raw, true) ?? [];


// TODO: Parse query parameters for filtering and searching
$resource = $_GET["resource"] ?? null;
$idParam = $_GET["id"] ?? null;
$topicIdParam = $_GET["topic_id"] ?? null;


// ============================================================================
// TOPICS FUNCTIONS
// ============================================================================

function getAllTopics($db) {
    // TODO: Initialize base SQL query
    // Select topic_id, subject, message, author, and created_at (formatted as date)
    $sql = "SELECT topic_id, subject, message, author, created_at FROM topics";
    
    // TODO: Initialize an array to hold bound parameters
    $params = [];
    
    // TODO: Check if search parameter exists in $_GET
    // If yes, add WHERE clause using LIKE for subject, message, OR author
    // Add the search term to the params array
    if (!empty($_GET["search"])) {
        $sql .= " WHERE subject LIKE :s OR message LIKE :s OR author LIKE :s";
        $params[":s"] = "%" . $_GET["search"] . "%";
    }
    
    // TODO: Add ORDER BY clause
    // Check for sort and order parameters in $_GET
    // Validate the sort field (only allow: subject, author, created_at)
    // Validate order (only allow: asc, desc)
    // Default to ordering by created_at DESC
    $allowed = ["subject", "author", "created_at"];
    $sort = $_GET["sort"] ?? "created_at";
    if (!in_array($sort, $allowed)) $sort = "created_at";

    $order = strtolower($_GET["order"] ?? "desc");
    if (!in_array($order, ["asc","desc"])) $order = "desc";

    $sql .= " ORDER BY $sort $order";
    
    // TODO: Prepare the SQL statement
    $stmt = $db->prepare($sql);
    
    // TODO: Bind parameters if search was used
    // Loop through $params array and bind each parameter
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch all results as an associative array
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Return JSON response with success status and data
    // Call sendResponse() helper function or echo json_encode directly
    sendResponse(["success" => true, "data" => $rows]);
}



function getTopicById($db, $topicId) {
    // TODO: Validate that topicId is provided
    // If empty, return error with 400 status
    if (empty($topicId)) {
        sendResponse(["success" => false, "message" => "Topic ID required"], 400);
    }
    
    // TODO: Prepare SQL query to select topic by topic_id
    // Select topic_id, subject, message, author, and created_at
    $stmt = $db->prepare("SELECT topic_id, subject, message, author, created_at 
                          FROM topics WHERE topic_id = :id");
    
    // TODO: Prepare and bind the topic_id parameter
    $stmt->bindValue(":id", $topicId);
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch the result
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // TODO: Check if topic exists
    // If topic found, return success response with topic data
    // If not found, return error with 404 status
    if ($row) {
        sendResponse(["success" => true, "data" => $row]);
    } else {
        sendResponse(["success" => false, "message" => "Topic not found"], 404);
    }
}



function createTopic($db, $data) {
    // TODO: Validate required fields
    // Check if topic_id, subject, message, and author are provided
    // If any required field is missing, return error with 400 status
    if (empty($data["topic_id"]) || empty($data["subject"]) || empty($data["message"]) || empty($data["author"])) {
        sendResponse(["success" => false, "message" => "Missing required fields"], 400);
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from all string fields
    // Use the sanitizeInput() helper function
    $topic_id = sanitizeInput($data["topic_id"]);
    $subject  = sanitizeInput($data["subject"]);
    $message  = sanitizeInput($data["message"]);
    $author   = sanitizeInput($data["author"]);
    
    // TODO: Check if topic_id already exists
    // Prepare and execute a SELECT query to check for duplicate
    // If duplicate found, return error with 409 status (Conflict)
    $chk = $db->prepare("SELECT topic_id FROM topics WHERE topic_id = :id");
    $chk->bindValue(":id", $topic_id);
    $chk->execute();
    if ($chk->fetch()) {
        sendResponse(["success" => false, "message" => "Duplicate topic_id"], 409);
    }
    
    // TODO: Prepare INSERT query
    // Insert topic_id, subject, message, and author
    // The created_at field should auto-populate with CURRENT_TIMESTAMP
    $stmt = $db->prepare("INSERT INTO topics (topic_id, subject, message, author)
                          VALUES (:id, :s, :m, :a)");
    
    // TODO: Prepare the statement and bind parameters
    // Bind all the sanitized values
    $stmt->bindValue(":id", $topic_id);
    $stmt->bindValue(":s", $subject);
    $stmt->bindValue(":m", $message);
    $stmt->bindValue(":a", $author);
    
    // TODO: Execute the query
    $ok = $stmt->execute();
    
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created)
    // Include the topic_id in the response
    // If no, return error with 500 status
    if ($ok) {
        sendResponse(["success" => true, "message" => "Topic created", "topic_id" => $topic_id], 201);
    } else {
        sendResponse(["success" => false, "message" => "Insert failed"], 500);
    }
}



function updateTopic($db, $data) {
    // TODO: Validate that topic_id is provided
    // If not provided, return error with 400 status
    if (empty($data["topic_id"])) {
        sendResponse(["success" => false, "message" => "topic_id required"], 400);
    }
    $topic_id = $data["topic_id"];
    
    // TODO: Check if topic exists
    // Prepare and execute a SELECT query
    // If not found, return error with 404 status
    $chk = $db->prepare("SELECT topic_id FROM topics WHERE topic_id = :id");
    $chk->bindValue(":id", $topic_id);
    $chk->execute();
    if (!$chk->fetch()) {
        sendResponse(["success" => false, "message" => "Topic not found"], 404);
    }
    
    // TODO: Build UPDATE query dynamically based on provided fields
    // Only update fields that are provided in the request
    $updates = [];
    $params = [":id" => $topic_id];
    
    if (!empty($data["subject"])) {
        $updates[] = "subject = :s";
        $params[":s"] = sanitizeInput($data["subject"]);
    }
    if (!empty($data["message"])) {
        $updates[] = "message = :m";
        $params[":m"] = sanitizeInput($data["message"]);
    }
    
    // TODO: Check if there are any fields to update
    // If $updates array is empty, return error
    if (empty($updates)) {
        sendResponse(["success" => false, "message" => "No fields to update"], 400);
    }
    
    // TODO: Complete the UPDATE query
    $sql = "UPDATE topics SET " . implode(", ", $updates) . " WHERE topic_id = :id";
    $stmt = $db->prepare($sql);
    
    // TODO: Prepare statement and bind parameters
    // Bind all parameters from the $params array
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Check if update was successful
    // If yes, return success response
    // If no rows affected, return appropriate message
    // If error, return error with 500 status
    sendResponse(["success" => true, "message" => "Topic updated"]);
}



function deleteTopic($db, $topicId) {
    // TODO: Validate that topicId is provided
    // If not, return error with 400 status
    if (empty($topicId)) {
        sendResponse(["success" => false, "message" => "Topic ID required"], 400);
    }
    
    // TODO: Check if topic exists
    // Prepare and execute a SELECT query
    // If not found, return error with 404 status
    $chk = $db->prepare("SELECT topic_id FROM topics WHERE topic_id = :id");
    $chk->bindValue(":id", $topicId);
    $chk->execute();
    if (!$chk->fetch()) {
        sendResponse(["success" => false, "message" => "Topic not found"], 404);
    }
    
    // TODO: Delete associated replies first (foreign key constraint)
    // Prepare DELETE query for replies table
    $delR = $db->prepare("DELETE FROM replies WHERE topic_id = :id");
    $delR->bindValue(":id", $topicId);
    $delR->execute();
    
    // TODO: Prepare DELETE query for the topic
    $stmt = $db->prepare("DELETE FROM topics WHERE topic_id = :id");
    
    // TODO: Prepare, bind, and execute
    $stmt->bindValue(":id", $topicId);
    $ok = $stmt->execute();
    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error with 500 status
    if ($ok) {
        sendResponse(["success" => true, "message" => "Topic deleted"]);
    } else {
        sendResponse(["success" => false, "message" => "Delete failed"], 500);
    }
}



// ============================================================================
// REPLIES FUNCTIONS
// ============================================================================

function getRepliesByTopicId($db, $topicId) {
    // TODO: Validate that topicId is provided
    // If not provided, return error with 400 status
    if (empty($topicId)) {
        sendResponse(["success" => false, "message" => "topic_id required"], 400);
    }
    
    // TODO: Prepare SQL query to select all replies for the topic
    // Select reply_id, topic_id, text, author, and created_at (formatted as date)
    // Order by created_at ASC (oldest first)
    $stmt = $db->prepare("SELECT reply_id, topic_id, text, author, created_at
                          FROM replies WHERE topic_id = :id
                          ORDER BY created_at ASC");
    
    // TODO: Prepare and bind the topic_id parameter
    $stmt->bindValue(":id", $topicId);
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch all results as an associative array
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Return JSON response
    // Even if no replies found, return empty array (not an error)
    sendResponse(["success" => true, "data" => $rows]);
}



function createReply($db, $data) {
    // TODO: Validate required fields
    // Check if reply_id, topic_id, text, and author are provided
    // If any field is missing, return error with 400 status
    if (empty($data["reply_id"]) || empty($data["topic_id"]) || empty($data["text"]) || empty($data["author"])) {
        sendResponse(["success" => false, "message" => "Missing required fields"], 400);
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    $reply_id = sanitizeInput($data["reply_id"]);
    $topic_id = sanitizeInput($data["topic_id"]);
    $text     = sanitizeInput($data["text"]);
    $author   = sanitizeInput($data["author"]);
    
    // TODO: Verify that the parent topic exists
    // Prepare and execute SELECT query on topics table
    // If topic doesn't exist, return error with 404 status (can't reply to non-existent topic)
    $chk = $db->prepare("SELECT topic_id FROM topics WHERE topic_id = :id");
    $chk->bindValue(":id", $topic_id);
    $chk->execute();
    if (!$chk->fetch()) {
        sendResponse(["success" => false, "message" => "Parent topic not found"], 404);
    }
    
    // TODO: Check if reply_id already exists
    // Prepare and execute SELECT query to check for duplicate
    // If duplicate found, return error with 409 status
    $chk2 = $db->prepare("SELECT reply_id FROM replies WHERE reply_id = :id");
    $chk2->bindValue(":id", $reply_id);
    $chk2->execute();
    if ($chk2->fetch()) {
        sendResponse(["success" => false, "message" => "Duplicate reply_id"], 409);
    }
    
    // TODO: Prepare INSERT query
    // Insert reply_id, topic_id, text, and author
    $stmt = $db->prepare("INSERT INTO replies (reply_id, topic_id, text, author)
                          VALUES (:rid, :tid, :txt, :auth)");
    
    // TODO: Prepare statement and bind parameters
    $stmt->bindValue(":rid", $reply_id);
    $stmt->bindValue(":tid", $topic_id);
    $stmt->bindValue(":txt", $text);
    $stmt->bindValue(":auth", $author);
    
    // TODO: Execute the query
    $ok = $stmt->execute();
    
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status
    // Include the reply_id in the response
    // If no, return error with 500 status
    if ($ok) {
        sendResponse(["success" => true, "message" => "Reply created", "reply_id" => $reply_id], 201);
    } else {
        sendResponse(["success" => false, "message" => "Insert failed"], 500);
    }
}



function deleteReply($db, $replyId) {
    // TODO: Validate that replyId is provided
    // If not, return error with 400 status
    if (empty($replyId)) {
        sendResponse(["success" => false, "message" => "reply_id required"], 400);
    }
    
    // TODO: Check if reply exists
    // Prepare and execute SELECT query
    // If not found, return error with 404 status
    $chk = $db->prepare("SELECT reply_id FROM replies WHERE reply_id = :id");
    $chk->bindValue(":id", $replyId);
    $chk->execute();
    if (!$chk->fetch()) {
        sendResponse(["success" => false, "message" => "Reply not found"], 404);
    }
    
    // TODO: Prepare DELETE query
    $stmt = $db->prepare("DELETE FROM replies WHERE reply_id = :id");
    
    // TODO: Prepare, bind, and execute
    $stmt->bindValue(":id", $replyId);
    $ok = $stmt->execute();
    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error with 500 status
    if ($ok) {
        sendResponse(["success" => true, "message" => "Reply deleted"]);
    } else {
        sendResponse(["success" => false, "message" => "Delete failed"], 500);
    }
}



// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on resource and HTTP method
    if (!isValidResource($resource)) {
        sendResponse(["success" => false, "message" => "Invalid resource"], 400);
    }

    // TODO: For GET requests, check for 'id' parameter in $_GET
    // TODO: For DELETE requests, get id from query parameter or request body
    // TODO: For unsupported methods, return 405 Method Not Allowed
    // TODO: For invalid resources, return 400 Bad Request

    switch ($resource) {
        case "topics":
            if ($method === "GET") {
                if ($idParam) { getTopicById($db, $idParam); }
                else { getAllTopics($db); }
            }
            elseif ($method === "POST") { createTopic($db, $data); }
            elseif ($method === "PUT") { updateTopic($db, $data); }
            elseif ($method === "DELETE") { deleteTopic($db, $idParam); }
            else { sendResponse(["success"=>false,"message"=>"Method not allowed"],405); }
            break;

        case "replies":
            if ($method === "GET") { getRepliesByTopicId($db, $topicIdParam); }
            elseif ($method === "POST") { createReply($db, $data); }
            elseif ($method === "DELETE") { deleteReply($db, $idParam); }
            else { sendResponse(["success"=>false,"message"=>"Method not allowed"],405); }
            break;
    }

} catch (PDOException $e) {
    // TODO: Handle database errors
    // DO NOT expose the actual error message to the client (security risk)
    // Log the error for debugging (optional)
    // Return generic error response with 500 status
    sendResponse(["success" => false, "message" => "Database error"], 500);

} catch (Exception $e) {
    // TODO: Handle general errors
    // Log the error for debugging
    // Return error response with 500 status
    sendResponse(["success" => false, "message" => "Server error"], 500);
}



// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);
    
    // TODO: Echo JSON encoded data
    // Make sure to handle JSON encoding errors
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    
    // TODO: Exit to prevent further execution
    exit;
}



function sanitizeInput($data) {
    // TODO: Check if data is a string
    // If not, return as is or convert to string
    if (!is_string($data)) return $data;
    
    // TODO: Trim whitespace from both ends
    $data = trim($data);
    
    // TODO: Remove HTML and PHP tags
    $data = strip_tags($data);
    
    // TODO: Convert special characters to HTML entities (prevents XSS)
    $data = htmlspecialchars($data, ENT_QUOTES, "UTF-8");
    
    // TODO: Return sanitized data
    return $data;
}



function isValidResource($resource) {
    // TODO: Define allowed resources
    $allowed = ["topics", "replies"];
    
    // TODO: Check if resource is in the allowed list
    return in_array($resource, $allowed);
}

?>