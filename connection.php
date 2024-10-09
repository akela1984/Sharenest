<?php
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
$dbServer = $config['database']['server'] ?? null;
$dbUsername = $config['database']['username'] ?? null;
$dbPassword = $config['database']['password'] ?? null;
$dbName = $config['database']['name'] ?? null;

if ($dbServer === null || $dbUsername === null || $dbPassword === null || $dbName === null) {
    die("Error: Database configuration is missing in the configuration file.");
}

// Create connection
$conn = new mysqli($dbServer, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
//echo "Connected successfully";
?>
