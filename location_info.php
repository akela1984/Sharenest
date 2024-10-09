<?php
include 'connection.php';

$location_name = $_GET['location_name'];

// Fetch the location id
$sql = "SELECT location_id FROM locations WHERE location_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $location_name);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$location_id = $row['location_id'];

// Fetch the number of listings and sharing listings
$sql_info = "SELECT 
                COUNT(*) as total_listings
             FROM listings WHERE location_id = ?";
$stmt_info = $conn->prepare($sql_info);
$stmt_info->bind_param("i", $location_id);
$stmt_info->execute();
$result_info = $stmt_info->get_result();
$info = $result_info->fetch_assoc();

// Fetch the last item added to the location
$sql_last_item = "SELECT title, time_added FROM listings WHERE location_id = ? ORDER BY time_added DESC LIMIT 1";
$stmt_last_item = $conn->prepare($sql_last_item);
$stmt_last_item->bind_param("i", $location_id);
$stmt_last_item->execute();
$result_last_item = $stmt_last_item->get_result();
$last_item = $result_last_item->fetch_assoc();

$response = [
    'total_listings' => $info['total_listings'],
    'last_item' => $last_item
];

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>
