<?php
include 'session_timeout.php';
include 'connection.php';

// Check if the user has access REMOVE THIS AFTER GO LIVE
if (!isset($_SESSION['access_granted'])) {
    header('Location: comingsoon.php');
    exit();
}

header('Content-Type: application/json');

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['csrf_token']) && hash_equals($_SESSION['csrf_token'], $data['csrf_token']) && isset($data['listing_id'])) {
        $listing_id = intval($data['listing_id']);

        // Update the listing status to "under_review"
        $sql = "UPDATE listings SET state = 'under_review' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $listing_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update the listing state.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token or listing ID.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
