<?php
include 'session_timeout.php';

// Redirect non-logged-in users to the sign-in page
if (!isset($_SESSION['loggedin'])) {
    header('Location: signin.php');
    exit;
}

include 'connection.php';

$success_message = '';
$error_message = '';

// Fetch the username from the session
$username = htmlspecialchars($_SESSION['username']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_listing'])) {
        $listing_id = intval($_POST['listing_id']);
        $title = htmlspecialchars($_POST['title']);
        $listing_description = htmlspecialchars($_POST['listing_description']);
        $listing_type = htmlspecialchars($_POST['listing_type']);
        $state = htmlspecialchars($_POST['state']);

        // Update listing details
        $sql_update = "
            UPDATE listings 
            SET title = ?, listing_description = ?, listing_type = ?, state = ? 
            WHERE id = ? AND user_id = (SELECT id FROM users WHERE username = ?)
        ";
        $stmt_update = $conn->prepare($sql_update);
        if ($stmt_update === false) {
            $error_message = 'Prepare failed: ' . htmlspecialchars($conn->error);
        } else {
            $stmt_update->bind_param("ssssis", $title, $listing_description, $listing_type, $state, $listing_id, $_SESSION['username']);
            if ($stmt_update->execute()) {
                $success_message = "Listing updated successfully.";

                // Handle image upload
                if (!empty($_FILES['images']['name'][0])) {
                    // Delete old images
                    $sql_images = "SELECT image_url FROM listing_images WHERE listing_id = ?";
                    $stmt_images = $conn->prepare($sql_images);
                    if ($stmt_images === false) {
                        $error_message = 'Prepare failed: ' . htmlspecialchars($conn->error);
                    } else {
                        $stmt_images->bind_param("i", $listing_id);
                        $stmt_images->execute();
                        $result_images = $stmt_images->get_result();
                        while ($row = $result_images->fetch_assoc()) {
                            $image_path = $row['image_url'];
                            if (file_exists($image_path)) {
                                unlink($image_path); // Delete the file
                            }
                        }

                        // Delete old image records from database
                        $sql_delete_images = "DELETE FROM listing_images WHERE listing_id = ?";
                        $stmt_delete_images = $conn->prepare($sql_delete_images);
                        if ($stmt_delete_images === false) {
                            $error_message = 'Prepare failed: ' . htmlspecialchars($conn->error);
                        } else {
                            $stmt_delete_images->bind_param("i", $listing_id);
                            $stmt_delete_images->execute();
                        }

                        // Upload new images
                        $upload_dir = 'uploads/listing_images/';
                        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                            $file_extension = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                            $random_code = bin2hex(random_bytes(5)); // Generate a random code
                            $file_name = $username . '_' . date('YmdHis') . '_' . $random_code . '.' . $file_extension;
                            $file_path = $upload_dir . $file_name;
                            if (move_uploaded_file($tmp_name, $file_path)) {
                                $sql_insert_image = "INSERT INTO listing_images (listing_id, image_url) VALUES (?, ?)";
                                $stmt_insert_image = $conn->prepare($sql_insert_image);
                                if ($stmt_insert_image === false) {
                                    $error_message = 'Prepare failed: ' . htmlspecialchars($conn->error);
                                } else {
                                    $stmt_insert_image->bind_param("is", $listing_id, $file_path);
                                    $stmt_insert_image->execute();
                                }
                            }
                        }
                    }
                }
            } else {
                $error_message = "Error updating listing.";
            }
        }
    } elseif (isset($_POST['delete_listing'])) {
        $listing_id = intval($_POST['listing_id']);

        // Fetch image URLs to delete files
        $sql_images = "SELECT image_url FROM listing_images WHERE listing_id = ?";
        $stmt_images = $conn->prepare($sql_images);
        if ($stmt_images === false) {
            $error_message = 'Prepare failed: ' . htmlspecialchars($conn->error);
        } else {
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
            if ($stmt_delete_images === false) {
                $error_message = 'Prepare failed: ' . htmlspecialchars($conn->error);
            } else {
                $stmt_delete_images->bind_param("i", $listing_id);
                $stmt_delete_images->execute();
            }

            // Delete from listings
            $sql_delete_listing = "DELETE FROM listings WHERE id = ? AND user_id = (SELECT id FROM users WHERE username = ?)";
            $stmt_delete_listing = $conn->prepare($sql_delete_listing);
            if ($stmt_delete_listing === false) {
                $error_message = 'Prepare failed: ' . htmlspecialchars($conn->error);
            } else {
                $stmt_delete_listing->bind_param("is", $listing_id, $_SESSION['username']);
                if ($stmt_delete_listing->execute()) {
                    header('Location: my_dashboard.php?success_message=' . urlencode('Listing deleted successfully.'));
                    exit;
                } else {
                    $error_message = "Error deleting listing.";
                }
            }
        }
    }
} else {
    $listing_id = intval($_GET['id']);
}

