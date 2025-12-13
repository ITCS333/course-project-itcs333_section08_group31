<?php
/**
 * Authentication Handler for Login Form
 * 
 * This PHP script handles user authentication via POST requests from the Fetch API.
 * It validates credentials against a MySQL database using PDO,
 * creates sessions, and returns JSON responses.
 */

// --- Session Management ---
// TODO: Start a PHP session using session_start()
// This must be called before any output is sent to the browser
// Sessions allow us to store user data across multiple pages
session_start();

// --- Set Response Headers ---
// TODO: Set the Content-Type header to 'application/json'
// This tells the browser that we're sending JSON data back
header("Content-Type: application/json");

// TODO: (Optional) Set CORS headers if your frontend and backend are on different domains
// You'll need headers for Access-Control-Allow-Origin, Methods, and Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handles preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Preflight OK']);
    exit;
}

// --- Check Request Method ---
// TODO: Verify that the request method is POST
// Use the $_SERVER superglobal to check the REQUEST_METHOD
// If the request is not POST, return an error response and exit
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method. POST required.']);
    exit;
}

// --- Get POST Data ---
// TODO: Retrieve the raw POST data
// The Fetch API sends JSON data in the request body
// Use file_get_contents with 'php://input' to read the raw request body
$rawInput = file_get_contents('php://input');

// TODO: Decode the JSON data into a PHP associative array
// Use json_decode with the second parameter set to true
$Data = json_decode($rawInput, true);

// TODO: Extract the email and password from the decoded data
// Check if both 'email' and 'password' keys exist in the array
// If either is missing, return an error response and exit
if (!is_array($Data) || !isset($Data['email']) || !isset($Data['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

// TODO: Store the email and password in variables
// Trim any whitespace from the email
$email = trim($Data['email']);
$password = $Data['password'];

// --- Server-Side Validation (Optional but Recommended) ---
// TODO: Validate the email format on the server side
// Use the appropriate filter function for email validation
// If invalid, return an error response and exit
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}

// TODO: Validate the password length (minimum 8 characters)
// If invalid, return an error response and exit
if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
    exit;
}

// --- Database Connection ---
// TODO: Get the database connection using the provided function
// Assume getDBConnection() returns a PDO instance with error mode set to exception
// The function is defined elsewhere (e.g., in a config file or db.php)
require_once __DIR__ . '/db.php';
$pdo = getDBConnection();

// TODO: Wrap database operations in a try-catch block to handle PDO exceptions
// This ensures you can return a proper JSON error response if something goes wrong
try {

    // --- Prepare SQL Query ---
    // TODO: Write a SQL SELECT query to find the user by email
    $sql = "SELECT id, name, email, password FROM users WHERE email = :email LIMIT 1";

    // --- Prepare the Statement ---
    $stmt = $pdo->prepare($sql);

    // --- Execute the Query ---
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    // --- Fetch User Data ---
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- Verify User Exists and Password Matches ---
    if ($user && isset($user['password'])) {

        $passwordMatches = password_verify($password, $user['password']);

        // --- Handle Successful Authentication ---
        if ($passwordMatches) {

            // TODO: Store user information in session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['logged_in'] = true;

            // TODO: Prepare a success response array
            $response = [
                'success' => true,
                'message' => 'Login Successful',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ]
            ];

            echo json_encode($response);
            exit;
        }
    }

    // --- Handle Failed Authentication ---
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    exit;

}
// TODO: Catch PDO exceptions in the catch block
catch (PDOException $e) {
    error_log("Database error during login:" . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An internal server error occurred. Please try again later.']);
    exit;
}
// TODO: Return a generic error message to the client
catch (Exception $e) {
    error_log("General error during login:" . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An internal server error occurred. Please try again later.']);
    exit;
}

// --- End of Script ---
?>