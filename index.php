<?php
session_start(); // Start session

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include 'connection.php'; 
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ShareNest - Coming Soon</title>

    <!-- Web App Manifest -->
    <link rel="manifest" href="/manifest.json">

    <!-- Theme Color -->
    <meta name="theme-color" content="#4CAF50">

    <!-- iOS-specific meta tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Sharenest">
    <link rel="apple-touch-icon" href="/img/favicon.png">

    <!-- Icons for various devices -->
    <link rel="apple-touch-icon" sizes="180x180" href="/img/favicon.png">
    <link rel="apple-touch-icon" sizes="192x192" href="/img/favicon.png">
    <link rel="apple-touch-icon" sizes="512x512" href="/img/favicon.png">

    <!-- Favicon for Browsers -->
    <link rel="icon" href="/img/favicon.png" type="image/png">
    <link rel="icon" href="/img/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/img/favicon.ico" type="image/x-icon">

    <!-- Link to External PWA Script -->
    <script src="/js/pwa.js" defer></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://getbootstrap.com/docs/5.3/assets/css/docs.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>

<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<div id="content" class="container mt-5 d-flex justify-content-center">
</div>

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->


<!-- Footer STARTS here -->
<?php // include 'footer.php'; ?>
<!-- Footer ENDS here -->
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
