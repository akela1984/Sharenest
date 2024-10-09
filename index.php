<?php
session_start(); // Start session

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
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

<?php include 'connection.php'; ?>

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<!-- Register Modal STARTS here -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="registerModalLabel">Register</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Create your account by filling out the form below -->
                <form method="post" action="register.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="mb-3">
                        <label for="registerUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="registerUsername" name="username" aria-describedby="usernameHelp" required>
                        <div id="usernameHelp" class="form-text">Choose a unique username.</div>
                    </div>
                    <div class="mb-3">
                        <label for="registerEmail" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="registerEmail" name="email" aria-describedby="emailHelp" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$" required>
                        <div id="emailHelp" class="form-text">Enter a valid email address. (e.g., example@example.com)</div>
                    </div>
                    <div class="mb-3">
                        <label for="registerPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="registerPassword" name="password" aria-describedby="passwordHelp" pattern="(?=.*\d)(?=.*[!@#$%^&*])(?=.*[a-z])(?=.*[A-Z]).{8,}" required>
                        <div id="passwordHelp" class="form-text">Password must be at least 8 characters long and include at least one number, one uppercase and lowercase letter, and one special character (e.g., !@#$%^&*)</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" aria-describedby="confirmPasswordHelp" required>
                        <div id="confirmPasswordHelp" class="form-text">Confirm your password.</div>
                    </div>
                    <!-- Click the button below to create your account -->
                    <button type="submit" class="btn btn-outline-primary">Register</button>
                </form>
            </div>
            <div class="modal-footer">
                <!-- Already have an account? Sign in here -->
                <p>Already have an account? <a href="#signInModal" data-bs-toggle="modal" data-bs-dismiss="modal">Sign in here</a></p>
            </div>
        </div>
    </div>
</div>
<!-- Register Modal ENDS here -->

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
    <button id="install-button" style="display: none;">Install Sharenest</button>
</body>
</html>
