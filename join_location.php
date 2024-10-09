<?php
include 'session_timeout.php';

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

include 'connection.php';

// Fetch the username from the session
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

// Generate CSRF token if it doesn't exist
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
    $location_name = htmlspecialchars($_POST['location_name']);

    if (isset($_POST['join_location'])) {
        // Check if the location already exists
        $sql = "SELECT location_id FROM locations WHERE location_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $location_name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Location exists, get the location_id
            $row = $result->fetch_assoc();
            $location_id = $row['location_id'];
        } else {
            // Location does not exist, create a new entry
            $sql = "INSERT INTO locations (location_name) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $location_name);
            if ($stmt->execute()) {
                $location_id = $stmt->insert_id;
            } else {
                $error = "Failed to create new location!";
            }
        }

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

            // Fetch image URLs to delete files
            $sql_images = "SELECT image_url FROM listing_images 
                           JOIN listings ON listing_images.listing_id = listings.id 
                           WHERE listings.user_id = ? AND listings.location_id = ?";
            $stmt_images = $conn->prepare($sql_images);
            if ($stmt_images === false) {
                $error = 'Prepare failed: ' . htmlspecialchars($conn->error);
            } else {
                $stmt_images->bind_param("ii", $user['id'], $location_id);
                $stmt_images->execute();
                $result_images = $stmt_images->get_result();
                while ($row = $result_images->fetch_assoc()) {
                    $image_path = $row['image_url'];
                    if (file_exists($image_path)) {
                        unlink($image_path); // Delete the file
                    }
                }

                // Delete image records from database
                $sql_delete_images = "DELETE listing_images FROM listing_images 
                                      JOIN listings ON listing_images.listing_id = listings.id 
                                      WHERE listings.user_id = ? AND listings.location_id = ?";
                $stmt_delete_images = $conn->prepare($sql_delete_images);
                if ($stmt_delete_images === false) {
                    $error = 'Prepare failed: ' . htmlspecialchars($conn->error);
                } else {
                    $stmt_delete_images->bind_param("ii", $user['id'], $location_id);
                    $stmt_delete_images->execute();
                }
            }

            // Delete the user's listings in the location
            $sql_delete_listings = "DELETE FROM listings WHERE user_id = ? AND location_id = ?";
            $stmt_delete_listings = $conn->prepare($sql_delete_listings);
            if ($stmt_delete_listings === false) {
                $error = 'Prepare failed: ' . htmlspecialchars($conn->error);
            } else {
                $stmt_delete_listings->bind_param("ii", $user['id'], $location_id);
                $stmt_delete_listings->execute();
            }

            // Delete the user's location association
            $sql_delete_user_location = "DELETE FROM users_locations WHERE user_id = ? AND location_id = ?";
            $stmt_delete_user_location = $conn->prepare($sql_delete_user_location);
            if ($stmt_delete_user_location === false) {
                $error = 'Prepare failed: ' . htmlspecialchars($conn->error);
            } else {
                $stmt_delete_user_location->bind_param("ii", $user['id'], $location_id);
                if ($stmt_delete_user_location->execute()) {
                    // Left successfully
                    header('Location: join_location.php');
                    exit;
                } else {
                    $error = "Failed to leave location!";
                }
            }
        }
    }
}

// Fetch user's associated locations
$sql = "SELECT locations.* FROM locations 
        JOIN users_locations ON locations.location_id = users_locations.location_id 
        WHERE users_locations.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$locationsResult = $stmt->get_result();

$conn->close();
?>

