<?php
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:;");

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

function validate_input($data, $max_length = 255) {
    if (empty($data)) return false;
    $data = trim($data);
    $data = stripslashes($data);
    if (strlen($data) > $max_length) return false;
    return preg_match('/^[a-zA-Z0-9_]+$/', $data) ? $data : false;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // DATABASE CONFIGURATION FOR RAILWAY
    $host = 'junction.proxy.rlwy.net';
    $db   = 'railway';
    $user = 'root';
    $pass = 'KKnlRsdVlmoSIGLSsKzsFKvCgPmxdYrx'; 
    $port = '39103';     
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";

    $username = validate_input($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? ''); 
    $password = trim($_POST['password'] ?? '');
    $role     = 'user'; 

    if (!$username || empty($password)) {
        header("Location: register.html?error=invalid_input");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: register.html?error=invalid_email");
        exit;
    }

    if (strlen($password) < 6) {
        header("Location: register.html?error=weak_password");
        exit;
    }

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_TIMEOUT            => 30, 
        ]);

        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $checkStmt->execute([$username, $email]);
        if ($checkStmt->fetch()) {
            header("Location: register.html?error=exists");
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword, $role]);

        header("Location: login.html?success=registered");
        exit;

    } catch (PDOException $e) {
        header("Location: register.html?error=db_fault");
        exit;
    }
} else {
    header("Location: register.html");
    exit;
}
?>
