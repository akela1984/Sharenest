<?php
// Enable mysqli error reporting
mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);

// Path to the configuration file
$configFilePath = dirname(__DIR__) . '/config/config.ini';

// Check if the configuration file exists
if (!file_exists($configFilePath)) {
    die("Error: Configuration file not found.");
}

// Load configuration from config.ini located in the config directory
$config = parse_ini_file($configFilePath, true);

if ($config === false) {
    die("Error: Failed to parse configuration file.");
}

// Extract database configuration
$dbServer = $config['database']['server'] ?? null;
$dbUsername = $config['database']['username'] ?? null;
$dbPassword = $config['database']['password'] ?? null;
$dbName = $config['database']['name'] ?? null;

if ($dbServer === null || $dbUsername === null || $dbPassword === null || $dbName === null) {
    die("Error: Database configuration is missing.");
}

try {
    // Create connection
    $conn = new mysqli($dbServer, $dbUsername, $dbPassword, $dbName);
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    //echo "Connected successfully";
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
