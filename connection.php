<?php
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
$dbServer = htmlspecialchars($config['database']['server'] ?? null);
$dbUsername = htmlspecialchars($config['database']['username'] ?? null);
$dbPassword = htmlspecialchars($config['database']['password'] ?? null);
$dbName = htmlspecialchars($config['database']['name'] ?? null);

if ($dbServer === null || $dbUsername === null || $dbPassword === null || $dbName === null) {
    die("Error: Database configuration is missing.");
}

// Create connection
$conn = new mysqli($dbServer, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error));
}
//echo "Connected successfully";
?>
