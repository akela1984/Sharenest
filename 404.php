<?php
include 'session_timeout.php'; // Ensure session_start() is called here

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 Not Found</title>

        <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-16S7LDQL7H"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-16S7LDQL7H');
    </script>

    <!-- SEO Meta Tags -->
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
    <style>
        /* Custom styles for 404 page */
        .error-404 {
            font-size: 100px;
            color: #28a745;
        }
        .funny-text {
            font-size: 24px;
            color: #28a745;
            margin-top: 20px;
        }
    </style>
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">
    <!-- Navbar STARTS here -->
    <?php include 'navbar.php'; ?>
    <!-- Navbar ENDS here -->

    <div id="content" class="container">
        <div class="row">
            <div class="col-md-12 text-center">
                <h1 class="error-404">404</h1>
                <p class="funny-text">Oops! The page you're looking for might have been recycled. 🌱♻️</p>
                <p>We're so good at recycling, even our pages get reused! Try going back to the <a href="index.php">Homepage</a>.</p>
            </div>
        </div>
    </div>

    <!-- Footer STARTS here -->
    <?php include 'footer.php'; ?>
    <!-- Footer ENDS here -->

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Include Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</body>
</html>
