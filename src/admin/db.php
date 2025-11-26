<?php
/**
 * db.php
 * Creates and returns a PDO database connection.
 * Used by ALL backend APIs (login, student management, course resources, etc.)
 */

function getDBConnection() {
    // ---- UPDATE THESE TO MATCH YOUR DATABASE ----
    $host = "localhost";   
    $dbname = "your_database_name";  
    $username = "root";       // or your MySQL username
    $password = "";           // XAMPP default is empty
    // ----------------------------------------------

    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,       // Throw exceptions on error
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // Return associative arrays
            PDO::ATTR_EMULATE_PREPARES => false,               // Use native prepared statements
        ]);

        return $pdo;

    } catch (PDOException $e) {
        // DO NOT echo error details (security risk)
        error_log("Database Connection Error: " . $e->getMessage());
        die("Database connection failed.");
    }
}
?>