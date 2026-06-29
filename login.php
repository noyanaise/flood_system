<?php
// Safely start secure session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Handle Logout routing action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array();
    session_destroy();
    header("Location: login.html");
    exit;
}

// Database Configuration using Railway Variables
$host = $_ENV['MYSQLHOST'] ?? 'mysql.railway.internal';
$db   = $_ENV['MYSQLDATABASE'] ?? 'railway';
$user = $_ENV['MYSQLUSER'] ?? 'root';
$pass = $_ENV['MYSQLPASSWORD'] ?? 'KKnlRsdVlmoSIGLSsKzsFKvCgPmxdYrx'; 
$port = $_ENV['MYSQLPORT'] ?? '3306'; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    echo json_encode(["error" => "Database link failure: " . $e->getMessage()]);
    exit;
}

// Intercept the Login Form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $inputData = json_decode($rawInput, true) ?? $_POST;

    $username = trim($inputData['username'] ?? '');
    $password = trim($inputData['password'] ?? '');

    if (empty($username) || empty($password)) {
        echo json_encode(["error" => "Please enter both username and password."]);
        exit;
    }

    try {
        // Query user details from DB
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $userAccount = $stmt->fetch();

        if ($userAccount && password_verify($password, $userAccount['password_hash'])) {
            
            // 🔥 OTP TEMPORARILY DISABLED: Directly authorize session values right now!
            $_SESSION['user_id']   = $userAccount['id'];
            $_SESSION['username']  = $userAccount['username'];
            $_SESSION['role']      = $userAccount['role'];
            $_SESSION['user_role'] = $userAccount['role']; // Fallback matching key

            // Tell your frontend everything went fine and to load index.php immediately
            echo json_encode([
                "status" => "success", 
                "message" => "Authentication clear! Redirecting...", 
                "redirect" => "index.php"
            ]);
            exit;
        } else {
            echo json_encode(["error" => "Invalid username or password match configuration."]);
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode(["error" => "System processing fault: " . $e->getMessage()]);
        exit;
    }
}
?>
