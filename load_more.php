<?php
include 'connection.php';

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$locationIds = isset($_GET['locationIds']) ? $_GET['locationIds'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

if (empty($locationIds)) {
    echo json_encode([]);
    exit;
}

$locationIdsArray = explode(',', $locationIds);
$locationIdsPlaceholder = implode(',', array_fill(0, count($locationIdsArray), '?'));

$sql = "
    SELECT 
        l.id, l.title, l.description, l.time_added, l.type, 
        (SELECT image_url FROM listing_images WHERE listing_id = l.id LIMIT 1) as image_url,
        u.username,
        loc.location_name
    FROM listings l
    JOIN users u ON l.user_id = u.id
    JOIN locations loc ON l.location_id = loc.location_id
    WHERE l.location_id IN ($locationIdsPlaceholder)
";

if ($filter === 'sharing') {
    $sql .= " AND l.type = 'sharing'";
} elseif ($filter === 'wanted') {
    $sql .= " AND l.type = 'wanted'";
}

$sql .= " ORDER BY l.time_added DESC LIMIT ?, ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => $conn->error]);
    exit;
}

// Bind dynamic location IDs and offset, limit parameters
$params = array_merge($locationIdsArray, [$offset, $limit]);
$stmt->bind_param(str_repeat('i', count($locationIdsArray)) . 'ii', ...$params);
$stmt->execute();
$result = $stmt->get_result();

$listings = [];
while ($row = $result->fetch_assoc()) {
    // Determine badge color based on listing type
    $badgeClass = ($row['type'] === 'wanted') ? 'badge-wanted' : 'badge-sharing';
    
    // Construct the listing object
    $listing = [
        'id' => $row['id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'time_added' => $row['time_added'],
        'image_url' => $row['image_url'],
        'username' => $row['username'],
        'location_name' => $row['location_name'],
        'listing_type' => $row['type'], // Make sure 'type' field is included in the listing object
        'badge_class' => $badgeClass
    ];

    // Add the listing object to the array
    $listings[] = $listing;
}

echo json_encode($listings);
?>