// Fetch listing details
$sql = "SELECT * FROM listings WHERE id = ? AND user_id = (SELECT id FROM users WHERE username = ?)";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $error_message = 'Prepare failed: ' . htmlspecialchars($conn->error);
}
$stmt->bind_param("is", $listing_id, $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $listing = $result->fetch_assoc();
} else {
    echo "<p>Listing not found or you do not have permission to edit this listing. Please <a href='my_listings.php'>go back</a>.</p>";
    exit;
}

// Fetch listing images
$sql_images = "SELECT image_url FROM listing_images WHERE listing_id = ?";
$stmt_images = $conn->prepare($sql_images);
if ($stmt_images === false) {
    $error_message = 'Prepare failed: ' . htmlspecialchars($conn->error);
}
$stmt_images->bind_param("i", $listing_id);
$stmt_images->execute();
$result_images = $stmt_images->get_result();
$images = [];
while ($row = $result_images->fetch_assoc()) {
    $images[] = htmlspecialchars($row['image_url']);
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
       <!-- Web App Manifest -->
       <link rel="manifest" href="/manifest.json">

<!-- Theme Color -->
<meta name="theme-color" content="#4CAF50">

<!-- iOS-specific meta tags -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Sharenest">
<link rel="apple-touch-icon" href="/icons/icon-192x192.png">

<!-- Icons for various devices -->
<link rel="apple-touch-icon" sizes="180x180" href="/icons/icon-180x180.png">
<link rel="apple-touch-icon" sizes="192x192" href="/icons/icon-192x192.png">
<link rel="apple-touch-icon" sizes="512x512" href="/icons/icon-512x512.png">

<!-- Link to External PWA Script -->
<script src="/js/pwa.js" defer></script>
    <title>Edit Listing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="css/styles.css" rel="stylesheet">
    <link href="css/edit_listing.css" rel="stylesheet">
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<div id="content" class="container edit-listing-container">
    <h2 class="edit-listing-title">Edit Listing</h2>

    <?php if ($success_message): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <form action="edit_listing.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="listing_id" value="<?php echo htmlspecialchars($listing['id']); ?>">
        
        <div class="mb-3">
            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($listing['title']); ?>" required>
        </div>
        
        <div class="mb-3">
            <label for="listing_description" class="form-label">Description <span class="text-danger">*</span></label>
            <textarea class="form-control" id="listing_description" name="listing_description" rows="4" required><?php echo htmlspecialchars($listing['listing_description']); ?></textarea>
        </div>
        
        <div class="mb-3">
            <label for="state" class="form-label">Status <span class="text-danger">*</span></label>
            <select class="form-select" id="state" name="state" required>
                <option value="available" <?php if ($listing['state'] == 'available') echo 'selected'; ?>>Available</option>
                <option value="unavailable" <?php if ($listing['state'] == 'unavailable') echo 'selected'; ?>>Unavailable</option>
                <option value="pending_collection" <?php if ($listing['state'] == 'pending_collection') echo 'selected'; ?>>Pending Collection</option>
                <option value="under_review" <?php if ($listing['state'] == 'under_review') echo 'selected'; ?>>Under Review</option>
            </select>
        </div>
        
        <div class="mb-3">
            <label for="listing_type" class="form-label">Type <span class="text-danger">*</span></label>
            <select class="form-select" id="listing_type" name="listing_type" required>
                <option value="sharing" <?php if ($listing['listing_type'] == 'sharing') echo 'selected'; ?>>For Sharing</option>
                <option value="wanted" <?php if ($listing['listing_type'] == 'wanted') echo 'selected'; ?>>Wanted</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="images" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top" title="You can upload up to 5 images.">Upload New Images</label>
            <input type="file" class="form-control" id="images" name="images[]" multiple>
            <small class="form-text text-muted">You can upload up to 5 images, but all previous images will be deleted even if only one new image is added.</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Current Images</label>
            <div>
                <?php foreach ($images as $image): ?>
                    <img src="<?php echo $image; ?>" alt="Listing Image" class="img-fluid my-listings-image">
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="d-flex justify-content-between">
            <div>
                <button type="submit" name="update_listing" class="btn btn-outline-success me-2">Update Listing</button>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-listing-id="<?php echo htmlspecialchars($listing['id']); ?>">Delete Listing</button>
            </div>
            <a href="my_dashboard.php" class="btn btn-outline-warning">Back to Dashboard</a>
        </div>
    </form>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deleteForm" method="post" action="edit_listing.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this listing?
                    <input type="hidden" name="listing_id" id="deleteListingId">
                    <input type="hidden" name="delete_listing" value="true">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var listingId = button.getAttribute('data-listing-id');
        var deleteListingIdInput = document.getElementById('deleteListingId');
        deleteListingIdInput.value = listingId;
    });
});
</script>
    <button id="install-button" style="display: none;">Install Sharenest</button>
    
    <!-- Footer STARTS here -->
<?php include 'footer.php'; ?>
<!-- Footer ENDS here -->

</body>
</html>
