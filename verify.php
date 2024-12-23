<?php
include 'session_timeout.php';
include 'connection.php';

// Path to the configuration file
$configFilePath = dirname(__DIR__) . '/config/config.ini';

// Check if the configuration file exists
if (!file_exists($configFilePath)) {
    die("Error: Configuration file not found at $configFilePath");
}

// Load configuration from config.ini located in the config directory
$config = parse_ini_file($configFilePath, true);

if ($config === false) {
    die("Error: Failed to parse configuration file at $configFilePath");
}

// Extract database configuration
$dbServer = htmlspecialchars($config['database']['server'], ENT_QUOTES, 'UTF-8'); // Sanitize server name
$dbUsername = htmlspecialchars($config['database']['username'], ENT_QUOTES, 'UTF-8'); // Sanitize username
$dbPassword = htmlspecialchars($config['database']['password'], ENT_QUOTES, 'UTF-8'); // Sanitize password
$dbName = htmlspecialchars($config['database']['name'], ENT_QUOTES, 'UTF-8'); // Sanitize database name

// Create connection
$conn = new mysqli($dbServer, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error, ENT_QUOTES, 'UTF-8'));
}

if (isset($_GET['token'])) {
    $token = htmlspecialchars($_GET['token'], ENT_QUOTES, 'UTF-8'); // Sanitize token

    // Validate the token
    $sql = "SELECT * FROM users WHERE token = ? AND status = 'inactive'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Token is valid, activate the user's account
        $sql = "UPDATE users SET status = 'active', token = NULL WHERE token = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();

        if ($stmt->affected_rows === 1) {
            $_SESSION['message'] = "Your account has been successfully activated!";
        } else {
            $_SESSION['message'] = "Account activation failed, please try again.";
        }
    } else {
        $_SESSION['message'] = "Invalid or expired token.";
    }
    header('Location: signin.php');
    exit;
} else {
    header('Location: index.php');
    exit;
}

$conn->close();
?>
