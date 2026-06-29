<?php
// Include PHPMailer classes at the top
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Security headers
// Security headers optimized for seamless Railway reverse proxy routing
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: no-referrer-when-downgrade");

// Optimized CSP: Allows the native browser form action to submit to self safely
header("Content-Security-Policy: default-src 'self'; form-action 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:;");
// Secure session configuration optimized for Railway proxies
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax' // Changed to Lax to prevent cross-page cookie loss behind reverse proxy
    ]);
    session_start();
}

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ========================================================
// ROUTE REGION: DE-AUTHENTICATION EXPLICIT ACTION (LOGOUT)
// ========================================================
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

        $stmt = $pdo->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userRow && password_verify($password, $userRow['password_hash'])) {
            session_regenerate_id(true); 

            // 1. Generate 6-digit cryptographic OTP
            $otp = sprintf("%06d", random_int(100000, 999999));
            $expiry = date("Y-m-d H:i:s", strtotime('+10 minutes'));

            // 2. Save OTP and Expiry into the user's row
            $updateStmt = $pdo->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?");
            $updateStmt->execute([$otp, $expiry, $userRow['id']]);

            // 3. Send email via PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; 
                $mail->SMTPAuth   = true;
                $mail->Username   = 'floodsystem6246@gmail.com';       
                $mail->Password   = 'ssco dghg qmfl crrq';        
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                $mail->Port       = 587;                          

                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );                 

                $mail->setFrom('floodsystem6246@gmail.com', 'Flood System Security');
                $mail->addAddress($userRow['email']); 

                $mail->isHTML(true);
                $mail->Subject = 'Your Login Verification Code';
                $mail->Body    = "Your One-Time Password (OTP) for login is <b>$otp</b>. It will expire in 10 minutes.";
                $mail->AltBody = "Your One-Time Password (OTP) for login is $otp. It will expire in 10 minutes.";

                $mail->send();

                // 4. Staging values safely for validation step in verification pipeline
                $_SESSION['temp_user_id']  = $userRow['id'];
                $_SESSION['temp_username'] = $userRow['username'];
                $_SESSION['temp_role']     = $userRow['role'];

                header("Location: otp.html");
                exit;

            } catch (Exception $e) {
                die("Mailer Error: " . $e->getMessage());
            }
        } else {
            header("Location: login.html?error=failed");
            exit;
        }

    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: login.html");
    exit;
}
?>
