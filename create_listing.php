<?php
session_start();

// Redirect non-logged-in users to the sign-in page
if (!isset($_SESSION['loggedin'])) {
    header('Location: signin.php');
    exit;
}

include 'connection.php';

$username = $_SESSION['username'];
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    die("User not found!");
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$uploadDir = 'uploads/listing_images/';
$listingCreated = false;
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $title = trim($_POST['title']);
    $location_id = intval($_POST['location_id']);
    $listing_description = trim($_POST['listing_description']);
    $listing_type = $_POST['listing_type'];

    // Validate inputs
    if (empty($title) || empty($listing_description) || empty($listing_type) || empty($location_id)) {
        $errors[] = "All fields are required.";
    }

    if (!in_array($listing_type, ['sharing', 'wanted'])) {
        $errors[] = "Invalid listing type.";
    }

    // Handle image uploads
    $imageUrls = [];
    if (!empty($_FILES['images']['name'][0])) {
        if (count($_FILES['images']['name']) > 5) {
            $errors[] = "You can upload a maximum of 5 images.";
        } else {
            foreach ($_FILES['images']['name'] as $key => $imageName) {
                $imageTmpName = $_FILES['images']['tmp_name'][$key];
                $imageExtension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
                $allowedFormats = ['jpg', 'jpeg', 'png', 'gif'];

                if (!in_array($imageExtension, $allowedFormats)) {
                    $errors[] = "Invalid file format for image: $imageName. Only jpg, jpeg, png, and gif are allowed.";
                } else {
                    // Generate a unique image name based on username, date, listing ID, and image number
                    $newImageName = $user['username'] . '_' . date('YmdHis') . '_' . uniqid() . '.' . $imageExtension;
                    $targetFilePath = $uploadDir . $newImageName;

                    if (move_uploaded_file($imageTmpName, $targetFilePath)) {
                        $imageUrls[] = $targetFilePath;
                    } else {
                        $errors[] = "Failed to upload image: $imageName.";
                    }
                }
            }
        }
    }

    // Proceed if there are no errors
    if (empty($errors)) {
        $sql = "INSERT INTO listings (title, location_id, listing_description, listing_type, state, user_id, time_added) VALUES (?, ?, ?, ?, 'available', ?, NOW())";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        } else {
            $stmt->bind_param("sisii", $title, $location_id, $listing_description, $listing_type, $user['id']);
            if ($stmt->execute()) {
                $listingId = $stmt->insert_id;

                // Insert image URLs into listing_images table
                foreach ($imageUrls as $imageUrl) {
                    $sql = "INSERT INTO listing_images (listing_id, image_url) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("is", $listingId, $imageUrl);
                    $stmt->execute();
                }

                $listingCreated = true;
            } else {
                $errors[] = "Failed to create listing: " . $stmt->error;
            }
        }
    }
}

$conn->close();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ShareNest - New Listing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">ShareNest</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item spacer">
                    <a class="btn btn-outline-success" href="my_nest.php">My Nest</a>
                </li>
                <li class="nav-item spacer">
                    <a class="btn btn-outline-success" href="create_listing.php">Create Listing</a>
                </li>
                <li class="nav-item spacer"></li>
                <?php if (isset($_SESSION['loggedin'])) { ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="me-2">
                                <i class="fa fa-user"></i>
                            </span>
                            Hi, <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="nav-item"><a class="nav-link" href="profile.php">My Profile</a></li>
                            <li class="nav-item"><a class="nav-link" href="join_location.php">My Locations</a></li>
                            <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php } else { ?>
                    <li class="nav-item">
                        <a class="btn btn-outline-success" href="signin.php">Sign in</a>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
</nav>
<!-- Navbar ENDS here -->

<!-- Listing Creation Form STARTS here -->
<div class="container mt-5">
    <div class="col-md-8 offset-md-2">
        <h2>Create New Listing</h2>
        <?php if (!empty($errors)) { ?>
            <div class='alert alert-danger' role='alert'>
                <?php foreach ($errors as $error) {
                    echo htmlspecialchars($error) . "<br>";
                } ?>
            </div>
        <?php } ?>
        <?php if ($listingCreated) { ?>
            <div class='alert alert-success' role='alert'>Listing created successfully!</div>
        <?php } ?>
        <?php
        // Check if the user has associated locations
        include 'connection.php';
        $sql = "SELECT COUNT(*) AS location_count FROM users_locations WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $locationCount = $row['location_count'];
        $conn->close();

        if ($locationCount > 0) {
        ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="mb-3">
                    <label for="title" class="form-label">Title:</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="mb-3">
                    <label for="listing_type" class="form-label">Listing Type:</label>
                    <select class="form-select" id="listing_type" name="listing_type" required>
                        <option value="sharing">For Sharing</option>
                        <option value="wanted">Wanted</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="location" class="form-label">Location:</label>
                    <select class="form-select" id="location" name="location_id" required>
                        <option value="" disabled selected>Select Location</option>
                        <!-- Populate with locations where the user belongs -->
                        <?php
                        include 'connection.php';
                        $sql = "SELECT l.* FROM locations l INNER JOIN users_locations ul ON l.location_id = ul.location_id WHERE ul.user_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $user['id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($row['location_id']) . "'>" . htmlspecialchars($row['location_name']) . "</option>";
                            }
                        }
                        $conn->close();
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="listing_description" class="form-label">Description:</label>
                    <textarea class="form-control" id="listing_description" name="listing_description" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="images" class="form-label">Images:</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*" required>
                    <div id="imageHelp" class="form-text">You can upload up to 5 images.</div>
                </div>
                <button type="submit" class="btn btn-primary">Create Listing</button>
            </form>
        <?php
        } else {
            echo "<p>You are not associated with any location. Please <a href='join_location.php'>join a location</a> to create a listing.</p>";
        }
        ?>
    </div>
</div>
<!-- Listing Creation Form ENDS here -->

<!-- Footer STARTS here -->
<footer class="text-white py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5>About Us</h5>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam ut tortor nisi. In hac habitasse platea dictumst.</p>
            </div>
            <div class="col-md-6">
                <h5>Contact Us</h5>
                <ul class="list-unstyled">
                    <li>Email: info@yoursite.com</li>
                    <li>Phone: +123-456-7890</li>
                </ul>
            </div>
        </div>
    </div>
</footer>
<!-- Footer ENDS here -->

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
