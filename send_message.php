<?php
include 'session_timeout.php';
include 'connection.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['conversation_id'], $data['message'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$conversation_id = $data['conversation_id'];
$sender_id = $_SESSION['user_id'];
$message = $data['message'];

// Fetch the recipient_id from the conversation_members table
$sql = "SELECT user_id FROM conversation_members WHERE conversation_id = ? AND user_id != ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $conversation_id, $sender_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Recipient not found']);
    exit;
}

$recipient = $result->fetch_assoc();
$recipient_id = $recipient['user_id'];

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
} else {
    $response['error'] = 'Error executing statement: ' . $stmt->error;
}

echo json_encode($response);
?>
