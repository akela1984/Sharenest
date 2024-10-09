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
    $error = "User not found!";
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle join or leave actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $location_id = intval($_POST['location_id']);
    
    if (isset($_POST['join_location'])) {
        // Check if the user is already a member of the location
        $sql = "SELECT * FROM users_locations WHERE user_id = ? AND location_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user['id'], $location_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            // User is not a member, so join the location
            $sql = "INSERT INTO users_locations (user_id, location_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $user['id'], $location_id);
            if ($stmt->execute()) {
                // Joined successfully
                header('Location: join_location.php');
                exit;
            } else {
                $error = "Failed to join location!";
            }
        }
    } elseif (isset($_POST['leave_location'])) {
        // Check if the user is a member of the location
        $sql = "SELECT * FROM users_locations WHERE user_id = ? AND location_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user['id'], $location_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User is a member, so leave the location
            $sql = "DELETE FROM users_locations WHERE user_id = ? AND location_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $user['id'], $location_id);
            if ($stmt->execute()) {
                // Left successfully
                header('Location: join_location.php');
                exit;
            } else {
                $error = "Failed to leave location!";
            }
        }
    }
}

// Fetch all locations from the database
$sql = "SELECT * FROM locations";
$result = $conn->query($sql);

// Fetch user's associated locations
$userLocations = array();
$sql = "SELECT location_id FROM users_locations WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$userResult = $stmt->get_result();
while ($row = $userResult->fetch_assoc()) {
    $userLocations[] = $row['location_id'];
}

$conn->close();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ShareNest - Join Location</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">ShareNest</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
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
                <?php if(isset($_SESSION['loggedin'])) { ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                           aria-expanded="false">
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

<!-- Join Location Form STARTS here -->

<div class="container mt-5 location-container">
    <h2>Join Location</h2>
    <?php if (isset($error)) { echo "<div class='alert alert-danger' role='alert'>" . htmlspecialchars($error) . "</div>"; } ?>
    <div class="row">
        <?php while ($row = $result->fetch_assoc()) { ?>
            <div class="col-md-3 mb-3">
                <div class="location-box">
                    <h5 class="card-title"><?php echo htmlspecialchars($row['location_name']); ?></h5>
                    <br>
                    <?php if (in_array($row['location_id'], $userLocations)) { ?>
                        <!-- If user is already a member, show leave button -->
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="location_id" value="<?php echo htmlspecialchars($row['location_id']); ?>">
                            <button type="submit" name="leave_location" class="btn btn-outline-danger">Leave</button>
                        </form>
                    <?php } else { ?>
                        <!-- If user is not a member, show join button -->
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="location_id" value="<?php echo htmlspecialchars($row['location_id']); ?>">
                            <button type="submit" name="join_location" class="btn btn-outline-success">Join</button>
                        </form>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<!-- Join Location Form ENDS here -->

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
