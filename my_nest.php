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
    echo "<p>User not found. Please <a href='signin.php'>sign in</a> again.</p>";
    exit;
}

// Fetch locations the user is part of
$sql = "SELECT location_id FROM users_locations WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();

$locationIds = [];
while ($row = $result->fetch_assoc()) {
    $locationIds[] = $row['location_id'];
}

if (empty($locationIds)) {
    echo "<p>You are not part of any location. Please <a href='join_location.php'>join a location</a> to see listings.</p>";
    exit;
}

$locationIdsStr = implode(',', $locationIds);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ShareNest - My Nest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        .listing-box {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            background-color: #f9f9f9; /* Optional: for better visibility */
        }
        .listing-image {
            width: 150px;
            height: auto;
            border-radius: 10px;
            margin-right: 15px;
        }
        .listing-details {
            flex: 1;
        }
        .listing-title {
            font-size: 1.5rem;
            margin-top: 10px;
            font-weight: bold; /* Optional: for emphasis */
        }
        .listing-description {
            margin-top: 10px;
            color: #555; /* Optional: for better readability */
        }
        .listing-footer {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #888;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-outline-primary {
            border: 1px solid #007bff;
            color: #007bff;
            background-color: transparent;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
        }
        .btn-outline-primary:hover {
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
        }
    </style>
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
                <!-- Add a spacer -->
                <li class="nav-item spacer"></li>
                <!-- Spacer end -->
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

<!-- My nest Listings STARTS here -->

    <div class="container mt-5">
        <h2>Available Listings</h2>
        <div id="listings-container">
            <!-- Listings will be loaded here -->
        </div>
        <button id="load-more" class="btn btn-outline-success" style="display: none;">Show more</button>
    </div>

<!-- My nest Listings ENDS here -->

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
    <script>
        let offset = 0;
        const limit = 20;
        const locationIdsStr = "<?php echo $locationIdsStr; ?>";

        function timeElapsedString(datetime) {
            const now = new Date();
            const past = new Date(datetime);
            const diff = now - past;

            const units = [
                { label: 'year', value: 1000 * 60 * 60 * 24 * 365 },
                { label: 'month', value: 1000 * 60 * 60 * 24 * 30 },
                { label: 'week', value: 1000 * 60 * 60 * 24 * 7 },
                { label: 'day', value: 1000 * 60 * 60 * 24 },
                { label: 'hour', value: 1000 * 60 * 60 },
                { label: 'minute', value: 1000 * 60 },
                { label: 'second', value: 1000 },
            ];

            for (const unit of units) {
                const elapsed = Math.floor(diff / unit.value);
                if (elapsed > 0) {
                    return `${elapsed} ${unit.label}${elapsed > 1 ? 's' : ''} ago`;
                }
            }
            return 'just now';
        }

        function loadListings() {
            fetch(`load_more.php?offset=${offset}&limit=${limit}&locationIds=${locationIdsStr}`)
                .then(response => response.json())
                .then(data => {
                    const listingsContainer = document.getElementById('listings-container');
                    data.forEach(listing => {
                        const listingBox = document.createElement('div');
                        listingBox.classList.add('listing-box');

                        const listingImage = document.createElement('img');
                        listingImage.src = listing.image_url;
                        listingImage.alt = 'Listing Image';
                        listingImage.classList.add('listing-image');

                        const listingDetails = document.createElement('div');
                        listingDetails.classList.add('listing-details');

                        const listingTitle = document.createElement('div');
                        listingTitle.classList.add('listing-title');
                        listingTitle.textContent = listing.title;

                        const listingDescription = document.createElement('div');
                        listingDescription.classList.add('listing-description');
                        listingDescription.textContent = `${listing.description.substring(0, 200)}...`;

                        const listingFooter = document.createElement('div');
                        listingFooter.classList.add('listing-footer');

                        const timePosted = document.createElement('span');
                        timePosted.textContent = `Posted ${timeElapsedString(listing.time_added)}`;

                        const seeDetailsButton = document.createElement('a');
                        seeDetailsButton.href = `listing_details.php?id=${listing.id}`;
                        seeDetailsButton.classList.add('btn', 'btn-outline-success');
                        seeDetailsButton.textContent = 'See details';

                        listingFooter.appendChild(timePosted);
                        listingFooter.appendChild(seeDetailsButton);

                        listingDetails.appendChild(listingTitle);
                        listingDetails.appendChild(listingDescription);
                        listingDetails.appendChild(listingFooter);

                        if (listing.image_url) {
                            listingBox.appendChild(listingImage);
                        }
                        listingBox.appendChild(listingDetails);

                        listingsContainer.appendChild(listingBox);
                    });

                    offset += limit;
                    if (data.length < limit) {
                        document.getElementById('load-more').style.display = 'none';
                    } else {
                        document.getElementById('load-more').style.display = 'block';
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        document.getElementById('load-more').addEventListener('click', loadListings);
        loadListings(); // Initial load
    </script>
</body>
</html>
