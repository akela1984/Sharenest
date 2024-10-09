<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['loggedin'])) {
    header('Location: signin.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$listing_id = $data['listing_id'];

$sql = "UPDATE listings SET state = 'pending_collection' WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Error preparing statement: ' . $conn->error]);
    exit;
}
$stmt->bind_param("ii", $listing_id, $user_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error executing statement: ' . $stmt->error]);
}
?>