<!doctype html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ShareNest - Community for Sharing Unwanted Goods in the Lothian area</title>

    <meta name="description" content="Join ShareNest, the community platform for sharing and discovering unwanted goods for free in the Lothian area. Connect with neighbours and give a second life to items you no longer need.">
    <meta name="keywords" content="share, unwanted goods, free items, community sharing, Lothian, give away, second hand, recycle, reuse">
    <meta name="robots" content="index, follow">
    <meta name="author" content="ShareNest">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#4CAF50">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ShareNest">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/icon-180x180.png">
    <link rel="apple-touch-icon" sizes="192x192" href="/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="/icons/icon-512x512.png">
    <link rel="icon" href="/img/favicon.png" type="image/png">
    <link rel="icon" href="/img/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/img/favicon.ico" type="image/x-icon">
    <meta property="og:title" content="ShareNest - Community for Sharing Unwanted Goods in the Lothian area">
    <meta property="og:description" content="Join ShareNest, the community platform for sharing and discovering unwanted goods for free in the Lothian area. Connect with neighbours and give a second life to items you no longer need.">
    <meta property="og:image" content="/icons/icon-512x512.png">
    <meta property="og:url" content="https://www.sharenest.org">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="ShareNest - Community for Sharing Unwanted Goods in the Lothian area">
    <meta name="twitter:description" content="Join ShareNest, the community platform for sharing and discovering unwanted goods for free in the Lothian area. Connect with neighbours and give a second life to items you no longer need.">
    <meta name="twitter:image" content="/icons/icon-512x512.png">

    <script src="/js/pwa.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://getbootstrap.com/docs/5.3/assets/css/docs.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    
    <script>
        function confirmLeave() {
            return confirm('Are you sure you want to leave this location? All your current existing available listings will be deleted and can\'t be redone');
        }
    </script>
    <style>
        .loading {
            display: none;
        }
        .error-message {
            color: red;
            margin-top: 10px;
        }
    </style>

</head>
<body class="bg-light">

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<div id="content" class="container mt-5">
    <div class="text-center mb-4">
        <h2 class="fw-bold">My Locations</h2>
    </div>
    <?php if (isset($error)) { echo "<div class='alert alert-danger' role='alert'>" . htmlspecialchars($error) . "</div>"; } ?>
    
    <div>
        <label for="cityName" class="form-label">Enter a city name:</label>
        <div class="input-group mb-3">
            <input type="text" id="cityName" name="cityName" class="form-control" autocomplete="off">
            <button id="searchButton" class="btn btn-outline-success">Search Cities</button>
        </div>
        <ul id="cityList" class="list-group"></ul>
        <p id="errorMessage" class="error-message" style="display: none;">We don't have your city or village. Please try another one, maybe one of your closest big cities.</p>
    </div>

    <ul class="list-group mt-4">

    <h4 class="mb-3">Already Member Of:</h4>

        <?php while ($row = $locationsResult->fetch_assoc()) { ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><?php echo htmlspecialchars($row['location_name']); ?></span>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return confirmLeave();">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="location_id" value="<?php echo htmlspecialchars($row['location_id']); ?>">
                    <button type="submit" name="leave_location" class="btn btn-outline-danger btn-sm">Leave</button>
                </form>
            </li>
        <?php } ?>
    </ul>
</div>

<!-- Footer STARTS here -->
<?php include 'footer.php'; ?>
<!-- Footer ENDS here -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<button id="install-button" style="display: none;">Install ShareNest</button>
<script>
    const apiUrl = 'https://secure.geonames.org/searchJSON';
    const username = 'sharenest'; // Replace with your GeoNames username

    const searchButton = document.getElementById('searchButton');
    const cityNameInput = document.getElementById('cityName');
    const cityList = document.getElementById('cityList');
    const errorMessage = document.getElementById('errorMessage');

    function performSearch() {
        const cityName = cityNameInput.value.trim();
        if (cityName.length === 0) {
            alert('Please enter a city name.');
            return;
        }

        cityList.innerHTML = '<li class="loading">Loading...</li>';

        fetch(`${apiUrl}?name_startsWith=${cityName}&country=GB&featureClass=P&featureCode=PPL*&maxRows=10&style=SHORT&username=${username}`)
            .then(response => response.json())
            .then(data => {
                console.log(data); // Debug statement
                cityList.innerHTML = '';
                if (data.geonames.length === 0) {
                    errorMessage.style.display = 'block';
                } else {
                    errorMessage.style.display = 'none';

                    const uniquePlaces = new Set();
                    data.geonames.forEach(place => {
                        if (!uniquePlaces.has(place.name)) {
                            uniquePlaces.add(place.name);
                            const li = document.createElement('li');
                            li.className = 'list-group-item d-flex justify-content-between align-items-center';
                            li.innerHTML = `<span>${place.name}</span>
                                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="location_name" value="${place.name}">
                                                <button type="submit" name="join_location" class="btn btn-outline-success btn-sm">Join</button>
                                            </form>`;
                            cityList.appendChild(li);
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error fetching data:', error);
                errorMessage.textContent = 'Error fetching data. Please try again later.';
                errorMessage.style.display = 'block';
            });
    }

    searchButton.addEventListener('click', performSearch);
    cityNameInput.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            performSearch();
        }
    });
</script>
</body>
</html>
