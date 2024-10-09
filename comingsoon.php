<?php
include 'session_timeout.php';
include 'connection.php';

// Check if user is already logged in and redirect to index.php
if (isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit();
}

// Generate CSRF token if it doesn't exist
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
            $_SESSION['firstname'] = $row['firstname'];
            $_SESSION['access_granted'] = true; // Grant access

            header('Location: index.php');
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
    <link href="css/styles.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
            margin: 0;
            font-family: Arial, sans-serif;
            text-align: center;
            color: #333;
        }
        .coming-soon-container {
            max-width: 600px;
            padding: 40px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .coming-soon-logo img {
            max-width: 200px;
            margin-bottom: 20px;
        }
        .coming-soon h1 {
            margin-bottom: 20px;
            font-size: 2.5em;
            color: #5cb85c;
        }
        .countdown {
            display: flex;
            justify-content: space-between;
            max-width: 400px;
            margin: 0 auto 20px;
            gap: 15px;
        }
        .countdown div {
            font-size: 1.5em;
            flex: 1;
        }
        .countdown div span {
            display: block;
            font-size: 2em;
            font-weight: bold;
            color: #5cb85c;
        }
        .access-form {
            margin-top: 20px;
        }
        .access-form input[type="text"],
        .access-form input[type="password"] {
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
        }
        .access-form input[type="submit"] {
            background-color: #5cb85c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .access-form input[type="submit"]:hover {
            background-color: #4cae4c;
        }
        .error-message {
            color: red;
        }
    </style>
</head>
<body>

<div class="coming-soon-container">
    <div class="coming-soon-logo">
        <img src="img/sharenest_logo.png" alt="ShareNest Logo">
    </div>
    <h1>Coming Soon</h1>
    <p>We are excited to launch ShareNest. Stay tuned for our launch on December 1st!</p>
    <div class="countdown" id="countdown">
        <div><span id="days">0</span> Days</div>
        <div><span id="hours">0</span> Hours</div>
        <div><span id="minutes">0</span> Minutes</div>
        <div><span id="seconds">0</span> Seconds</div>
    </div>
    <form method="post" class="access-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <div class="mb-3">
            <label for="usernameOrEmail" class="form-label">Username or Email:</label>
            <input type="text" class="form-control" id="usernameOrEmail" name="usernameOrEmail" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password:</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <input type="submit" value="Login">
        <?php if (isset($error)) : ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
    </form>
</div>

<script>
    // Countdown timer
    const countdown = () => {
        const countDate = new Date("December 1, 2024 00:00:00").getTime();
        const now = new Date().getTime();
        const gap = countDate - now;

        const second = 1000;
        const minute = second * 60;
        const hour = minute * 60;
        const day = hour * 24;

        const textDay = Math.floor(gap / day);
        const textHour = Math.floor((gap % day) / hour);
        const textMinute = Math.floor((gap % hour) / minute);
        const textSecond = Math.floor((gap % minute) / second);

        document.getElementById('days').innerText = textDay;
        document.getElementById('hours').innerText = textHour;
        document.getElementById('minutes').innerText = textMinute;
        document.getElementById('seconds').innerText = textSecond;
    };

    setInterval(countdown, 1000);
</script>
</body>
</html>
