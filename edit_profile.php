<?php
include 'session_timeout.php';

// Redirect non-logged-in users to the sign-in page
if (!isset($_SESSION['loggedin'])) {
    header('Location: signin.php');
    exit;
}

include 'connection.php';

$username = htmlspecialchars($_SESSION['username']);
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    $error = "User not found!";
}

$uploadDir = 'uploads/user_profile_img/';
$profileUpdated = false;

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $newUsername = htmlspecialchars(trim($_POST['username']));
    $newEmail = htmlspecialchars(trim($_POST['email']));

    // Validate input
    if (empty($newUsername) || empty($newEmail)) {
        $error = "All fields are required!";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {
        $profileImage = $user['profile_image']; // default to current image

        // Handle profile image upload
        if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == UPLOAD_ERR_OK) {
            $imageTmpName = $_FILES['profileImage']['tmp_name'];
            $imageName = $_FILES['profileImage']['name'];
            $imageExtension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));

            // Define allowed file formats
            $allowedFormats = array('jpg', 'jpeg', 'png', 'gif');

            // Validate file format
            if (!in_array($imageExtension, $allowedFormats)) {
                $error = "Only JPG, JPEG, PNG, and GIF files are allowed!";
            } else {
                // Rename image file
                $newImageName = $newUsername . '_' . date('YmdHis') . '.' . $imageExtension;
                $targetFilePath = $uploadDir . $newImageName;

                // Move uploaded file with new name
                if (move_uploaded_file($imageTmpName, $targetFilePath)) {
                    // Delete old image if it exists
                    if (!empty($profileImage) && file_exists($profileImage)) {
                        unlink($profileImage);
                    }
                    $profileImage = htmlspecialchars($targetFilePath);
                } else {
                    $error = "Failed to upload profile image!";
                }
            }
        }

        // Update user information
        $sql = "UPDATE users SET username = ?, email = ?, profile_image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $newUsername, $newEmail, $profileImage, $user['id']);

        if ($stmt->execute()) {
            $_SESSION['username'] = $newUsername;
            header("Location: profile.php?success=1");
            exit();
        } else {
            $error = "Profile update failed, please try again!";
        }
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
    <title>ShareNest - Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<!-- Profile Form STARTS here -->
<div id="content" class="container mt-5 d-flex justify-content-center">
    <div class="col-md-6 col-sm-8">
        <h2>Profile</h2>
        <?php if (isset($error)) { echo "<div class='alert alert-danger' role='alert'>" . htmlspecialchars($error) . "</div>"; } ?>
        <?php if (isset($_GET['success'])) { echo "<div class='alert alert-success' role='alert'>Profile updated successfully!</div>"; } ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required readonly>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required readonly>
            </div>
            <div class="mb-3">
                <label for="profileImage" class="form-label">Profile Image:</label>
                <?php if ($user['profile_image']) {
                    echo "<img src='" . htmlspecialchars($user['profile_image']) . "' alt='Profile Image' width='150' class='mb-3'>";
                } ?>
                <input type="file" class="form-control" id="profileImage" name="profileImage" accept="image/*" aria-describedby="imageHelp">
                <div id="imageHelp" class="form-text">Accepted formats: JPG, JPEG, PNG, GIF</div>
            </div>
            <button type="button" id="editButton" class="btn btn-outline-warning">Edit</button>
            <button type="submit" id="saveButton" class="btn btn-outline-success" style="display:none;">Save</button>
        </form>
    </div>
</div>
<!-- Profile Form ENDS here -->

<!-- Footer STARTS here -->
<?php include 'footer.php'; ?>
<!-- Footer ENDS here -->

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('editButton').addEventListener('click', function() {
        document.getElementById('username').removeAttribute('readonly');
        document.getElementById('email').removeAttribute('readonly');
        document.getElementById('editButton').style.display = 'none';
        document.getElementById('saveButton').style.display = 'inline-block';
    });
</script>
    <button id="install-button" style="display: none;">Install Sharenest</button>
</body>
</html>
