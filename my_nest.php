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

// Fetch user's postcode
$sql = "SELECT postcode FROM users_address WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user_address = $result->fetch_assoc();
    $user_postcode = $user_address['postcode'];
} else {
    echo "<p>User address not found. Please <a href='update_address.php'>update your address</a>.</p>";
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
    <link href="https://getbootstrap.com/docs/5.3/assets/css/docs.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <style>
        .listing-box {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
            position: relative;
        }
        .listing-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .listing-header-left {
            display: flex;
            flex-direction: column;
            margin-bottom: 10px;
        }
        .listing-header-right {
            display: flex;
            align-items: center;
        }
        .listing-content {
            display: flex;
            flex-direction: column;
        }
        @media (min-width: 576px) {
            .listing-content {
                flex-direction: row;
            }
        }
        .listing-image {
            width: 100%;
            max-width: 225px;
            height: auto;
            max-height: 150px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        @media (min-width: 576px) {
            .listing-image {
                margin-right: 15px;
                margin-bottom: 0;
            }
        }
        .listing-details {
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .listing-title {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .listing-description {
            margin-top: 10px;
            color: #555;
        }
        .listing-footer {
            margin-top: auto;
            font-size: 0.9rem;
            color: #888;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .badge-sharing {
            background-color: #5cb85c;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .badge-wanted {
            background-color: #d9534f;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .btn-outline-success {
            border: 1px solid #5cb85c;
            color: #5cb85c;
            background-color: transparent;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
        }
        .btn-outline-success:hover {
            background-color: #5cb85c;
            color: #fff;
            text-decoration: none;
        }
        .modal-footer .btn-request {
            margin-left: auto;
        }
        .modal-title .badge {
            margin-right: 10px; /* Add space between badge and title */
        }
        .filter-buttons {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-start;
            gap: 10px;
        }
        .carousel-item img {
            width: 100%;
            max-width: 100%;
            height: auto;
            max-height: 400px;
            object-fit: contain;
            margin: 0 auto;
        }
        .carousel-indicators {
            bottom: -40px;
        }
        .carousel-control-prev,
        .carousel-control-next {
            filter: invert(100%);
        }
        #map {
            height: 400px;
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
    <div class="filter-buttons">
        <button id="filter-all" class="btn btn-outline-success">All</button>
        <button id="filter-sharing" class="btn btn-outline-success">For Sharing</button>
        <button id="filter-wanted" class="btn btn-outline-success">Wanted</button>
    </div>
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
    const userPostcode = "<?php echo $user_postcode; ?>";
    let currentFilter = 'all';

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
    fetch(`load_more.php?offset=${offset}&limit=${limit}&locationIds=${locationIdsStr}&filter=${currentFilter}`)
        .then(response => response.json())
        .then(data => {
            console.log(data); // Debugging line to check JSON structure
            const listingsContainer = document.getElementById('listings-container');
            if (offset === 0) {
                listingsContainer.innerHTML = ''; // Clear previous listings if loading from the beginning
            }
            data.forEach(listing => {
                const listingBox = document.createElement('div');
                listingBox.classList.add('listing-box');

                const listingHeader = document.createElement('div');
                listingHeader.classList.add('listing-header');

                const listingHeaderLeft = document.createElement('div');
                listingHeaderLeft.classList.add('listing-header-left');

                const categoryBadge = document.createElement('span');
                categoryBadge.classList.add('badge');
                categoryBadge.classList.add(listing.listing_type === 'sharing' ? 'badge-sharing' : 'badge-wanted');
                categoryBadge.textContent = listing.listing_type === 'sharing' ? 'For Sharing' : 'Wanted';

                const locationInfo = document.createElement('span');
                locationInfo.textContent = `Location: ${listing.location_name}`;
                locationInfo.style.marginTop = '5px';

                listingHeaderLeft.appendChild(categoryBadge);
                listingHeaderLeft.appendChild(locationInfo);

                const listingHeaderRight = document.createElement('div');
                listingHeaderRight.classList.add('listing-header-right');

                const timePosted = document.createElement('span');
                timePosted.textContent = `Posted ${timeElapsedString(listing.time_added)}`;

                listingHeaderRight.appendChild(timePosted);

                listingHeader.appendChild(listingHeaderLeft);
                listingHeader.appendChild(listingHeaderRight);

                const listingContent = document.createElement('div');
                listingContent.classList.add('listing-content');

                const listingImage = document.createElement('img');
                listingImage.src = listing.images[0]; // Use the first image as the thumbnail
                listingImage.alt = 'Listing Image';
                listingImage.classList.add('listing-image');

                const listingDetails = document.createElement('div');
                listingDetails.classList.add('listing-details');

                const listingTitle = document.createElement('div');
                listingTitle.classList.add('listing-title');
                listingTitle.textContent = listing.title;

                const listingDescription = document.createElement('div');
                listingDescription.classList.add('listing-description');
                listingDescription.textContent = `${listing.listing_description.substring(0, 200)}...`;

                const listingFooter = document.createElement('div');
                listingFooter.classList.add('listing-footer');

                const listingUser = document.createElement('span');
                listingUser.textContent = `Listed by: ${listing.username}`;

                const seeDetailsButton = document.createElement('button');
                seeDetailsButton.classList.add('btn', 'btn-outline-success');
                seeDetailsButton.textContent = 'See details';
                seeDetailsButton.setAttribute('data-bs-toggle', 'modal');
                seeDetailsButton.setAttribute('data-bs-target', `#modal-${listing.id}`);

                listingFooter.appendChild(listingUser);
                listingFooter.appendChild(seeDetailsButton);

                listingDetails.appendChild(listingTitle);
                listingDetails.appendChild(listingDescription);

                listingContent.appendChild(listingImage);
                listingContent.appendChild(listingDetails);

                listingBox.appendChild(listingHeader);
                listingBox.appendChild(listingContent);
                listingBox.appendChild(listingFooter);

                listingsContainer.appendChild(listingBox);

                // Create modal
                const modal = document.createElement('div');
                modal.classList.add('modal', 'fade');
                modal.id = `modal-${listing.id}`;
                modal.tabIndex = -1;
                modal.setAttribute('aria-labelledby', `modalLabel-${listing.id}`);
                modal.setAttribute('aria-hidden', 'true');

                const buttonText = listing.listing_type === 'wanted' ? 'Offer' : 'Request this';
                const badgeClass = listing.listing_type === 'sharing' ? 'badge-sharing' : 'badge-wanted';
                const badgeText = listing.listing_type === 'sharing' ? 'For Sharing' : 'Wanted';

                const images = listing.images.map((image, index) => `
                    <div class="carousel-item ${index === 0 ? 'active' : ''}">
                        <img src="${image}" class="d-block w-100" alt="Listing Image ${index + 1}">
                    </div>
                `).join('');

                modal.innerHTML = `
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <span class="badge ${badgeClass}" style="margin-right: 10px;">${badgeText}</span>
                                <h5 class="modal-title" id="modalLabel-${listing.id}">
                                    ${listing.title}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="carousel-${listing.id}" class="carousel slide" data-bs-ride="carousel">
                                    <div class="carousel-indicators">
                                        ${listing.images.map((_, index) => `
                                            <button type="button" data-bs-target="#carousel-${listing.id}" data-bs-slide-to="${index}" class="${index === 0 ? 'active' : ''}" aria-current="${index === 0 ? 'true' : 'false'}" aria-label="Slide ${index + 1}"></button>
                                        `).join('')}
                                    </div>
                                    <div class="carousel-inner">
                                        ${images}
                                    </div>
                                    <button class="carousel-control-prev" type="button" data-bs-target="#carousel-${listing.id}" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Previous</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#carousel-${listing.id}" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Next</span>
                                    </button>
                                </div>
                                <div class="mt-3">
                                    <p>${listing.listing_description}</p>
                                    <p><strong>Location:</strong> ${listing.location_name}</p>
                                    <p><strong>Listed by:</strong> ${listing.username}</p>
                                    <p><strong>Posted:</strong> ${timeElapsedString(listing.time_added)}</p>
                                    <div id="map-${listing.id}" style="height: 400px; width: 100%;"></div>
                                    <textarea class="form-control mt-3" placeholder="Request message"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-outline-success btn-request">${buttonText}</button>
                            </div>
                        </div>
                    </div>
                `;

                document.body.appendChild(modal);

                // Geocode the user's postcode and initialize the Leaflet map
                fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${userPostcode}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            const lat = data[0].lat;
                            const lon = data[0].lon;

                            const map = L.map(`map-${listing.id}`).setView([lat, lon], 13);

                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                maxZoom: 19,
                            }).addTo(map);

                            L.circle([lat, lon], {
                                color: '#5CB853',
                                fillColor: '#5CB853',
                                fillOpacity: 0.5,
                                radius: 1000 // Radius in meters
                            }).addTo(map);

                            // Invalidate map size after the modal is shown
                            const modalElement = document.getElementById(`modal-${listing.id}`);
                            modalElement.addEventListener('shown.bs.modal', () => {
                                map.invalidateSize();
                            });
                        }
                    })
                    .catch(error => console.error('Error:', error));
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

document.getElementById('filter-all').addEventListener('click', () => {
    offset = 0;
    currentFilter = 'all';
    loadListings();
});

document.getElementById('filter-sharing').addEventListener('click', () => {
    offset = 0;
    currentFilter = 'sharing';
    loadListings();
});

document.getElementById('filter-wanted').addEventListener('click', () => {
    offset = 0;
    currentFilter = 'wanted';
    loadListings();
});

loadListings(); // Initial load
</script>
</body>
</html>
