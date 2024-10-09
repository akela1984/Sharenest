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

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Path to the configuration file
$configFilePath = dirname(__DIR__) . '/config/config.ini';

// Check if the configuration file exists
if (!file_exists($configFilePath)) {
    die("Error: Configuration file not found at $configFilePath");
}

// Load configuration from config.ini located in the config directory
$config = parse_ini_file($configFilePath, true);

if ($config === false) {
    die("Error: Failed to parse configuration file at $configFilePath");
}

// Load PHPMailer
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message_sent = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['listing_id'])) {
    // Check CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $sender_id = $_SESSION['user_id'];
    $listing_id = intval($_POST['listing_id']);
    $message = htmlspecialchars(trim($_POST['message']), ENT_QUOTES, 'UTF-8');

    if (strlen($message) < 2) {
        $_SESSION['message'] = "Error: Message must be at least 2 characters long.";
        header('Location: my_nest.php');
        exit;
    }

    // Fetch the recipient_id and listing title from the listings table
    $sql = "SELECT user_id, title FROM listings WHERE id = ?";
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
    $listing_title = htmlspecialchars($listing['title'], ENT_QUOTES, 'UTF-8');

    // Fetch sender and recipient usernames
    $sql = "SELECT id, username, email FROM users WHERE id IN (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $sender_id, $recipient_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows < 2) {
        $_SESSION['message'] = "You can't send a message to yourself!";
        header('Location: my_nest.php');
        exit;
    }

    while ($user = $result->fetch_assoc()) {
        if ($user['id'] == $sender_id) {
            $sender_username = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
            $sender_email = htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8');
        } else {
            $recipient_username = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
            $recipient_email = htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8');
        }
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
            $_SESSION['message'] = "Error creating conversation: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            header('Location: my_nest.php');
            exit;
        }
    }

    // Insert the message
    $sql = "INSERT INTO messages (conversation_id, sender_id, recipient_id, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $conversation_id, $sender_id, $recipient_id, $message);
    if ($stmt->execute()) {
        // Send email notification to the recipient
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.livemail.co.uk';
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['smtp']['username'];
            $mail->Password   = $config['smtp']['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('no-reply@sharenest.org', 'Sharenest');
            $mail->addAddress($recipient_email, $recipient_username);

            // Load HTML template
            $templatePath = __DIR__ . '/templates/internal_message_template.html';
            if (!file_exists($templatePath)) {
                throw new Exception("Email template not found at $templatePath");
            }
            $template = file_get_contents($templatePath);
            $emailBody = str_replace(
                ['{{recipient_username}}', '{{sender_username}}', '{{listing_title}}', '{{message}}'],
                [$recipient_username, $sender_username, $listing_title, nl2br($message)],
                $template
            );

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'New Message Notification';
            $mail->Body    = $emailBody;

            // Embed the image
            $logoPath = __DIR__ . '/img/sharenest_logo.png';
            if (!file_exists($logoPath)) {
                throw new Exception("Logo not found at $logoPath");
            }
            $mail->addEmbeddedImage($logoPath, 'sharenest_logo');

            $mail->send();
            $_SESSION['message'] = "Message sent successfully and the recipient has been notified!";
        } catch (Exception $e) {
            $_SESSION['message'] = "Message sent but email notification failed: " . htmlspecialchars($mail->ErrorInfo, ENT_QUOTES, 'UTF-8') . ". Exception: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    } else {
        $_SESSION['message'] = "Error sending message.";
    }
    header('Location: my_nest.php');
    exit;
}

// Fetch the logged-in user's information
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

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

  <link href="css/styles.css" rel="stylesheet">
  <!-- Include Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
</head>

<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<!-- My nest Listings STARTS here -->

<div id="content" class="container">
    <h2>Available Listings</h2>
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['message']); ?></div>
    <?php endif; ?>
    <?php if (empty($locationIds)): ?>
        <div class="alert alert-info" role="alert">
            You are not part of any location. Please <a href="join_location.php" class="alert-link">join a location</a> to see listings.
        </div>
    <?php else: ?>
        <div class="filter-buttons">
            <div class="btn-group">
                <button class="btn btn-outline-success dropdown-toggle" type="button" id="locationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    All Locations
                </button>
                <ul class="dropdown-menu" aria-labelledby="locationDropdown">
                    <li><a class="dropdown-item location-option" href="#" onclick="filterByLocation('all', 'All locations')" id="location-all">All locations <span class="tick">&#10003;</span></a></li>
                    <?php
                    foreach ($locationIds as $locationId) {
                        // Fetch location details
                        $sql = "SELECT location_name FROM locations WHERE location_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $locationId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            $location = $result->fetch_assoc();
                            echo '<li><a class="dropdown-item location-option" href="#" onclick="filterByLocation(' . $locationId . ', \'' . htmlspecialchars($location['location_name'], ENT_QUOTES, 'UTF-8') . '\')" id="location-' . $locationId . '">' . htmlspecialchars($location['location_name'], ENT_QUOTES, 'UTF-8') . ' <span class="tick" style="display: none;">&#10003;</span></a></li>';
                        }
                    }
                    ?>
                    <li><a class="dropdown-item" href="join_location.php">... Join location</a></li>
                </ul>
            </div>
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
        <div id="no-results-message" class="alert alert-info" style="display: none;">No listings found for your search.</div>
        <div class="btn-container">
            <button id="load-more" class="btn btn-outline-success" style="display: none;">Show more</button>
        </div>
    <?php endif; ?>
