<?php
include 'session_timeout.php';
include 'connection.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Load PHPMailer
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['conversation_id'], $data['message'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$conversation_id = $data['conversation_id'];
$sender_id = $_SESSION['user_id'];
$message = $data['message'];

// Fetch the recipient_id and recipient email from the conversation_members table
$sql = "
    SELECT cm.user_id, u.username AS recipient_username, u.email AS recipient_email, l.title AS listing_title, us.username AS sender_username
    FROM conversation_members cm
    JOIN users u ON cm.user_id = u.id
    JOIN conversations c ON cm.conversation_id = c.id
    JOIN listings l ON c.listing_id = l.id
    JOIN users us ON us.id = ?
    WHERE cm.conversation_id = ? AND cm.user_id != ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $sender_id, $conversation_id, $sender_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Recipient not found']);
    exit;
}

$recipient = $result->fetch_assoc();
$recipient_id = $recipient['user_id'];
$recipient_username = $recipient['recipient_username'];
$recipient_email = $recipient['recipient_email'];
$listing_title = $recipient['listing_title'];
$sender_username = $recipient['sender_username'];

// Insert new message into the database
$sql = "INSERT INTO messages (conversation_id, sender_id, recipient_id, message, sent_at) VALUES (?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Error preparing statement: ' . $conn->error]);
    exit;
}
$stmt->bind_param("iiis", $conversation_id, $sender_id, $recipient_id, $message);

$response = ['success' => false];

if ($stmt->execute()) {
    $response['success'] = true;

    // Send email notification to the recipient
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.livemail.co.uk';
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp']['username'];
        $mail->Password   = $config['smtp']['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('no-reply@sharenest.org', 'Sharenest');
        $mail->addAddress($recipient_email, $recipient_username);

        // Load HTML template
        $templatePath = __DIR__ . '/templates/internal_message_template.html';
        if (!file_exists($templatePath)) {
            throw new Exception("Email template not found at $templatePath");
        }
        $template = file_get_contents($templatePath);
        
        // Sanitize and encode variables for HTML output
        $safe_recipient_username = htmlspecialchars($recipient_username);
        $safe_sender_username = htmlspecialchars($sender_username);
        $safe_listing_title = htmlspecialchars($listing_title);
        $safe_message = nl2br(htmlspecialchars($message));

        // Replace placeholders in the template
        $emailBody = str_replace(
            ['{{recipient_username}}', '{{sender_username}}', '{{listing_title}}', '{{message}}'],
            [$safe_recipient_username, $safe_sender_username, $safe_listing_title, $safe_message],
            $template
        );

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New Message Notification';
        $mail->Body    = $emailBody;

        // Embed the image
        $logoPath = __DIR__ . '/img/sharenest_logo.png';
        if (!file_exists($logoPath)) {
            throw new Exception("Logo not found at $logoPath");
        }
        $mail->addEmbeddedImage($logoPath, 'sharenest_logo');

        $mail->send();
    } catch (Exception $e) {
        $response['error'] = 'Message sent but email notification failed: ' . $mail->ErrorInfo . ' Exception: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'Error executing statement: ' . $stmt->error;
}

echo json_encode($response);
?>
