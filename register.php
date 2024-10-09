<?php
session_start();
include 'session_timeout.php';

// Redirect logged-in users to the homepage
if (isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit;
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

// Extract database configuration
$dbServer = $config['database']['server'];
$dbUsername = $config['database']['username'];
$dbPassword = $config['database']['password'];
$dbName = $config['database']['name'];

// Create connection
$conn = new mysqli($dbServer, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error));
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Load PHPMailer at the top of the file
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $username = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars(trim($_POST['email']), ENT_QUOTES, 'UTF-8');
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match!";
    } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/', $password)) {
        $error = "Password must be at least 8 characters long, include at least one letter, one number, and one special character from @$!%*#?&.";
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
            // Generate a verification token
            $token = bin2hex(random_bytes(16));
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user into the database with a token and status as inactive
            $sql = "INSERT INTO users (username, email, password, token, status) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $status = 'inactive';
            $stmt->bind_param("sssss", $username, $email, $hashedPassword, $token, $status);

            if ($stmt->execute()) {
                // Registration successful, send confirmation email
                $smtpUsername = $config['smtp']['username'];
                $smtpPassword = $config['smtp']['password'];

                $mail = new PHPMailer(true);

                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.livemail.co.uk';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $smtpUsername;
                    $mail->Password   = $smtpPassword;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    // Recipients
                    $mail->setFrom('no-reply@sharenest.org', 'Sharenest');
                    $mail->addAddress($email, $username);

                    // Load HTML template
                    $templatePath = __DIR__ . '/templates/register_email_template.html';
                    if (!file_exists($templatePath)) {
                        throw new Exception("Email template not found at $templatePath");
                    }
                    $template = file_get_contents($templatePath);
                    $verificationLink = "http://sharenest.org/verify.php?token=" . urlencode($token);
                    $emailBody = str_replace(['{{username}}', '{{verification_link}}'], [htmlspecialchars($username, ENT_QUOTES, 'UTF-8'), htmlspecialchars($verificationLink, ENT_QUOTES, 'UTF-8')], $template);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Email Verification - Sharenest';
                    $mail->Body    = $emailBody;

                    // Embed the image
                    $logoPath = __DIR__ . '/img/sharenest_logo.png';
                    if (!file_exists($logoPath)) {
                        throw new Exception("Logo not found at $logoPath");
                    }
                    $mail->addEmbeddedImage($logoPath, 'sharenest_logo');

                    $mail->send();
                    echo "<script>alert('Registration successful! Please check your email, including the junk mail folder, for verification.'); window.location.href='signin.php';</script>";
                    exit;

                } catch (Exception $e) {
                    echo "Message could not be sent. Mailer Error: " . htmlspecialchars($mail->ErrorInfo);
                    echo "Exception: " . htmlspecialchars($e->getMessage());
                }

                // Redirect to the signin page
                echo "<script>alert('Registration successful! Please check your email for verification.'); window.location.href='signin.php';</script>";
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
        <?php if (!empty($error)) { echo "<div class='alert alert-danger' role='alert'>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</div>"; } ?>
        <div id="errorMessage" class="alert alert-danger" style="display:none;"></div>
        <form id="registerForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
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
                <small id="passwordHelp" class="form-text text-muted">
                    Password must be at least 8 characters long, include at least one letter, one number, and one special character. 
                    Allowed special characters: @$!%*#?&.
                </small>
            </div>
            <div class="mb-3">
                <label for="confirmPassword" class="form-label">Confirm Password:</label>
                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
            </div>
            <button type="submit" id="registerButton" class="btn btn-outline-success">Register</button>
            <span id="registeringText" class="text-success" style="display: none;">Registering...</span>
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
<script>
document.getElementById('registerForm').addEventListener('submit', function(event) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const errorMessage = document.getElementById('errorMessage');
    const regex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/;

    errorMessage.style.display = 'none';

    if (!regex.test(password)) {
        errorMessage.textContent = 'Password must be at least 8 characters long, include at least one letter, one number, and one special character from @$!%*#?&.';
        errorMessage.style.display = 'block';
        event.preventDefault();
    } else if (password !== confirmPassword) {
        errorMessage.textContent = 'Passwords do not match!';
        errorMessage.style.display = 'block';
        event.preventDefault();
    } else {
        const registerButton = document.getElementById('registerButton');
        const registeringText = document.getElementById('registeringText');

        registerButton.disabled = true;
        registerButton.style.display = 'none';
        registeringText.style.display = 'inline';
    }
});
</script>
<button id="install-button" style="display: none;">Install Sharenest</button>
</body>
</html>
