<?php
session_start();

// Redirect non-logged-in users to the sign-in page
if (!isset($_SESSION['loggedin'])) {
    header('Location: signin.php');
    exit;
}

include 'connection.php';

$success_message = '';
$error_message = '';

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_listing_id'])) {
    $listing_id = intval($_POST['delete_listing_id']);

    // Begin a transaction
    $conn->begin_transaction();

    try {
        // Fetch image URLs to delete files
        $sql_images = "SELECT image_url FROM listing_images WHERE listing_id = ?";
        $stmt_images = $conn->prepare($sql_images);
        $stmt_images->bind_param("i", $listing_id);
        $stmt_images->execute();
        $result_images = $stmt_images->get_result();
        
        while ($row = $result_images->fetch_assoc()) {
            $image_path = $row['image_url'];
            if (file_exists($image_path)) {
                unlink($image_path); // Delete the file
            }
        }

        // Delete from listing_images
        $sql_delete_images = "DELETE FROM listing_images WHERE listing_id = ?";
        $stmt_delete_images = $conn->prepare($sql_delete_images);
        $stmt_delete_images->bind_param("i", $listing_id);
        $stmt_delete_images->execute();

        // Delete all related messages and conversation members
        $sql_delete_messages = "DELETE m FROM messages m
                                JOIN conversations c ON m.conversation_id = c.id
                                WHERE c.listing_id = ?";
        $stmt_delete_messages = $conn->prepare($sql_delete_messages);
        $stmt_delete_messages->bind_param("i", $listing_id);
        $stmt_delete_messages->execute();

        $sql_delete_conversation_members = "DELETE cm FROM conversation_members cm
                                            JOIN conversations c ON cm.conversation_id = c.id
                                            WHERE c.listing_id = ?";
        $stmt_delete_conversation_members = $conn->prepare($sql_delete_conversation_members);
        $stmt_delete_conversation_members->bind_param("i", $listing_id);
        $stmt_delete_conversation_members->execute();

        // Delete from conversations
        $sql_delete_conversations = "DELETE FROM conversations WHERE listing_id = ?";
        $stmt_delete_conversations = $conn->prepare($sql_delete_conversations);
        $stmt_delete_conversations->bind_param("i", $listing_id);
        $stmt_delete_conversations->execute();

        // Delete from listings
        $sql_delete_listing = "DELETE FROM listings WHERE id = ?";
        $stmt_delete_listing = $conn->prepare($sql_delete_listing);
        $stmt_delete_listing->bind_param("i", $listing_id);
        $stmt_delete_listing->execute();

        // Commit the transaction
        $conn->commit();

        $success_message = "Listing deleted successfully.";
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        error_log("Error deleting listing: " . $e->getMessage());
        $error_message = "Error deleting listing. Please try again.";
    }
}

// Fetch user's information
$username = $_SESSION['username'];
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "<p>User not found. Please <a href='signin.php'>sign in</a> again.</p>";
    exit;
}

// Placeholder for user image if not uploaded
$profileImage = !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'img/users_placeholder.png';

// Fetch listing data for the user
$userId = $user['id'];
$sqlListings = "SELECT state FROM listings WHERE user_id = ?";
$stmtListings = $conn->prepare($sqlListings);
$stmtListings->bind_param("i", $userId);
$stmtListings->execute();
$resultListings = $stmtListings->get_result();

$totalListings = $resultListings->num_rows;
$activeListings = 0;
$pendingCollection = 0;

while ($row = $resultListings->fetch_assoc()) {
    if ($row['state'] === 'available') {
        $activeListings++;
    } elseif ($row['state'] === 'pending_collection') {
        $pendingCollection++;
    }
}

// Fetch total conversations and conversations with unread messages
$sql_conversations = "
    SELECT 
        COUNT(DISTINCT c.id) AS total_conversations,
        COUNT(DISTINCT CASE WHEN m.read = 0 AND m.recipient_id = ? THEN c.id END) AS unread_conversations
    FROM conversations c
    JOIN conversation_members cm ON c.id = cm.conversation_id
    LEFT JOIN messages m ON c.id = m.conversation_id
    WHERE cm.user_id = ?";

