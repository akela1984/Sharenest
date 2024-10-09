<?php
include 'session_timeout.php';

// Redirect non-logged-in users to the sign-in page
if (!isset($_SESSION['loggedin'])) {
    header('Location: signin.php');
    exit;
}

include 'connection.php';

// Sanitize input
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$locationIds = isset($_GET['locationIds']) ? trim($_GET['locationIds']) : '';

if (empty($query) || empty($locationIds)) {
    echo json_encode([]);
    exit;
}

// Sanitize and prepare location IDs
$locationIdsArray = array_map('intval', explode(',', $locationIds));
$locationIdsPlaceholder = implode(',', array_fill(0, count($locationIdsArray), '?'));

// Prepare the search term
$searchTerm = '%' . $conn->real_escape_string($query) . '%';

$sql = "
    SELECT 
        l.id, l.title, l.listing_description, l.listing_type,
        COALESCE(li.image_url, 'img/listing_placeholder.jpeg') as image
    FROM listings l
    LEFT JOIN listing_images li ON l.id = li.listing_id
    WHERE l.location_id IN ($locationIdsPlaceholder) 
    AND (l.title LIKE ? OR l.listing_description LIKE ?)
    GROUP BY l.id
    ORDER BY l.time_added DESC
    LIMIT 3
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8')]);
    exit;
}

$params = array_merge($locationIdsArray, [$searchTerm, $searchTerm]);
$stmt->bind_param(str_repeat('i', count($locationIdsArray)) . 'ss', ...$params);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];
while ($row = $result->fetch_assoc()) {
    $suggestions[] = [
        'id' => htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'),
        'title' => htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'),
        'image' => htmlspecialchars($row['image'], ENT_QUOTES, 'UTF-8'),
        'listing_type' => htmlspecialchars($row['listing_type'], ENT_QUOTES, 'UTF-8')
    ];
}

echo json_encode($suggestions);
?>