</div>

<!-- My nest Listings ENDS here -->

<!-- Footer STARTS here -->
<?php include 'footer.php'; ?>
<!-- Footer ENDS here -->
<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Include Leaflet JavaScript -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    let offset = 0;
    const limit = 20;
    const allLocationIdsStr = "<?php echo htmlspecialchars(implode(',', $locationIds), ENT_QUOTES, 'UTF-8'); ?>";
    let currentLocationIdsStr = allLocationIdsStr;
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
        fetch(`search_suggestions.php?query=${encodeURIComponent(query)}&locationIds=${currentLocationIdsStr}`)
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
                            <span>${decodeEntities(suggestion.title)}</span>
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
        fetch(`load_more.php?offset=${offset}&limit=${limit}&locationIds=${currentLocationIdsStr}&filter=${currentFilter}&search=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(data => {
                const listingsContainer = document.getElementById('listings-container');
                if (offset === 0) {
                    listingsContainer.innerHTML = ''; // Clear previous listings if loading from the beginning
                }

                if (data.length === 0) {
                    document.getElementById('no-results-message').style.display = 'block';
                    document.getElementById('load-more').style.display = 'none';
                    return;
                } else {
                    document.getElementById('no-results-message').style.display = 'none';
                }

                data.forEach(listing => {
                    const listingBox = document.createElement('div');
                    listingBox.classList.add('listing-box');
                    if (listing.state === 'pending_collection') {
                        listingBox.classList.add('pending-collection');
                    }

                    const listingHeader = document.createElement('div');
                    listingHeader.classList.add('listing-header');

                    const listingHeaderLeft = document.createElement('div');
                    listingHeaderLeft.classList.add('listing-header-left');

                    const categoryBadge = document.createElement('span');
                    categoryBadge.classList.add('badge');
                    categoryBadge.classList.add(listing.listing_type === 'sharing' ? 'badge-sharing' : 'badge-wanted');
                    categoryBadge.textContent = listing.listing_type === 'sharing' ? 'For Sharing' : 'Wanted';

                    listingHeaderLeft.appendChild(categoryBadge);

                    if (listing.state === 'under_review') {
                        const underReviewBadge = document.createElement('span');
                        underReviewBadge.classList.add('badge-under-review');
                        underReviewBadge.innerHTML = '<span class="badge-under-review-circle">!</span><span class="badge-under-review-text">Under Review</span>';
                        listingHeaderLeft.appendChild(underReviewBadge);
                    }

                    const locationInfo = document.createElement('span');
                    locationInfo.textContent = `Location: ${decodeEntities(listing.location_name)}`;
                    locationInfo.style.marginTop = '5px';

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
                    listingTitle.textContent = decodeEntities(listing.title);

                    const listingDescription = document.createElement('div');
                    listingDescription.classList.add('listing-description');
                    listingDescription.textContent = `${decodeEntities(listing.listing_description.substring(0, 200))}...`;

                    const listingFooter = document.createElement('div');
                    listingFooter.classList.add('listing-footer');

                    const listingUser = document.createElement('span');
                    listingUser.textContent = `Listed by: ${decodeEntities(listing.username)}`;

                    const seeDetailsButton = document.createElement('button');
                    seeDetailsButton.classList.add('btn', 'btn-outline-success');
                    seeDetailsButton.textContent = 'See details';
                    seeDetailsButton.setAttribute('data-bs-toggle', 'modal');
                    seeDetailsButton.setAttribute('data-bs-target', `#modal-${listing.id}`);
                    if (listing.state === 'pending_collection') {
                        seeDetailsButton.style.display = 'none'; // Ensure the button is hidden if state is pending_collection
                    }

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

                    if (listing.state === 'pending_collection') {
                        const watermark = document.createElement('div');
                        watermark.classList.add('watermark');
                        watermark.textContent = 'Pending Collection';
                        listingBox.appendChild(watermark);
                    }

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
                                        ${decodeEntities(listing.title)}
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    ${modalBodyContent}
                                    <div class="mt-3">
                                        <p>${decodeEntities(listing.listing_description)}</p>
                                        <p><strong>Location:</strong> ${decodeEntities(listing.location_name)}</p>
                                        <p><strong>Listed by:</strong> ${decodeEntities(listing.username)}</p>
                                        <p><strong>Posted:</strong> ${timeElapsedString(listing.time_added)}</p>
                                        ${listing.postcode ? `<div id="map-${listing.id}" style="height: 400px; width: 100%;"></div>` : ''}
                                        <form action="my_nest.php" method="POST" onsubmit="return validateMessage(${listing.id});">
                                            <textarea name="message" class="form-control mt-3" placeholder="Type your message here..." minlength="2" required></textarea>
                                            <input type="hidden" name="listing_id" value="${listing.id}">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <button type="submit" class="btn btn-primary mt-2 d-none" id="send-message-${listing.id}">Send</button>
                                        </form>
                                        <p class="text-danger mt-2" id="report-listing-${listing.id}" style="cursor: pointer;">Report this listing</p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-outline-success btn-request" onclick="handleRequest(this, ${listing.id});">${buttonText}</button>
                                    <span class="text-success d-none" id="sending-${listing.id}">Sending...</span>
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

                                    const map = L.map(`map-${listing.id}`, {
                                        scrollWheelZoom: false, // Disable scroll wheel zoom
                                        dragging: false, // Disable dragging
                                    }).setView([lat, lon], 13);

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

                attachReportEventListeners();
            })
            .catch(error => console.error('Error:', error));
    }

    document.getElementById('load-more').addEventListener('click', loadListings);

    document.getElementById('filter-all').addEventListener('click', () => {
        offset = 0;
        currentFilter = 'all';
        currentLocationIdsStr = allLocationIdsStr;
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

    function filterByLocation(locationId, locationName) {
        offset = 0;
        if (locationId === 'all') {
            currentLocationIdsStr = allLocationIdsStr;
        } else {
            currentLocationIdsStr = locationId;
        }
        loadListings();

        // Update dropdown button text
        document.getElementById('locationDropdown').textContent = locationName;

        // Update tick marks
        document.querySelectorAll('.location-option .tick').forEach(tick => tick.style.display = 'none');
        document.getElementById(`location-${locationId}`).querySelector('.tick').style.display = 'inline';
    }

    // Ensure "All locations" is selected by default
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('location-all').querySelector('.tick').style.display = 'inline';
        loadListings(); // Initial load
    });

    function validateMessage(listingId) {
        const textarea = document.querySelector(`#modal-${listingId} textarea[name="message"]`);
        if (textarea.value.trim().length < 2) {
            alert("Message must be at least 2 characters long.");
            return false;
        }
        return true;
    }

    function handleRequest(button, listingId) {
        const sendButton = document.getElementById(`send-message-${listingId}`);
        const sendingText = document.getElementById(`sending-${listingId}`);

        const textarea = document.querySelector(`#modal-${listingId} textarea[name="message"]`);
        if (textarea.value.trim().length < 2) {
            alert("Message must be at least 2 characters long.");
            return;
        }

        button.classList.add('d-none');
        sendingText.classList.remove('d-none');

        sendButton.click();

        // Listen for form submission completion
        document.querySelector(`#modal-${listingId} form`).addEventListener('submit', function (event) {
            event.preventDefault(); // Prevent form from submitting normally

            // Fetch the form data
            const formData = new FormData(event.target);

            fetch('my_nest.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                sendingText.classList.add('d-none');
                button.classList.remove('d-none');

                // Handle the response from the server (e.g., show a success message, close the modal, etc.)
                console.log('Message sent:', data);
                alert('Message sent successfully.');
                const modalElement = document.getElementById(`modal-${listingId}`);
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                modalInstance.hide();
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Error sending message. Please try again.');
                sendingText.classList.add('d-none');
                button.classList.remove('d-none');
            });
        });
    }

    function reportListing(listingId) {
        const csrfToken = "<?php echo $_SESSION['csrf_token']; ?>";

        fetch('report_listing.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ listing_id: listingId, csrf_token: csrfToken })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Listing has been reported and is now under review.');
                // Optionally hide the listing or update its status in the UI
            } else {
                alert('Failed to report the listing: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error reporting the listing:', error);
            alert('An error occurred. Please try again.');
        });
    }

    function attachReportEventListeners() {
        document.querySelectorAll('[id^="report-listing-"]').forEach(reportLink => {
            reportLink.addEventListener('click', function () {
                const listingId = this.id.split('-')[2];
                if (confirm('Do you really want to report this listing?')) {
                    reportListing(listingId);
                }
            });
        });
    }

    // Attach the report event listeners after the DOM content is loaded
    document.addEventListener('DOMContentLoaded', attachReportEventListeners);

    function decodeEntities(encodedString) {
        const textArea = document.createElement('textarea');
        textArea.innerHTML = encodedString;
        return textArea.value;
    }
</script>

<button id="install-button" style="display: none;">Install Sharenest</button>
</body>
</html>
