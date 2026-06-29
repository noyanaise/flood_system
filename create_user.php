<?php
$host = $_ENV['MYSQLHOST'] ?? 'mysql.railway.internal';
$db   = $_ENV['MYSQLDATABASE'] ?? 'railway';
$user = $_ENV['MYSQLUSER'] ?? 'root';
$pass = $_ENV['MYSQLPASSWORD'] ?? 'KKnlRsdVlmoSIGLSsKzsFKvCgPmxdYrx'; 
$port = $_ENV['MYSQLPORT'] ?? '3306'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;port=$port", $user, $pass);
    
    // Create an account with username "admin" and password "password123"
    $username = 'admin';
    $password = 'password123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'admin';

    // Delete existing test user if there is one
    $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
    $stmt->execute([$username]);

    // Insert clean user account profile
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
    $stmt->execute([$username, $hash, $role]);

    echo "Account created successfully! Username: admin | Password: password123";
} catch (PDOException $e) {
    echo "Error inserting user: " . $e->getMessage();
}
?>
