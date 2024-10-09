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
    die("Connection failed: " . $conn->connect_error);
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
                    $emailBody = str_replace('{{username}}', htmlspecialchars($username), $template);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Welcome to Sharenest';
                    $mail->Body    = $emailBody;

                    // Embed the image
                    $logoPath = __DIR__ . '/img/sharenest_logo.png';
                    if (!file_exists($logoPath)) {
                        throw new Exception("Logo not found at $logoPath");
                    }
                    $mail->addEmbeddedImage($logoPath, 'sharenest_logo');

                    $mail->send();
                    echo 'Email has been sent successfully';

                } catch (Exception $e) {
                    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    echo "Exception: {$e->getMessage()}";
                }

                // Redirect to the signin page
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
        <form id="registerForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
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
    const registerButton = document.getElementById('registerButton');
    const registeringText = document.getElementById('registeringText');

    registerButton.disabled = true;
    registerButton.style.display = 'none';
    registeringText.style.display = 'inline';
});
</script>
</body>
</html>
