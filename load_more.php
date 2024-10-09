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
        l.id, l.title, l.listing_description, l.time_added, l.listing_type, 
        GROUP_CONCAT(li.image_url) as images,
        u.username,
        loc.location_name
    FROM listings l
    JOIN users u ON l.user_id = u.id
    JOIN locations loc ON l.location_id = loc.location_id
    LEFT JOIN listing_images li ON l.id = li.listing_id
    WHERE l.location_id IN ($locationIdsPlaceholder) AND l.state IN ('available', 'pending_collection')
";

if ($filter === 'sharing') {
    $sql .= " AND l.listing_type = 'sharing'";
} elseif ($filter === 'wanted') {
    $sql .= " AND l.listing_type = 'wanted'";
}

$sql .= " GROUP BY l.id ORDER BY l.time_added DESC LIMIT ?, ?";

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
    $badgeClass = ($row['listing_type'] === 'wanted') ? 'badge-wanted' : 'badge-sharing';
    
    // Split the concatenated image URLs into an array
    $images = array_filter(explode(',', $row['images']));
    
    // Construct the listing object
    $listing = [
        'id' => $row['id'],
        'title' => $row['title'],
        'listing_description' => $row['listing_description'],
        'time_added' => $row['time_added'],
        'images' => $images,
        'username' => $row['username'],
        'location_name' => $row['location_name'],
        'listing_type' => $row['listing_type'],
        'badge_class' => $badgeClass
    ];

    // Add the listing object to the array
    $listings[] = $listing;
}

echo json_encode($listings);
?>
