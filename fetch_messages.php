<?php
session_start();
include 'connection.php';

if (!isset($_GET['conversation_id'])) {
    echo json_encode([]);
    exit;
}

$conversation_id = intval($_GET['conversation_id']);
$user_id = $_SESSION['user_id'];

// Fetch messages for the conversation and mark them as read
$sql = "SELECT id, sender_id, recipient_id, message, sent_at, `read` FROM messages WHERE conversation_id = ? ORDER BY sent_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $conversation_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
    if ($row['recipient_id'] == $user_id && !$row['read']) {
        // Mark the message as read
        $update_sql = "UPDATE messages SET `read` = TRUE WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $row['id']);
        $update_stmt->execute();
    }
}

echo json_encode($messages);
?>
