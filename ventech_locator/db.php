<?php
// Set up the database connection using PDO
$host = '127.0.0.1'; // Change to your database host (localhost or IP address)
$db = 'ventech_db'; // Your database name
$user = 'root'; // Your database username
$pass = ''; // Your database password (empty for default local setup)

try {
    // Create a PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If the connection fails, show an error message
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}
?>
