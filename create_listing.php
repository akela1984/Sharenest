  <?php
include 'session_timeout.php';
include 'connection.php';


// Check if the user has access REMOVE THIS AFTER GO LIVE
if (!isset($_SESSION['access_granted'])) {
    header('Location: comingsoon.php');
    exit();
  }
  
  

// Redirect non-logged-in users to the sign-in page
if (!isset($_SESSION['loggedin'])) {
    header('Location: signin.php');
    exit;
}

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success_message = '';
$error_message = '';

// Fetch user's associated locations
$userLocations = array();
$sql = "SELECT l.location_id, l.location_name FROM users_locations ul JOIN locations l ON ul.location_id = l.location_id WHERE ul.user_id = (SELECT id FROM users WHERE username = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$userResult = $stmt->get_result();
while ($row = $userResult->fetch_assoc()) {
    $userLocations[] = $row;
}

if (empty($userLocations)) {
    $error_message = "You are not associated with any location. Please <a href='join_location.php' class='alert-link'>join a location</a> to create a listing.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($userLocations)) {
    // Check CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $title = $_POST['title'];
    $location_id = $_POST['location_id'];
    $listing_description = $_POST['listing_description'];
    $listing_type = $_POST['listing_type'];
    $state = 'available'; // Set default state to available
    
    // Fetch user_id from session
    $sql_user = "SELECT id FROM users WHERE username = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("s", $_SESSION['username']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user = $result_user->fetch_assoc();
    $user_id = $user['id'];

    // Insert new listing
    $sql_insert = "
        INSERT INTO listings (title, location_id, listing_description, user_id, state, listing_type) 
        VALUES (?, ?, ?, ?, ?, ?)
    ";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("sisiss", $title, $location_id, $listing_description, $user_id, $state, $listing_type);
    
    if ($stmt_insert->execute()) {
        $listing_id = $stmt_insert->insert_id;
        $success_message = "Listing created successfully. You have earned a green point!";

        // Update user green points
        $sql_update_points = "UPDATE users SET green_points = green_points + 1 WHERE id = ?";
        $stmt_update_points = $conn->prepare($sql_update_points);
        $stmt_update_points->bind_param("i", $user_id);
        $stmt_update_points->execute();

        // Handle image upload
        if (!empty($_FILES['images']['name'][0])) {
            $upload_dir = 'uploads/listing_images/';
            $username = $_SESSION['username'];
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                $file_ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                $new_file_name = $username . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(5)) . '.' . $file_ext;
                $file_path = $upload_dir . $new_file_name;
                if (move_uploaded_file($tmp_name, $file_path)) {
                    $sql_insert_image = "INSERT INTO listing_images (listing_id, image_url) VALUES (?, ?)";
                    $stmt_insert_image = $conn->prepare($sql_insert_image);
                    $stmt_insert_image->bind_param("is", $listing_id, $file_path);
                    $stmt_insert_image->execute();
                }
            }
        }

    } else {
        $error_message = "Error creating listing.";
    }
}

$conn->close();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
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

    <!-- Stylesheets and Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="css/styles.css" rel="stylesheet">

    <style>
        .alert-container {
            max-width: 800px;
            margin: 0 auto;
        }
    </style>
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<div id="content" class="container edit-listing-container">
    <h2 class="edit-listing-title">Create Listing</h2>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-container" role="alert">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-info alert-container" role="alert">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($userLocations)) { ?>
        <form action="create_listing.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            
            <div class="mb-3">
                <label for="listing_description" class="form-label">Description <span class="text-danger">*</span></label>
                <textarea class="form-control" id="listing_description" name="listing_description" rows="4" required></textarea>
            </div>

            <div class="mb-3">
                <label for="location_id" class="form-label">Location <span class="text-danger">*</span></label>
                <select class="form-select" id="location_id" name="location_id" required>
                    <?php foreach ($userLocations as $location) { ?>
                        <option value="<?php echo htmlspecialchars($location['location_id']); ?>"><?php echo htmlspecialchars($location['location_name']); ?></option>
                    <?php } ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="listing_type" class="form-label">Type <span class="text-danger">*</span></label>
                <select class="form-select" id="listing_type" name="listing_type" required>
                    <option value="sharing">For Sharing</option>
                    <option value="wanted">Wanted</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="images" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top" title="You can upload up to 5 images.">Upload Images</label>
                <input type="file" class="form-control" id="images" name="images[]" multiple>
                <small class="form-text text-muted">You can upload up to 5 images.</small>
            </div>
            
            <!-- Hidden field for state -->
            <input type="hidden" name="state" value="available">

            <button type="submit" class="btn btn-outline-success">Create Listing</button>
        </form>
    <?php } ?>
</div>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
</script>
<button id="install-button" style="display: none;">Install Sharenest</button>
<!-- Footer STARTS here -->
<?php include 'footer.php'; ?>
<!-- Footer ENDS here -->
</body>
</html>
