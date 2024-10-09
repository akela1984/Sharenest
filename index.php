<?php
include 'session_timeout.php';
include 'connection.php'; 

// Redirect to the comingsoon page if access is not granted
if (!isset($_SESSION['access_granted'])) {
    header('Location: comingsoon.php');
    exit();
}

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
$firstname = $isLoggedIn ? $_SESSION['firstname'] : '';
$displayName = !empty($firstname) ? $firstname : $username;
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

    <!-- Link to External PWA Script -->
    <script src="/js/pwa.js" defer></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://getbootstrap.com/docs/5.3/assets/css/docs.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        .hero-section {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
            position: relative;
            align-items: center; /* Center vertically */
        }
        .hero-section .col {
            padding-right: 0;
            padding-left: 0;
        }
        .hero-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: opacity 1s ease-in-out;
        }
        .hero-text {
            position: absolute;
            top: 50%;
            left: 20px;
            width: 60%;
            max-width: calc(33.33% * 3 - 20px); /* Max width to cover up to the end of the third image */
            transform: translateY(-50%); /* Center vertically */
            background-color: rgba(92, 184, 92, 0.8); /* Green transparent background */
            color: white;
            padding: 20px;
            border-radius: 5px;
            z-index: 10; /* Ensure the text is on top of the images */
            box-sizing: border-box;
            overflow-wrap: break-word; /* Ensure text does not overflow */
        }
        @media (max-width: 767.98px) {
            .hero-section .second-row {
                display: none;
            }
            .hero-section .col-6 {
                max-width: 33.33%;
                flex: 0 0 33.33%;
            }
            .hero-text {
                top: 20px;
                left: 10px;
                width: calc(100% - 20px);
                max-width: none;
                transform: none;
                padding: 10px;
            }
        }
        .register-section {
            padding: 60px 20px;
            text-align: center;
            background-color: #f8f9fa;
        }
        .register-section h2 {
            margin-bottom: 20px;
        }
        .register-section p {
            margin-bottom: 30px;
        }
        .register-section .btn-register, .register-section .btn-mynest {
            background-color: #5cb85c;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-transform: uppercase;
        }
    </style>
</head>

<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->
<div id="content">

<!-- Hero Section STARTS here -->
<div class="container-fluid px-0 mt-3">
    <div class="row hero-section">
        <div class="hero-text">
            <h1><?php echo $isLoggedIn ? 'Welcome back, ' . htmlspecialchars($displayName) : 'Welcome to ShareNest'; ?></h1>
            <p>Discover and share amazing free items.</p>
        </div>
        <?php
        // Fetch available listings and their images from the database
        $query = "SELECT li.image_url 
                  FROM listings l 
                  INNER JOIN listing_images li ON l.id = li.listing_id 
                  WHERE l.state = 'available'";
        $result = mysqli_query($conn, $query);
        $images = [];

        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $images[] = $row['image_url'];
            }

            // Output the first 12 images for initial display
            for ($i = 0; $i < 12; $i++) {
                $rowClass = $i < 6 ? 'first-row' : 'second-row'; // Assign row class based on index
                echo '
                <div class="col-6 col-md-2 p-0 ' . $rowClass . '">
                    <div class="card border-0">
                        <img src="' . htmlspecialchars($images[$i]) . '" class="card-img" alt="Listing Image">
                    </div>
                </div>';
            }
        } else {
            echo '<p>No images available.</p>';
        }

        // Close the connection
        mysqli_close($conn);
        ?>
    </div>
</div>
<!-- Hero Section ENDS here -->

<!-- Register Section STARTS here -->
<div class="container register-section">
    <h2><?php echo $isLoggedIn ? 'Welcome Back to Sharenest' : 'Join ShareNest Today'; ?></h2>
    <p><?php echo $isLoggedIn ? 'Browse and see the free items listings available to you.' : 'Sign up now to start discovering and sharing amazing free items.'; ?></p>
    <a href="<?php echo $isLoggedIn ? 'my_nest.php' : 'register.php'; ?>" class="btn <?php echo $isLoggedIn ? 'btn-mynest' : 'btn-register'; ?>">
        <?php echo $isLoggedIn ? 'My Nest' : 'Register Now'; ?>
    </a>
</div>
<!-- Register Section ENDS here -->

</div>
<!-- Footer STARTS here -->
<?php // include 'footer.php'; ?>
<!-- Footer ENDS here -->
<script>
    const images = <?php echo json_encode($images); ?>;
    const imageElements = document.querySelectorAll('.hero-section .card-img');

    function changeRandomImages() {
        const indexes = Array.from({ length: imageElements.length }, (_, i) => i);
        const randomIndexes = indexes.sort(() => 0.5 - Math.random()).slice(0, 3);

        randomIndexes.forEach(index => {
            const randomImage = images[Math.floor(Math.random() * images.length)];
            imageElements[index].style.opacity = 0; // Fade out

            setTimeout(() => {
                imageElements[index].src = randomImage;
                imageElements[index].style.opacity = 1; // Fade in
            }, 1000);
        });
    }

    setInterval(changeRandomImages, 3000);
</script>
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/service-worker.js').then(registration => {
        console.log('ServiceWorker registration successful with scope: ', registration.scope);
      }, error => {
        console.log('ServiceWorker registration failed: ', error);
      });
    });
  }
</script>
<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Footer STARTS here -->
<?php include 'footer.php'; ?>
<!-- Footer ENDS here -->

    <button id="install-button" style="display: none;">Install Sharenest</button>
</body>
</html>
