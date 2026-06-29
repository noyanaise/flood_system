<?php
// Force error reporting on to immediately catch any remaining database execution issues
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Security headers optimized for seamless Railway reverse proxy routing
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: no-referrer-when-downgrade");

// Secure session configuration optimized for Railway proxies
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Handle Logout routing action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
    header("Location: login.html");
    exit;
}

// ========================================================
// ROUTE REGION: POST REQUEST VALIDATION & RUNTIME CHECK
// ========================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // DATABASE CONFIGURATION FOR INTERNAL RAILWAY NETWORK
    $host = $_ENV['MYSQLHOST'] ?? 'mysql.railway.internal';
    $db   = $_ENV['MYSQLDATABASE'] ?? 'railway';
    $user = $_ENV['MYSQLUSER'] ?? 'root';
    $pass = $_ENV['MYSQLPASSWORD'] ?? 'KKnlRsdVlmoSIGLSsKzsFKvCgPmxdYrx'; 
    $port = $_ENV['MYSQLPORT'] ?? '3306'; 
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        header("Location: login.html?error=empty");
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

        // Query user details from DB
        $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userRow && password_verify($password, $userRow['password_hash'])) {
            session_regenerate_id(true); 

            // 🔥 OTP BYPASS: Directly authenticate the main dashboard sessions!
            $_SESSION['user_id']   = $userRow['id'];
            $_SESSION['username']  = $userRow['username'];
            $_SESSION['role']      = $userRow['role'];
            $_SESSION['user_role'] = $userRow['role']; // Secondary fallback mapping key

            // Send them straight to the main dashboard panel, bypassing otp.html
            header("Location: index.php");
            exit;
        } else {
            header("Location: login.html?error=failed");
            exit;
        }

    } catch (PDOException $e) {
        die(json_encode(["error" => "Database Connection/Query Error: " . $e->getMessage()]));
    }
} else {
    header("Location: login.html");
    exit;
}
?>
