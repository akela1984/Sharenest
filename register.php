<?php
include 'session_timeout.php';

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

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match!";
    } else {
        // Check if username or email already exists
        $sql = "SELECT * FROM users WHERE email = ? OR username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Username or email already taken!";
        } else {
            // Insert new user into the database
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $username, $email, $hashedPassword);

            if ($stmt->execute()) {
                // Registration successful
                header('Location: signin.php');
                exit;
            } else {
                $error = "Registration failed, please try again!";
            }
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ShareNest - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<!-- Register Form STARTS here -->
<div class="container mt-5 d-flex justify-content-center">
    <div class="col-md-6 col-sm-8">
        <h2>Register</h2>
        <?php if (isset($error)) { echo "<div class='alert alert-danger' role='alert'>" . htmlspecialchars($error) . "</div>"; } ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="confirmPassword" class="form-label">Confirm Password:</label>
                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
            </div>
            <button type="submit" class="btn btn-outline-success">Register</button>
            <p class="mt-3">Already have an account? <a href="signin.php">Sign in here</a>.</p>
        </form>
    </div>
</div>
<!-- Register Form ENDS here -->

<!-- Footer STARTS here -->
<?php // include 'footer.php'; ?>
<!-- Footer ENDS here -->

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
