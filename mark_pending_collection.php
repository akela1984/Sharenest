<?php
include 'session_timeout.php';
include 'connection.php';

// Redirect to the comingsoon page if access is not granted
if (!isset($_SESSION['access_granted'])) {
    header('Location: comingsoon.php');
    exit();
}

// Redirect to the sign-in page if the user is not logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: signin.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

// Check if CSRF token is valid
if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

$listing_id = $data['listing_id'];

// Prepare the SQL statement to update the listing state
$sql = "UPDATE listings SET state = 'pending_collection' WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Error preparing statement: ' . $conn->error]);
    exit();
}

$stmt->bind_param("ii", $listing_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error executing statement: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
