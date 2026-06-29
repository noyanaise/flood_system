<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $host = $_ENV['MYSQLHOST'] ?? 'mysql.railway.internal';
    $db   = $_ENV['MYSQLDATABASE'] ?? 'railway';
    $user = $_ENV['MYSQLUSER'] ?? 'root';
    $pass = $_ENV['MYSQLPASSWORD'] ?? 'KKnlRsdVlmoSIGLSsKzsFKvCgPmxdYrx'; 
    $port = $_ENV['MYSQLPORT'] ?? '3306'; 

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userRow && password_verify($password, $userRow['password_hash'])) {
            $_SESSION['user_id'] = $userRow['id'];
            $_SESSION['username'] = $userRow['username'];
            $_SESSION['role'] = $userRow['role'];
            
            header("Location: index.php");
            exit;
        } else {
            // Echo out if credentials don't match so it doesn't leave you guesssing
            die("Authentication Mismatch: Username or password is incorrect.");
        }
    } catch (PDOException $e) {
        die("Database Fatal Crash: " . $e->getMessage());
    }
} else {
    header("Location: login.html");
    exit;
}
?>