$stmt_conversations = $conn->prepare($sql_conversations);
$stmt_conversations->bind_param("ii", $userId, $userId);
$stmt_conversations->execute();
$result_conversations = $stmt_conversations->get_result();
$conversations_data = $result_conversations->fetch_assoc();

$totalConversations = $conversations_data['total_conversations'];
$unreadConversations = $conversations_data['unread_conversations'];

// Fetch total unread messages for the circle chart
$totalMessages = $totalConversations;
$unreadMessages = $unreadConversations;

// Dummy data for statistics (replace with actual queries)
$greenPoints = 120;  // Replace with actual query to get green points

$limit = 10; // Number of entries to show per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'time_added';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Fetch user's listings with images and pagination
$sql = "
    SELECT l.id, l.title, l.listing_description, l.time_added, l.listing_type, l.state,
           (SELECT li.image_url FROM listing_images li WHERE li.listing_id = l.id LIMIT 1) as image_url 
    FROM listings l
    WHERE l.user_id = ?
    ORDER BY $sort_by $order
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user['id'], $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$listings = [];
while ($row = $result->fetch_assoc()) {
    $listings[] = $row;
}

// Get the total number of listings for pagination
$sql_total = "SELECT COUNT(*) as count FROM listings WHERE user_id = ?";
$stmt_total = $conn->prepare($sql_total);
$stmt_total->bind_param("i", $user['id']);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$total_listings = $result_total->fetch_assoc()['count'];
$total_pages = ceil($total_listings / $limit);
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ShareNest - Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link href="https://getbootstrap.com/docs/5.3/assets/css/docs.css" rel="stylesheet">
<link href="css/styles.css" rel="stylesheet">
<style>
    .circle-chart {
        position: relative;
        width: 80px;
        height: 80px;
        margin: 20px auto;
    }
    .circle-chart canvas {
        position: absolute;
        top: 0;
        left: 0;
    }
    .circle-chart .circle-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 0.8rem;
        font-weight: bold;
    }
    .legend {
        text-align: center;
        margin-top: 10px;
    }
    .legend span {
        display: inline-block;
        width: 12px;
        height: 12px;
        margin-right: 5px;
    }
    .blue { background-color: #007bff; }
    .grey { background-color: #e9ecef; }
    .red { background-color: #dc3545; }
    .orange { background-color: #ffc107; }
    .green { background-color: #28a745; }

    @media (max-width: 576px) {
        .circle-chart {
            width: 60px;
            height: 60px;
            margin: 10px auto;
        }
        .circle-chart .circle-text {
            font-size: 0.7rem;
        }
    }
</style>
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<div class="container my-listings-container">
    <h2 class="my-listings-title">Dashboard</h2>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="row text-center">
                <div class="col-4">
                    <div class="circle-chart">
                        <canvas id="messagesChart" width="100" height="100"></canvas>
                        <div class="circle-text"><?php echo $unreadMessages; ?>/<?php echo $totalMessages; ?></div>
                    </div>
                    <h3 style="font-size: 1rem;">Conversations</h3>
                    <div class="legend">
                        <span class="blue"></span> Unread<br>
                        <span class="grey"></span> Total
                    </div>
                </div>
                <div class="col-4">
                    <div class="circle-chart">
                        <canvas id="listingsChart" width="100" height="100"></canvas>
                        <div class="circle-text"><?php echo "$activeListings/$pendingCollection"; ?></div>
                    </div>
                    <h3 style="font-size: 1rem;">Listings</h3>
                    <div class="legend">
                        <span class="red"></span> Active<br>
                        <span class="orange"></span> Pending Collection
                    </div>
                </div>
                <div class="col-4">
                    <div class="circle-chart">
                        <canvas id="greenPointsChart" width="100" height="100"></canvas>
                        <div class="circle-text"><?php echo $greenPoints; ?></div>
                    </div>
                    <h3 style="font-size: 1rem;">Green Points</h3>
                    <div class="legend">
                        <span class="green"></span> Points
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success" role="alert">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($listings)): ?>
        <div class="alert alert-info" role="alert">
            You do not have any active listings. <a href="create_listing.php">Add a new listing here.</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table my-listings-table table-striped">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>
                            <a href="?sort_by=title&order=<?php echo $order == 'ASC' ? 'DESC' : 'ASC'; ?>">
                                Title
                                <?php if ($sort_by == 'title') echo $order == 'ASC' ? '▲' : '▼'; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort_by=listing_type&order=<?php echo $order == 'ASC' ? 'DESC' : 'ASC'; ?>">
                                Type
                                <?php if ($sort_by == 'listing_type') echo $order == 'ASC' ? '▲' : '▼'; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort_by=time_added&order=<?php echo $order == 'ASC' ? 'DESC' : 'ASC'; ?>">
                                Date
                                <?php if ($sort_by == 'time_added') echo $order == 'ASC' ? '▲' : '▼'; ?>
                            </a>
                        </th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listings as $listing): ?>
                        <tr>
                            <td>
                                <?php if ($listing['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($listing['image_url']); ?>" alt="Listing Image" class="img-fluid my-listings-image">
                                <?php else: ?>
                                    <img src="img/listing_placeholder.jpeg" alt="No Image Available" class="img-fluid my-listings-image">
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($listing['title']); ?></td>
                            <td>
                                <span class="badge <?php echo $listing['listing_type'] == 'wanted' ? 'badge-wanted' : 'badge-sharing'; ?>">
                                    <?php echo $listing['listing_type'] == 'wanted' ? 'Wanted' : 'For Sharing'; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($listing['time_added']); ?></td>
                            <td>
                                <span class="badge <?php echo $listing['state'] == 'available' ? 'badge-available' : ($listing['state'] == 'unavailable' ? 'badge-unavailable' : ($listing['state'] == 'pending_collection' ? 'badge-pending' : 'badge-review')); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $listing['state'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons d-flex">
                                    <form action="edit_listing.php" method="GET" class="edit-form d-inline">
                                        <input type="hidden" name="id" value="<?php echo $listing['id']; ?>">
                                        <button type="submit" class="btn btn-outline-warning my-listings-edit-btn">Edit</button>
                                    </form>
                                    <form action="my_dashboard.php" method="POST" class="delete-form d-inline">
                                        <input type="hidden" name="delete_listing_id" value="<?php echo $listing['id']; ?>">
                                        <button type="submit" class="btn btn-outline-danger my-listings-delete-btn" onclick="return confirm('Are you sure you want to delete this listing?');">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination STARTS here -->
        <nav aria-label="Page navigation example">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <!-- Pagination ENDS here -->
    <?php endif; ?>
</div>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Messages Chart
        const messagesCtx = document.getElementById('messagesChart').getContext('2d');
        new Chart(messagesCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [<?php echo $unreadMessages; ?>, <?php echo $totalMessages - $unreadMessages; ?>],
                    backgroundColor: ['#007bff', '#e9ecef']
                }]
            },
            options: {
                cutout: '70%',
                plugins: {
                    tooltip: {enabled: false},
                    legend: {display: false}
                }
            }
        });

        // Listings Chart
        const listingsCtx = document.getElementById('listingsChart').getContext('2d');
        new Chart(listingsCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [
                        <?php echo $activeListings; ?>,
                        <?php echo $pendingCollection; ?>,
                        <?php echo $activeListings + $pendingCollection === 0 ? 1 : 0; ?>
                    ],
                    backgroundColor: [
                        '#dc3545',
                        '#ffc107',
                        '#e9ecef'
                    ]
                }]
            },
            options: {
                cutout: '70%',
                plugins: {
                    tooltip: {enabled: false},
                    legend: {display: false}
                }
            }
        });

        // Green Points Chart
        const greenPointsCtx = document.getElementById('greenPointsChart').getContext('2d');
        new Chart(greenPointsCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [<?php echo $greenPoints; ?>, 0], // Always full circle
                    backgroundColor: ['#28a745', '#28a745']
                }]
            },
            options: {
                cutout: '70%',
                plugins: {
                    tooltip: {enabled: false},
                    legend: {display: false}
                }
            }
        });
    });
</script>
</body>
</html>
