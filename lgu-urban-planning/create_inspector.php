<?php
require_once __DIR__ . '/core/Database.php';

$db = Database::getInstance();

// Mga detalye para sa Inspector account
$username = "inspector";
$email = "inspector@lgu.gov.ph";
$password = "inspector123"; 
$first_name = "Inspector";
$last_name = "Juan";
$role = "inspector";
$is_verified = 1;
$is_active = 1;

// I-hash ang password (gamit ang BCRYPT na tugma sa $2y$10$...)
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// SQL query base sa structure na binigay mo
$sql = "INSERT INTO users (
            username, 
            email, 
            password_hash, 
            first_name, 
            last_name, 
            role, 
            is_verified, 
            is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

try {
    $db->query($sql, [
        $username, 
        $email, 
        $password_hash, 
        $first_name, 
        $last_name, 
        $role, 
        $is_verified, 
        $is_active
    ]);
    echo "<h3>Inspector account created successfully!</h3>";
    echo "Username: <b>inspector</b><br>";
    echo "Password: <b>inspector123</b><br>";
    echo "<p style='color:red;'>Please delete this file after running it.</p>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>