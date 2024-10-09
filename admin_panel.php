<?php
include 'session_timeout.php'; // Ensure session_start() is called here


// Check if the user has access REMOVE THIS AFTER GO LIVE
if (!isset($_SESSION['access_granted'])) {
    header('Location: comingsoon.php');
    exit();
  }
  

error_reporting(E_ALL);

// Redirect non-admin users to the homepage
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'] || $_SESSION['is_admin'] !== 'true') {
    header('Location: index.php');
    exit;
}

include 'connection.php'; // Include the connection to your database

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Load PHPMailer at the top of the file
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

// Fetch statistics
$total_users = fetch_total_count($conn, 'users');
$total_listings = fetch_total_count($conn, 'listings');
$total_conversations = fetch_total_count($conn, 'conversations');
$total_messages = fetch_total_count($conn, 'messages');

// Fetch listings by state
$sql = "SELECT state, COUNT(*) as count FROM listings GROUP BY state";
$listings_by_state = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Fetch listings by type
$sql = "SELECT listing_type, COUNT(*) as count FROM listings GROUP BY listing_type";
$listings_by_type = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Fetch messages by read status
$sql = "SELECT `read`, COUNT(*) as count FROM messages GROUP BY `read`";
$messages_by_status = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

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

// Function to send email
function send_email($to, $subject, $template, $placeholders = []) {
    global $config;

    $smtpUsername = $config['smtp']['username'];
    $smtpPassword = $config['smtp']['password'];

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.livemail.co.uk';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUsername;
        $mail->Password   = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('no-reply@sharenest.org', 'Sharenest');
        $mail->addAddress($to);

        // Load HTML template
        $templatePath = __DIR__ . "/templates/{$template}.html";
        if (!file_exists($templatePath)) {
            throw new Exception("Email template not found at $templatePath");
        }
        $templateContent = file_get_contents($templatePath);

        // Replace placeholders
        foreach ($placeholders as $key => $value) {
            $templateContent = str_replace("{{{$key}}}", htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $templateContent);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $templateContent;

        // Embed the image
        $logoPath = __DIR__ . '/img/sharenest_logo.png';
        if (!file_exists($logoPath)) {
            throw new Exception("Logo not found at $logoPath");
        }
        $mail->addEmbeddedImage($logoPath, 'sharenest_logo');

        $mail->send();
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: " . htmlspecialchars($mail->ErrorInfo));
        error_log("Exception: " . htmlspecialchars($e->getMessage()));
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
    // CSRF token validation
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

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
    // CSRF token validation
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

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
    // CSRF token validation
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $user_id = $_POST['user_id'];
    $reason = $_POST['reason'];

    // Get user email and username before deletion
    $sql = "SELECT email, username FROM users WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Delete images associated with the user's listings
    delete_listing_images($conn, $user_id);

    // Delete user and cascading deletes will handle the rest
    $sql = "DELETE FROM users WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Send email notification
    $template = $reason === 'User Requested' ? 'user_deleted_user_requested' : 'user_deleted_admin_decision';
    send_email($user['email'], 'Account Deletion Notification', $template, ['username' => $user['username']]);

    header('Location: admin_panel.php?tab=users&message=User deleted successfully');
    exit;
}

// Function to create a new user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_username'])) {
    // CSRF token validation
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $new_username = htmlspecialchars(trim($_POST['new_username']), ENT_QUOTES, 'UTF-8');
    $new_email = htmlspecialchars(trim($_POST['new_email']), ENT_QUOTES, 'UTF-8');
    $new_password = $_POST['new_password'];
    $confirmPassword = $_POST['confirmPassword'];
    $new_firstname = htmlspecialchars(trim($_POST['new_firstname']), ENT_QUOTES, 'UTF-8');
    $new_lastname = htmlspecialchars(trim($_POST['new_lastname']), ENT_QUOTES, 'UTF-8');
    $new_status = $_POST['new_status'];
    $new_is_admin = $_POST['new_is_admin'];
    $token = bin2hex(random_bytes(16));

    // Validate input
    $error = '';
    if (empty($new_username) || empty($new_email) || empty($new_password) || empty($confirmPassword)) {
        $error = "All fields are required!";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } elseif ($new_password !== $confirmPassword) {
        $error = "Passwords do not match!";
    } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/', $new_password)) {
        $error = "Password must be at least 8 characters long, include at least one letter, one number, and one special character from @$!%*#?&.";
    } else {
        // Check if username or email already exists
        $sql = "SELECT * FROM users WHERE email = ? OR username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $new_email, $new_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Username or email already taken!";
        } else {
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

            // Insert new user into the database with a token and status as inactive
            $sql = "INSERT INTO users (username, email, password, firstname, lastname, status, is_admin, token) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssss", $new_username, $new_email, $hashedPassword, $new_firstname, $new_lastname, $new_status, $new_is_admin, $token);

            if ($stmt->execute()) {
                // If the new user is inactive, send a verification email
                if ($new_status === 'inactive') {
                    send_email($new_email, 'Email Verification - Sharenest', 'register_email_template', [
                        'username' => $new_username,
                        'verification_link' => "http://sharenest.org/verify.php?token=" . urlencode($token)
                    ]);
                }
                header('Location: admin_panel.php?tab=create_user&message=User created successfully');
                exit;
            } else {
                $error = 'Failed to create user: ' . $stmt->error;
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- SEO Meta Tags -->
    <title>ShareNest - Community for Sharing Unwanted Goods in the Lothian area</title>

        <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-16S7LDQL7H"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-16S7LDQL7H');
    </script>

    <meta name="description" content="Join ShareNest, the community platform for sharing and discovering unwanted goods for free in the Lothian area. Connect with neighbours and give a second life to items you no longer need.">
    <meta name="keywords" content="share, unwanted goods, free items, community sharing, Lothian, give away, second hand, recycle, reuse">
    <meta name="robots" content="index, follow">
    <meta name="author" content="ShareNest">
    
    <!-- Web App Manifest -->
    <link rel="manifest" href="/manifest.json">

    <!-- Theme Color -->
    <meta name="theme-color" content="#4CAF50">

    <!-- iOS-specific meta tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ShareNest">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

    <!-- Icons for various devices -->
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/icon-180x180.png">
    <link rel="apple-touch-icon" sizes="192x192" href="/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="/icons/icon-512x512.png">

     <!-- Favicon for Browsers -->
 <link rel="icon" href="/img/favicon.png" type="image/png">
    <link rel="icon" href="/img/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/img/favicon.ico" type="image/x-icon">

    <!-- Icons for various devices -->
    <link rel="apple-touch-icon" sizes="180x180" href="/img/favicon.png">
    <link rel="apple-touch-icon" sizes="192x192" href="/img/favicon.png">
    <link rel="apple-touch-icon" sizes="512x512" href="/img/favicon.png">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="ShareNest - Community for Sharing Unwanted Goods in the Lothian area">
    <meta property="og:description" content="Join ShareNest, the community platform for sharing and discovering unwanted goods for free in the Lothian area. Connect with neighbours and give a second life to items you no longer need.">
    <meta property="og:image" content="/icons/icon-512x512.png">
    <meta property="og:url" content="https://www.sharenest.org">
    <meta property="og:type" content="website">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="ShareNest - Community for Sharing Unwanted Goods in the Lothian area">
    <meta name="twitter:description" content="Join ShareNest, the community platform for sharing and discovering unwanted goods for free in the Lothian area. Connect with neighbours and give a second life to items you no longer need.">
    <meta name="twitter:image" content="/icons/icon-512x512.png">

    <!-- Link to External PWA Script -->
    <script src="/js/pwa.js" defer></script>
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
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        .stats-card {
            flex: 1 1 calc(25% - 20px);
            padding: 20px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            text-align: center;
        }
        .stats-card h3 {
            margin-bottom: 20px;
            color: #5cb85c;
        }
        .stats-card p {
            font-size: 1.5rem;
        }
    </style>
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<div id="content" class="container mt-5">
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
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'create_user') ? 'active' : ''; ?>" id="create_user-tab" data-bs-toggle="tab" data-bs-target="#create_user" type="button" role="tab" aria-controls="create_user" aria-selected="<?php echo (isset($_GET['tab']) && $_GET['tab'] == 'create_user') ? 'true' : 'false'; ?>">Create User</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'stats') ? 'active' : ''; ?>" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab" aria-controls="stats" aria-selected="<?php echo (isset($_GET['tab']) && $_GET['tab'] == 'stats') ? 'true' : 'false'; ?>">Stats</button>
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
                                <td>
                                    <select name="status" class="form-select" disabled>
                                        <option value="inactive" <?php echo $user['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                    </select>
                                </td>
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
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="delete_user" value="">
                                    <div class="d-flex">
                                        <button type="button" class="btn btn-warning edit-btn me-2">Edit</button>
                                        <button type="submit" name="update_user" class="btn btn-primary save-btn d-none me-2">Save</button>
                                        <button type="button" class="btn btn-danger delete-btn d-none me-2" data-bs-toggle="modal" data-bs-target="#deleteModal" data-user-id="<?php echo htmlspecialchars($user['id']); ?>">Delete</button>
                                        <span class="processing-text text-success d-none">Processing...</span>
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
                        <?php foreach ($listings as $listing) { 
                            // Convert state and listing_type to human-readable format
                            $state_readable = ucwords(str_replace('_', ' ', $listing['state']));
                            $type_readable = ucwords(str_replace('_', ' ', $listing['listing_type']));
                        ?>
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
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="delete_listing" value="">
                                    <div class="d-flex">
                                        <button type="button" class="btn btn-warning edit-btn me-2">Edit</button>
                                        <button type="submit" name="update_listing" class="btn btn-primary save-btn d-none me-2">Save</button>
                                        <button type="button" class="btn btn-danger delete-btn d-none me-2" data-bs-toggle="modal" data-bs-target="#deleteModalListing" data-listing-id="<?php echo htmlspecialchars($listing['id']); ?>">Delete</button>
                                        <span class="processing-text text-success d-none">Processing...</span>
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

        <!-- Create User Tab -->
        <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'create_user') ? 'show active' : ''; ?>" id="create_user" role="tabpanel" aria-labelledby="create_user-tab">
            <div class="container mt-5">
                <h3>Create New User</h3>
                <?php if (isset($error) && !empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <form id="createUserForm" method="post" action="admin_panel.php?tab=create_user">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-3">
                        <label for="new_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="new_username" name="new_username" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="new_email" name="new_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <small id="passwordHelp" class="form-text text-muted">
                            Password must be at least 8 characters long, include at least one letter, one number, and one special character. 
                            Allowed special characters: @$!%*#?&.
                        </small>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_firstname" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="new_firstname" name="new_firstname" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_lastname" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="new_lastname" name="new_lastname" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_status" class="form-label">Status</label>
                        <select id="new_status" name="new_status" class="form-select" required>
                            <option value="inactive">Inactive</option>
                            <option value="active">Active</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="new_is_admin" class="form-label">Admin</label>
                        <select id="new_is_admin" name="new_is_admin" class="form-select" required>
                            <option value="false">False</option>
                            <option value="true">True</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline-success create-user-btn">Create User</button>
                    <span id="creatingUserText" class="text-success d-none">Creating user...</span>
                </form>
            </div>
        </div>

        <!-- Stats Tab -->
        <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'stats') ? 'show active' : ''; ?>" id="stats" role="tabpanel" aria-labelledby="stats-tab">
            <div class="stats-container">
                <div class="stats-card">
                    <h3>Total Users</h3>
                    <p><?php echo htmlspecialchars($total_users); ?></p>
                </div>
                <div class="stats-card">
                    <h3>Total Listings</h3>
                    <p><?php echo htmlspecialchars($total_listings); ?></p>
                </div>
                <div class="stats-card">
                    <h3>Total Conversations</h3>
                    <p><?php echo htmlspecialchars($total_conversations); ?></p>
                </div>
                <div class="stats-card">
                    <h3>Total Messages</h3>
                    <p><?php echo htmlspecialchars($total_messages); ?></p>
                </div>
                <div class="stats-card">
                    <h3>Listings by State</h3>
                    <?php foreach ($listings_by_state as $state) { ?>
                        <p><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $state['state']))) . ': ' . htmlspecialchars($state['count']); ?></p>
                    <?php } ?>
                </div>
                <div class="stats-card">
                    <h3>Listings by Type</h3>
                    <?php foreach ($listings_by_type as $type) { ?>
                        <p><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $type['listing_type']))) . ': ' . htmlspecialchars($type['count']); ?></p>
                    <?php } ?>
                </div>
                <div class="stats-card">
                    <h3>Messages by Status</h3>
                    <?php foreach ($messages_by_status as $status) { ?>
                        <p><?php echo $status['read'] ? 'Read: ' : 'Unread: '; ?><?php echo htmlspecialchars($status['count']); ?></p>
                    <?php } ?>
                </div>
            </div>
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
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="delete_user" value="true">
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Deletion:</label>
                        <select name="reason" id="reason" class="form-select" required>
                            <option value="">Select Reason</option>
                            <option value="User Requested">User Requested</option>
                            <option value="Admin Decision">Admin Decision</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger delete-btn-modal">Delete</button>
                    <span class="processing-text-modal text-success d-none">Processing...</span>
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
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="delete_listing" value="true">
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Deletion:</label>
                        <select name="reason" id="reason" class="form-select" required>
                            <option value="">Select Reason</option>
                            <option value="User Requested">User Requested</option>
                            <option value="Admin Decision">Admin Decision</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger delete-btn-modal">Delete</button>
                    <span class="processing-text-modal text-success d-none">Processing...</span>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Footer STARTS here -->
