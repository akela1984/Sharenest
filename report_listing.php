<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['listing_id'])) {
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
        echo json_encode(['success' => false, 'message' => 'Invalid listing ID.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
