<?php
session_start(); // Ensure session is started

// Redirect non-admin users to the homepage
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'] || $_SESSION['is_admin'] !== 'true') {
    header('Location: index.php');
    exit;
}

include 'connection.php'; // Include the connection to your database


// Function to fetch data for tables with pagination and sorting
function fetch_data($conn, $table, $page, $perPage, $sortColumn, $sortOrder) {
    $offset = ($page - 1) * $perPage;
    if ($table === 'listings') {
        $sql = "SELECT listings.*, locations.location_name, users.username 
                FROM listings 
                JOIN locations ON listings.location_id = locations.location_id 
                JOIN users ON listings.user_id = users.id 
                ORDER BY $sortColumn $sortOrder 
                LIMIT $offset, $perPage";
    } else {
        $sql = "SELECT * FROM $table ORDER BY $sortColumn $sortOrder LIMIT $offset, $perPage";
    }
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function fetch_total_count($conn, $table) {
    $sql = "SELECT COUNT(*) as count FROM $table";
    return $conn->query($sql)->fetch_assoc()['count'];
}

function fetch_under_review_count($conn) {
    $sql = "SELECT COUNT(*) as count FROM listings WHERE state = 'under_review'";
    return $conn->query($sql)->fetch_assoc()['count'];
}

// Function to delete images from the filesystem
function delete_listing_images($conn, $user_id) {
    // Fetch all listing images URLs for the user
    $sql = "SELECT listing_images.image_url 
            FROM listing_images 
            JOIN listings ON listing_images.listing_id = listings.id 
            WHERE listings.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Delete each image from the filesystem
    while ($row = $result->fetch_assoc()) {
        $image_path = $row['image_url'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
}

// Pagination and sorting for Users table
$users_page = isset($_GET['users_page']) ? (int)$_GET['users_page'] : 1;
$users_perPage = 10;
$users_sort_column = isset($_GET['users_sort_column']) ? htmlspecialchars($_GET['users_sort_column']) : 'id';
$users_sort_order = isset($_GET['users_sort_order']) ? htmlspecialchars($_GET['users_sort_order']) : 'ASC';

$users = fetch_data($conn, 'users', $users_page, $users_perPage, $users_sort_column, $users_sort_order);
$total_users = fetch_total_count($conn, 'users');
$total_users_pages = ceil($total_users / $users_perPage);

// Pagination and sorting for Listings table
$listings_page = isset($_GET['listings_page']) ? (int)$_GET['listings_page'] : 1;
$listings_perPage = 10;
$listings_sort_column = isset($_GET['listings_sort_column']) ? htmlspecialchars($_GET['listings_sort_column']) : 'id';
$listings_sort_order = isset($_GET['listings_sort_order']) ? htmlspecialchars($_GET['listings_sort_order']) : 'ASC';

$listings = fetch_data($conn, 'listings', $listings_page, $listings_perPage, $listings_sort_column, $listings_sort_order);
$total_listings = fetch_total_count($conn, 'listings');
$total_listings_pages = ceil($total_listings / $listings_perPage);

// Count listings with 'under_review' state
$under_review_count = fetch_under_review_count($conn);

// Function to update user data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $status = $_POST['status'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $green_points = $_POST['green_points'];
    $is_admin = $_POST['is_admin'];

    $sql = "UPDATE users SET username=?, email=?, status=?, firstname=?, lastname=?, green_points=?, is_admin=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssi", $username, $email, $status, $firstname, $lastname, $green_points, $is_admin, $user_id);
    $stmt->execute();
    header('Location: admin_panel.php?tab=users&message=User updated successfully');
    exit;
}

// Function to update listing data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_listing'])) {
    $listing_id = $_POST['listing_id'];
    $title = $_POST['title'];
    $listing_description = $_POST['listing_description'];
    $state = $_POST['state'];
    $listing_type = $_POST['listing_type'];

    $sql = "UPDATE listings SET title=?, listing_description=?, state=?, listing_type=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $title, $listing_description, $state, $listing_type, $listing_id);
    
    if ($stmt->execute()) {
        header('Location: admin_panel.php?tab=listings&message=Listing updated successfully');
        exit;
    } else {
        error_log('Failed to update listing: ' . $stmt->error);
        echo 'Error updating listing.';
    }
}

