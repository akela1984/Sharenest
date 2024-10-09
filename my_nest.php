<?php
session_start();

// Redirect non-logged-in users to the sign-in page
if (!isset($_SESSION['loggedin'])) {
    header('Location: signin.php');
    exit;
}

include 'connection.php';

$message_sent = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['listing_id'])) {
    $sender_id = $_SESSION['user_id'];
    $listing_id = intval($_POST['listing_id']);
    $message = $_POST['message'];

    // Fetch the recipient_id from the listings table
    $sql = "SELECT user_id FROM listings WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $listing_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['message'] = "Error: Listing does not exist.";
        header('Location: my_nest.php');
        exit;
    }

    $listing = $result->fetch_assoc();
    $recipient_id = $listing['user_id'];

    // Debugging: Display sender_id and recipient_id
    echo "Sender ID: $sender_id<br>";
    echo "Recipient ID: $recipient_id<br>";

    // Verify both sender and recipient exist in the users table
    $sql = "SELECT id FROM users WHERE id IN (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $sender_id, $recipient_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows < 2) {
        $_SESSION['message'] = "Error: One or both users do not exist. Sender ID: $sender_id, Recipient ID: $recipient_id";
        header('Location: my_nest.php');
        exit;
    }

    // Check if a conversation already exists for this listing
    $sql = "SELECT id FROM conversations WHERE listing_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $listing_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $conversation = $result->fetch_assoc();
        $conversation_id = $conversation['id'];
    } else {
        // Create a new conversation
        $conn->begin_transaction();
        try {
            $sql = "INSERT INTO conversations (listing_id) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $listing_id);
            $stmt->execute();
            $conversation_id = $stmt->insert_id;

            // Add members to the conversation
            $sql = "INSERT INTO conversation_members (conversation_id, user_id) VALUES (?, ?), (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiii", $conversation_id, $sender_id, $conversation_id, $recipient_id);
            $stmt->execute();

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message'] = "Error creating conversation: " . $e->getMessage();
            header('Location: my_nest.php');
            exit;
        }
    }

    // Insert the message
    $sql = "INSERT INTO messages (conversation_id, sender_id, recipient_id, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $conversation_id, $sender_id, $recipient_id, $message);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Message sent successfully!";
    } else {
        $_SESSION['message'] = "Error sending message.";
    }
    header('Location: my_nest.php');
    exit;
}

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
        .modal-footer .btn-request {
            margin-left: auto;
        }
        .modal-title .badge {
            margin-right: 10px;
        }
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
        .filter-search {
            position: relative;
            flex-grow: 1;
        }
        @media (max-width: 576px) {
            .filter-buttons {
                flex-direction: column;
                align-items: flex-start;
            }
            .filter-search {
                width: 100%;
                margin-top: 10px;
            }
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
        .btn-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .search-suggestions {
            position: absolute;
            z-index: 1000;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: none;
        }
        .search-suggestions-header {
            padding: 10px;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
        }
        .search-suggestion {
            padding: 10px;
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .search-suggestion img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 10px;
        }
        .search-suggestion:hover {
            background-color: #f0f0f0;
        }
        .badge-suggestion-sharing {
            background-color: #5cb85c;
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
            margin-left: auto;
        }
        .badge-suggestion-wanted {
            background-color: #d9534f;
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
            margin-left: auto;
        }
    </style>
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<!-- My nest Listings STARTS here -->

<div class="container mt-5">
    <h2>Available Listings</h2>
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-info"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>
    <?php if (empty($locationIds)): ?>
        <div class="alert alert-info" role="alert">
            You are not part of any location. Please <a href="join_location.php" class="alert-link">join a location</a> to see listings.
        </div>
    <?php else: ?>
        <div class="filter-buttons">
            <div>
                <button id="filter-all" class="btn btn-outline-success active">All</button>
                <button id="filter-sharing" class="btn btn-outline-success">For Sharing</button>
                <button id="filter-wanted" class="btn btn-outline-success">Wanted</button>
            </div>
            <div class="filter-search">
                <input type="text" id="search-input" class="form-control" placeholder="Search listings...">
                <div id="search-suggestions" class="search-suggestions"></div>
            </div>
        </div>
        <div id="listings-container" class="mt-3">
            <!-- Listings will be loaded here -->
        </div>
        <div class="btn-container">
            <button id="load-more" class="btn btn-outline-success" style="display: none;">Show more</button>
        </div>
    <?php endif; ?>
</div>

<!-- My nest Listings ENDS here -->

<!-- Footer STARTS here -->
<?php // include 'footer.php'; ?>
<!-- Footer ENDS here -->

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let offset = 0;
    const limit = 20;
    const locationIdsStr = "<?php echo $locationIdsStr; ?>";
    let currentFilter = 'all';
    let searchTerm = '';
    const placeholderImage = 'img/listing_placeholder.jpeg';

    document.getElementById('search-input').addEventListener('input', () => {
        searchTerm = document.getElementById('search-input').value.trim();
        if (searchTerm.length > 2) {
            fetchSuggestions(searchTerm);
        } else {
            document.getElementById('search-suggestions').style.display = 'none';
        }
    });

    document.getElementById('search-input').addEventListener('keypress', (event) => {
        if (event.key === 'Enter') {
            offset = 0;
            loadListings();
            document.getElementById('search-suggestions').style.display = 'none';
        }
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.filter-search')) {
            document.getElementById('search-suggestions').style.display = 'none';
        }
    });

    function fetchSuggestions(query) {
        fetch(`search_suggestions.php?query=${encodeURIComponent(query)}&locationIds=${locationIdsStr}`)
            .then(response => response.json())
            .then(data => {
                const suggestionsContainer = document.getElementById('search-suggestions');
                suggestionsContainer.innerHTML = '';
                if (data.length > 0) {
                    const header = document.createElement('div');
                    header.classList.add('search-suggestions-header');
                    header.textContent = 'Top findings for you';
                    suggestionsContainer.appendChild(header);

                    data.forEach(suggestion => {
                        const suggestionElement = document.createElement('div');
                        suggestionElement.classList.add('search-suggestion');
                        suggestionElement.innerHTML = `
                            <img src="${suggestion.image ? suggestion.image : placeholderImage}" alt="${suggestion.title}">
                            <span>${suggestion.title}</span>
                            <span class="badge ${suggestion.listing_type === 'sharing' ? 'badge-suggestion-sharing' : 'badge-suggestion-wanted'}">
                                ${suggestion.listing_type === 'sharing' ? 'For Sharing' : 'Wanted'}
                            </span>
                        `;
                        suggestionElement.addEventListener('click', () => {
                            openModal(suggestion.id);
                            suggestionsContainer.style.display = 'none';
                        });
                        suggestionsContainer.appendChild(suggestionElement);
                    });
                    suggestionsContainer.style.display = 'block';
                } else {
                    suggestionsContainer.style.display = 'none';
                }
            });
    }

    function openModal(listingId) {
        const modalElement = document.querySelector(`#modal-${listingId}`);
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    }

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
        fetch(`load_more.php?offset=${offset}&limit=${limit}&locationIds=${locationIdsStr}&filter=${currentFilter}&search=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(data => {
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
                    listingImage.src = listing.images[0] ? listing.images[0] : placeholderImage; // Use the first image or placeholder if none available
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

                    let modalBodyContent = '';

                    if (listing.images.length > 0) {
                        const images = listing.images.map((image, index) => `
                            <div class="carousel-item ${index === 0 ? 'active' : ''}">
                                <img src="${image}" class="d-block w-100" alt="Listing Image ${index + 1}">
                            </div>
                        `).join('');

                        modalBodyContent = `
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
                        `;
                    } else {
                        modalBodyContent = `<img src="${placeholderImage}" class="d-block w-100" alt="No Image Available">`;
                    }

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
                                    ${modalBodyContent}
                                    <div class="mt-3">
                                        <p>${listing.listing_description}</p>
                                        <p><strong>Location:</strong> ${listing.location_name}</p>
                                        <p><strong>Listed by:</strong> ${listing.username}</p>
                                        <p><strong>Posted:</strong> ${timeElapsedString(listing.time_added)}</p>
                                        ${listing.postcode ? `<div id="map-${listing.id}" style="height: 400px; width: 100%;"></div>` : ''}
                                        <form action="my_nest.php" method="POST">
                                            <textarea name="message" class="form-control mt-3" placeholder="Type your message here..."></textarea>
                                            <input type="hidden" name="listing_id" value="${listing.id}">
                                            <button type="submit" class="btn btn-primary mt-2 d-none" id="send-message-${listing.id}">Send</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-outline-success btn-request" onclick="document.getElementById('send-message-${listing.id}').click();">${buttonText}</button>
                                </div>
                            </div>
                        </div>
                    `;

                    document.body.appendChild(modal);

                    if (listing.postcode) {
                        // Geocode the listing's postcode and initialize the Leaflet map
                        fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${listing.postcode}`)
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
                    }
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
        document.querySelectorAll('.filter-buttons button').forEach(btn => btn.classList.remove('active'));
        document.getElementById('filter-all').classList.add('active');
        loadListings();
    });

    document.getElementById('filter-sharing').addEventListener('click', () => {
        offset = 0;
        currentFilter = 'sharing';
        document.querySelectorAll('.filter-buttons button').forEach(btn => btn.classList.remove('active'));
        document.getElementById('filter-sharing').classList.add('active');
        loadListings();
    });

    document.getElementById('filter-wanted').addEventListener('click', () => {
        offset = 0;
        currentFilter = 'wanted';
        document.querySelectorAll('.filter-buttons button').forEach(btn => btn.classList.remove('active'));
        document.getElementById('filter-wanted').classList.add('active');
        loadListings();
    });

    loadListings(); // Initial load
</script>

</body>
</html>