<?php include 'footer.php'; ?>
<!-- Footer ENDS here -->

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

    // Handle form submission for delete actions
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            const deleteButton = form.querySelector('.delete-btn-modal');
            const processingText = form.querySelector('.processing-text-modal');

            if (deleteButton && processingText) {
                deleteButton.classList.add('d-none');
                processingText.classList.remove('d-none');
            }
        });
    });

    // Handle form submission for creating users
    const createUserForm = document.getElementById('createUserForm');
    createUserForm.addEventListener('submit', function(event) {
        const new_password = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const errorMessage = document.getElementById('errorMessage');
        const regex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/;

        errorMessage.style.display = 'none';

        if (!regex.test(new_password)) {
            errorMessage.textContent = 'Password must be at least 8 characters long, include at least one letter, one number, and one special character from @$!%*#?&.';
            errorMessage.style.display = 'block';
            event.preventDefault();
        } else if (new_password !== confirmPassword) {
            errorMessage.textContent = 'Passwords do not match!';
            errorMessage.style.display = 'block';
            event.preventDefault();
        } else {
            const createUserButton = createUserForm.querySelector('.create-user-btn');
            const creatingUserText = document.getElementById('creatingUserText');

            createUserButton.disabled = true;
            createUserButton.style.display = 'none';
            creatingUserText.style.display = 'inline';
        }
    });
});
</script>


<button id="install-button" style="display: none;">Install Sharenest</button>
</body>
</html>
