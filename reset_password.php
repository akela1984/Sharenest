<?php
// Include session management script (session_timeout.php)
include 'session_timeout.php';

// Redirect logged-in users to the homepage (index.php)
if (isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit;
}

// Include database connection script (connection.php)
include 'connection.php';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$error = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    // Sanitize inputs
    $password = htmlspecialchars($_POST['password'], ENT_QUOTES, 'UTF-8');
    $confirm_password = htmlspecialchars($_POST['confirm_password'], ENT_QUOTES, 'UTF-8');
    $token = $_GET['token']; // Retrieve token from URL (assuming it's already sanitized properly)

    // Validate password and confirm password match
    if ($password != $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update password in the database for the user with the provided token
        $sql = "UPDATE users SET password = ?, token = NULL, status = 'active' WHERE token = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $hashed_password, $token);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Password has been reset successfully!";
            header('Location: signin.php');
            exit;
        } else {
            $error = "Password reset failed, please try again.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ShareNest - Reset Password</title>
    
    <!-- Meta tags for SEO and social sharing -->
    <meta name="description" content="Reset your password for ShareNest.">
    <meta name="keywords" content="password reset, ShareNest, community sharing, unwanted goods">
    <meta name="robots" content="noindex, nofollow">
    <meta name="author" content="ShareNest">

    <!-- CSS stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="p-3 m-0 border-0 bd-example">

<!-- Navbar -->
<?php include 'navbar.php'; ?>

<!-- Content -->
<div id="content" class="container mt-5 d-flex align-items-center justify-content-center">
    <div class="col-md-5 col-sm-8">
        <h2>Reset Password</h2>
        <?php if (!empty($error)) { ?>
            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?token=' . htmlspecialchars($_GET['token']); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-3">
                <label for="password" class="form-label">New Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password:</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-outline-success">Reset Password</button>
        </form>
    </div>
</div>

<!-- Footer -->
<?php include 'footer.php'; ?>

</body>
</html>
