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

    // Delete from listings
    $sql_delete_listing = "DELETE FROM listings WHERE id = ?";
    $stmt_delete_listing = $conn->prepare($sql_delete_listing);
    $stmt_delete_listing->bind_param("i", $listing_id);
    if ($stmt_delete_listing->execute()) {
        $success_message = "Listing deleted successfully.";
    } else {
        $error_message = "Error deleting listing.";
    }
}

// Fetch user's information
$username = $_SESSION['username'];
$sql = "SELECT id FROM users WHERE username = ?";
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
    <title>My Listings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="css/styles.css" rel="stylesheet">
    <link href="css/my_listings.css" rel="stylesheet">
</head>
<body>

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<div class="container my-listings-container">
    <h2 class="my-listings-title">My Listings</h2>

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
                                <form action="my_listings.php" method="POST" class="delete-form d-inline">
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
</div>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
