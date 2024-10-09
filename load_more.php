<?php
session_start();

if (!isset($_SESSION['loggedin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

include 'connection.php';

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$locationIds = isset($_GET['locationIds']) ? $_GET['locationIds'] : '';

if (empty($locationIds)) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT listings.*, listing_images.image_url 
        FROM listings 
        LEFT JOIN listing_images ON listings.id = listing_images.listing_id 
        WHERE location_id IN ($locationIds) AND state IN ('available', 'pending')
        GROUP BY listings.id 
        ORDER BY time_added DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$listings = [];
while ($row = $result->fetch_assoc()) {
    $listings[] = $row;
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($listings);
