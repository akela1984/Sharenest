<?php
session_start();

// Redirect logged-in users to the homepage
if (isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit;
}

include 'connection.php';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    // Basic rate limiting
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }
    $_SESSION['login_attempts'] += 1;

    if ($_SESSION['login_attempts'] > 5) {
        die("Too many login attempts. Please try again later.");
    }

    // Sanitize user input
    $usernameOrEmail = htmlspecialchars($_POST['usernameOrEmail']);
    $password = htmlspecialchars($_POST['password']);

    // Use prepared statements to prevent SQL injection
    $sql = "SELECT * FROM users WHERE email = ? OR username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User found
        $row = $result->fetch_assoc();
        $hashedPassword = $row['password'];
        $status = $row['status'];

        if ($status !== 'active') {
            // User is not verified
            $error = "Your account is not verified. Please check your email to verify your account.";
        } elseif (password_verify($password, $hashedPassword)) {
            // Password is correct and user is verified
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $row['id']; // Set user_id in session
            $_SESSION['username'] = $row['username'];
            $_SESSION['user_image'] = $row['profile_image']; // Set profile image in session
            $_SESSION['is_admin'] = $row['is_admin']; // Set admin status in session

            header('Location: my_nest.php');
            exit;
        } else {
            // Password is incorrect
            $error = "Invalid username/email or password!";
        }
    } else {
        // User not found
        $error = "Invalid username/email or password!";
    }

    $stmt->close();
}

$conn->close();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
       <!-- Web App Manifest -->
       <link rel="manifest" href="/manifest.json">

<!-- Theme Color -->
<meta name="theme-color" content="#4CAF50">

<!-- iOS-specific meta tags -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Sharenest">
<link rel="apple-touch-icon" href="/icons/icon-192x192.png">

<!-- Icons for various devices -->
<link rel="apple-touch-icon" sizes="180x180" href="/icons/icon-180x180.png">
<link rel="apple-touch-icon" sizes="192x192" href="/icons/icon-192x192.png">
<link rel="apple-touch-icon" sizes="512x512" href="/icons/icon-512x512.png">

<!-- Link to External PWA Script -->
<script src="/js/pwa.js" defer></script>
    <title>ShareNest - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<!-- Login Form STARTS here -->
<div id="content" class="container mt-5 d-flex align-items-center justify-content-center">
    <div class="col-md-5 col-sm-8" >
        <h2>Login</h2>
        <?php if (isset($error)) { echo "<div class='alert alert-danger' role='alert'>" . htmlspecialchars($error) . "</div>"; } ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-3">
                <label for="usernameOrEmail" class="form-label">Username or Email:</label>
                <input type="text" class="form-control" id="usernameOrEmail" name="usernameOrEmail" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-outline-success">Login</button>
            <p class="mt-3">Don't have an account? <a href="register.php">Register here</a>.</p>
        </form>
    </div>
</div>
<!-- Login Form ENDS here -->

<!-- Footer STARTS here -->
<?php include 'footer.php'; ?>
<!-- Footer ENDS here -->

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <button id="install-button" style="display: none;">Install Sharenest</button>
</body>
</html>