// Function to delete user data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];

    // Delete images associated with the user's listings
    delete_listing_images($conn, $user_id);

    // Delete user and cascading deletes will handle the rest
    $sql = "DELETE FROM users WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    header('Location: admin_panel.php?tab=users&message=User deleted successfully');
    exit;
}

// Function to delete listing data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_listing'])) {
    $listing_id = $_POST['listing_id'];

    $sql = "DELETE FROM listings WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $listing_id);
    $stmt->execute();
    header('Location: admin_panel.php?tab=listings&message=Listing deleted successfully');
    exit;
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
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        .nav-link {
            color: green !important;
        }
        .table-responsive {
            max-width: 100%;
            overflow-x: auto;
        }
        .under-review {
            background-color: lightcoral !important;
        }
    </style>
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<div class="container mt-5">
    <h2>Admin Panel</h2>
    
    <?php if ($under_review_count > 0): ?>
    <div class="alert alert-info" role="alert">
        Important, there are <?php echo $under_review_count; ?> listings reported which need to be reviewed.
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['message'])): ?>
    <div class="alert alert-success" role="alert">
        <?php echo htmlspecialchars($_GET['message']); ?>
    </div>
    <?php endif; ?>

    <!-- Tabs navigation -->
    <ul class="nav nav-tabs" id="adminTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'users') ? 'active' : ''; ?>" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="<?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'users') ? 'true' : 'false'; ?>">Users</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'listings') ? 'active' : ''; ?>" id="listings-tab" data-bs-toggle="tab" data-bs-target="#listings" type="button" role="tab" aria-controls="listings" aria-selected="<?php echo (isset($_GET['tab']) && $_GET['tab'] == 'listings') ? 'true' : 'false'; ?>">Listings</button>
        </li>
    </ul>

    <!-- Tabs content -->
    <div class="tab-content" id="adminTabContent">
        <!-- Users Tab -->
        <div class="tab-pane fade <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'users') ? 'show active' : ''; ?>" id="users" role="tabpanel" aria-labelledby="users-tab">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th><a href="?tab=users&users_sort_column=id&users_sort_order=<?php echo $users_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">ID</a></th>
                            <th><a href="?tab=users&users_sort_column=created_at&users_sort_order=<?php echo $users_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Created</a></th>
                            <th><a href="?tab=users&users_sort_column=username&users_sort_order=<?php echo $users_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Username</a></th>
                            <th><a href="?tab=users&users_sort_column=email&users_sort_order=<?php echo $users_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Email</a></th>
                            <th><a href="?tab=users&users_sort_column=status&users_sort_order=<?php echo $users_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Status</a></th>
                            <th><a href="?tab=users&users_sort_column=firstname&users_sort_order=<?php echo $users_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">First Name</a></th>
                            <th><a href="?tab=users&users_sort_column=lastname&users_sort_order=<?php echo $users_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Last Name</a></th>
                            <th><a href="?tab=users&users_sort_column=green_points&users_sort_order=<?php echo $users_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Green Points</a></th>
                            <th><a href="?tab=users&users_sort_column=is_admin&users_sort_order=<?php echo $users_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Admin</a></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user) { ?>
                        <tr>
                            <form method="post" action="admin_panel.php?tab=users" class="user-form">
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                <td><input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="form-control" disabled></td>
                                <td><input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-control" disabled></td>
                                <td><input type="text" name="status" value="<?php echo htmlspecialchars($user['status']); ?>" class="form-control" disabled></td>
                                <td><input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" class="form-control" disabled></td>
                                <td><input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" class="form-control" disabled></td>
                                <td><input type="number" name="green_points" value="<?php echo htmlspecialchars($user['green_points']); ?>" class="form-control" disabled></td>
                                <td>
                                    <select name="is_admin" class="form-select" disabled>
                                        <option value="false" <?php echo $user['is_admin'] == 'false' ? 'selected' : ''; ?>>False</option>
                                        <option value="true" <?php echo $user['is_admin'] == 'true' ? 'selected' : ''; ?>>True</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                    <input type="hidden" name="delete_user" value="">
                                    <div class="d-flex">
                                        <button type="button" class="btn btn-warning edit-btn me-2">Edit</button>
                                        <button type="submit" name="update_user" class="btn btn-primary save-btn d-none me-2">Save</button>
                                        <button type="button" class="btn btn-danger delete-btn d-none me-2" data-bs-toggle="modal" data-bs-target="#deleteModal" data-user-id="<?php echo htmlspecialchars($user['id']); ?>">Delete</button>
                                        <button type="button" class="btn btn-secondary cancel-btn d-none">Cancel</button>
                                    </div>
                                </td>
                            </form>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <nav>
                <ul class="pagination">
                    <?php if ($users_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?tab=users&users_page=<?php echo htmlspecialchars($users_page - 1); ?>&users_sort_column=<?php echo htmlspecialchars($users_sort_column); ?>&users_sort_order=<?php echo htmlspecialchars($users_sort_order); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_users_pages; $i++) { ?>
                    <li class="page-item <?php if ($users_page == $i) echo 'active'; ?>"><a class="page-link" href="?tab=users&users_page=<?php echo htmlspecialchars($i); ?>&users_sort_column=<?php echo htmlspecialchars($users_sort_column); ?>&users_sort_order=<?php echo htmlspecialchars($users_sort_order); ?>"><?php echo htmlspecialchars($i); ?></a></li>
                    <?php } ?>
                    <?php if ($users_page < $total_users_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?tab=users&users_page=<?php echo htmlspecialchars($users_page + 1); ?>&users_sort_column=<?php echo htmlspecialchars($users_sort_column); ?>&users_sort_order=<?php echo htmlspecialchars($users_sort_order); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        
        <!-- Listings Tab -->
        <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'listings') ? 'show active' : ''; ?>" id="listings" role="tabpanel" aria-labelledby="listings-tab">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th><a href="?tab=listings&listings_sort_column=id&listings_sort_order=<?php echo $listings_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">ID</a></th>
                            <th><a href="?tab=listings&listings_sort_column=time_added&listings_sort_order=<?php echo $listings_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Time Added</a></th>
                            <th><a href="?tab=listings&listings_sort_column=title&listings_sort_order=<?php echo $listings_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Title</a></th>
                            <th><a href="?tab=listings&listings_sort_column=location_name&listings_sort_order=<?php echo $listings_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Location</a></th>
                            <th><a href="?tab=listings&listings_sort_column=listing_description&listings_sort_order=<?php echo $listings_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Description</a></th>
                            <th><a href="?tab=listings&listings_sort_column=username&listings_sort_order=<?php echo $listings_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">User</a></th>
                            <th><a href="?tab=listings&listings_sort_column=state&listings_sort_order=<?php echo $listings_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">State</a></th>
                            <th><a href="?tab=listings&listings_sort_column=listing_type&listings_sort_order=<?php echo $listings_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Type</a></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listings as $listing) { ?>
                        <tr class="<?php echo $listing['state'] == 'under_review' ? 'under-review' : ''; ?>">
                            <form method="post" action="admin_panel.php?tab=listings" class="listing-form">
                                <td><?php echo htmlspecialchars($listing['id']); ?></td>
                                <td><?php echo htmlspecialchars($listing['time_added']); ?></td>
                                <td><input type="text" name="title" value="<?php echo htmlspecialchars($listing['title']); ?>" class="form-control" disabled></td>
                                <td><?php echo htmlspecialchars($listing['location_name']); ?></td>
                                <td><input type="text" name="listing_description" value="<?php echo htmlspecialchars($listing['listing_description']); ?>" class="form-control" disabled></td>
                                <td><?php echo htmlspecialchars($listing['username']); ?></td>
                                <td>
                                    <select name="state" class="form-select" disabled>
                                        <option value="available" <?php echo $listing['state'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="unavailable" <?php echo $listing['state'] == 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                                        <option value="pending_collection" <?php echo $listing['state'] == 'pending_collection' ? 'selected' : ''; ?>>Pending Collection</option>
                                        <option value="under_review" <?php echo $listing['state'] == 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="listing_type" class="form-select" disabled>
                                        <option value="sharing" <?php echo $listing['listing_type'] == 'sharing' ? 'selected' : ''; ?>>Sharing</option>
                                        <option value="wanted" <?php echo $listing['listing_type'] == 'wanted' ? 'selected' : ''; ?>>Wanted</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="listing_id" value="<?php echo htmlspecialchars($listing['id']); ?>">
                                    <input type="hidden" name="delete_listing" value="">
                                    <div class="d-flex">
                                        <button type="button" class="btn btn-warning edit-btn me-2">Edit</button>
                                        <button type="submit" name="update_listing" class="btn btn-primary save-btn d-none me-2">Save</button>
                                        <button type="button" class="btn btn-danger delete-btn d-none me-2" data-bs-toggle="modal" data-bs-target="#deleteModalListing" data-listing-id="<?php echo htmlspecialchars($listing['id']); ?>">Delete</button>
                                        <button type="button" class="btn btn-secondary cancel-btn d-none">Cancel</button>
                                    </div>
                                </td>
                            </form>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <nav>
                <ul class="pagination">
                    <?php if ($listings_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?tab=listings&listings_page=<?php echo htmlspecialchars($listings_page - 1); ?>&listings_sort_column=<?php echo htmlspecialchars($listings_sort_column); ?>&listings_sort_order=<?php echo htmlspecialchars($listings_sort_order); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_listings_pages; $i++) { ?>
                    <li class="page-item <?php if ($listings_page == $i) echo 'active'; ?>"><a class="page-link" href="?tab=listings&listings_page=<?php echo htmlspecialchars($i); ?>&listings_sort_column=<?php echo htmlspecialchars($listings_sort_column); ?>&listings_sort_order=<?php echo htmlspecialchars($listings_sort_order); ?>"><?php echo htmlspecialchars($i); ?></a></li>
                    <?php } ?>
                    <?php if ($listings_page < $total_listings_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?tab=listings&listings_page=<?php echo htmlspecialchars($listings_page + 1); ?>&listings_sort_column=<?php echo htmlspecialchars($listings_sort_column); ?>&listings_sort_order=<?php echo htmlspecialchars($listings_sort_order); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deleteForm" method="post" action="admin_panel.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this user?
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <input type="hidden" name="delete_user" value="true">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Listing Modal -->
<div class="modal fade" id="deleteModalListing" tabindex="-1" aria-labelledby="deleteModalListingLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deleteFormListing" method="post" action="admin_panel.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalListingLabel">Confirm Deletion</h5>
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
    // Get the current URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    // Get the active tab parameter
    const activeTab = urlParams.get('tab') || 'users';
    // Activate the correct tab
    const tabElement = document.querySelector(`#${activeTab}-tab`);
    const tabInstance = new bootstrap.Tab(tabElement);
    tabInstance.show();
    
    // Update the URL parameter when a tab is clicked
    document.querySelectorAll('.nav-link').forEach(function(tab) {
        tab.addEventListener('shown.bs.tab', function(event) {
            const newTab = event.target.id.split('-')[0];
            urlParams.set('tab', newTab);
            window.history.replaceState({}, '', `${window.location.pathname}?${urlParams.toString()}`);
        });
    });

    // Make fields editable and show buttons
    document.querySelectorAll('.edit-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            row.querySelectorAll('.form-control, .form-select').forEach(function(input) {
                input.disabled = false;
            });
            row.querySelector('.save-btn').classList.remove('d-none');
            row.querySelector('.delete-btn').classList.remove('d-none');
            row.querySelector('.cancel-btn').classList.remove('d-none');
            this.classList.add('d-none');
        });
    });

    // Confirm save action
    document.querySelectorAll('.save-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to save changes?')) {
                this.closest('form').submit();
            }
        });
    });

    // Confirm delete action
    document.querySelectorAll('.delete-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const listingId = this.getAttribute('data-listing-id');
            if (userId) {
                document.getElementById('deleteUserId').value = userId;
            }
            if (listingId) {
                document.getElementById('deleteListingId').value = listingId;
            }
        });
    });

    // Cancel edit action
    document.querySelectorAll('.cancel-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            row.querySelectorAll('.form-control, .form-select').forEach(function(input) {
                input.disabled = true;
            });
            row.querySelector('.save-btn').classList.add('d-none');
            row.querySelector('.delete-btn').classList.add('d-none');
            row.querySelector('.edit-btn').classList.remove('d-none');
            this.classList.add('d-none');
        });
    });
});
</script>
    <button id="install-button" style="display: none;">Install Sharenest</button>
</body>
</html>
