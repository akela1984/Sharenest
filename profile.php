<?php
include 'session_timeout.php';

// Redirect non-logged-in users to the sign-in page
if (!isset($_SESSION['loggedin'])) {
    header('Location: signin.php');
    exit;
}

include 'connection.php';

// Function to fetch user data
function fetch_user_data($conn, $username) {
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to fetch user address
function fetch_user_address($conn, $user_id) {
    $sql = "SELECT * FROM users_address WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

$username = $_SESSION['username'];
$user = fetch_user_data($conn, $username);
$user_id = $user['id'];
$user_address = fetch_user_address($conn, $user_id);

if (!$user) {
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

    $newEmail = htmlspecialchars(trim($_POST['email']), ENT_QUOTES, 'UTF-8');
    $newFirstName = htmlspecialchars(trim($_POST['firstname']), ENT_QUOTES, 'UTF-8');
    $newLastName = htmlspecialchars(trim($_POST['lastname']), ENT_QUOTES, 'UTF-8');
    $currentPassword = trim($_POST['current_password']);
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);

    $address_line1 = htmlspecialchars(trim($_POST['address_line1']), ENT_QUOTES, 'UTF-8');
    $address_line2 = htmlspecialchars(trim($_POST['address_line2']), ENT_QUOTES, 'UTF-8');
    $town_city = htmlspecialchars(trim($_POST['town_city']), ENT_QUOTES, 'UTF-8');
    $postcode = htmlspecialchars(trim($_POST['postcode']), ENT_QUOTES, 'UTF-8');
    $country = htmlspecialchars(trim($_POST['country']), ENT_QUOTES, 'UTF-8');

    // Validate input
    if (empty($newEmail)) {
        $error = "Email is required!";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } elseif (!empty($newPassword) && $newPassword !== $confirmPassword) {
        $error = "New passwords do not match!";
    } elseif (empty($currentPassword)) {
        $error = "Current password is required to save changes!";
    } elseif (!password_verify($currentPassword, $user['password'])) {
        $error = "Current password is incorrect!";
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
                $newImageName = htmlspecialchars($username . '_' . date('YmdHis') . '.' . $imageExtension, ENT_QUOTES, 'UTF-8');
                $targetFilePath = $uploadDir . $newImageName;

                // Move uploaded file with new name
                if (move_uploaded_file($imageTmpName, $targetFilePath)) {
                    // Delete old image if it exists
                    if (!empty($profileImage) && file_exists($profileImage)) {
                        unlink($profileImage);
                    }
                    $profileImage = $targetFilePath;
                    $_SESSION['user_image'] = $profileImage; // Update session with new image
                } else {
                    $error = "Failed to upload profile image!";
                }
            }
        }

        // Update user information
        $sql = "UPDATE users SET email = ?, firstname = ?, lastname = ?, profile_image = ? WHERE id = ?";
        $params = [$newEmail, $newFirstName, $newLastName, $profileImage, $user['id']];

        // If the new password field is filled out, update the password as well
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $sql = "UPDATE users SET email = ?, firstname = ?, lastname = ?, password = ?, profile_image = ? WHERE id = ?";
            $params = [$newEmail, $newFirstName, $newLastName, $hashedPassword, $profileImage, $user['id']];
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('s', count($params) - 1) . 'i', ...$params);

        if ($stmt->execute()) {
            $_SESSION['username'] = $username;
            $_SESSION['user_image'] = $profileImage; // Ensure the session is updated
            $profileUpdated = true; // Set profile updated flag

            // Update user address
            if ($user_address) {
                $sql_address = "UPDATE users_address SET address_line1 = ?, address_line2 = ?, town_city = ?, postcode = ?, country = ? WHERE user_id = ?";
                $params_address = [$address_line1, $address_line2, $town_city, $postcode, $country, $user_id];
                $stmt_address = $conn->prepare($sql_address);
                $stmt_address->bind_param(str_repeat('s', count($params_address) - 1) . 'i', ...$params_address);
                $stmt_address->execute();
            } else {
                $sql_address = "INSERT INTO users_address (user_id, address_line1, address_line2, town_city, postcode, country) VALUES (?, ?, ?, ?, ?, ?)";
                $params_address = [$user_id, $address_line1, $address_line2, $town_city, $postcode, $country];
                $stmt_address = $conn->prepare($sql_address);
                $stmt_address->bind_param('isssss', ...$params_address);
                $stmt_address->execute();
            }
        } else {
            $error = "Profile update failed, please try again!";
        }

        // Refetch user data after update
        $user = fetch_user_data($conn, $username);
        $user_address = fetch_user_address($conn, $user_id);
        if (!$user) {
            $error = "User not found!";
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
    <title>ShareNest - Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        .form-control[readonly], .form-control:disabled {
            background-color: #e9ecef; /* Grey out the background */
            opacity: 1; /* Ensure the text is still readable */
        }
    </style>
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<!-- Profile Form STARTS here -->
<div class="container mt-5 d-flex justify-content-center">
    <div class="col-md-6 col-sm-8">
        <h2>Profile</h2>
        <?php if (isset($error) && $error) { echo "<div class='alert alert-danger' role='alert'>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</div>"; } ?>
        <?php if ($profileUpdated) { echo "<div class='alert alert-success' role='alert'>Profile updated successfully!</div>"; } ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                <small class="form-text text-muted">This can't be edited.</small>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" required readonly>
            </div>
            <div class="mb-3">
                <label for="firstname" class="form-label">First Name:</label>
                <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['firstname'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="lastname" class="form-label">Last Name:</label>
                <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['lastname'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="address_line1" class="form-label">Address Line 1:</label>
                <input type="text" class="form-control" id="address_line1" name="address_line1" value="<?php echo htmlspecialchars($user_address['address_line1'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="address_line2" class="form-label">Address Line 2:</label>
                <input type="text" class="form-control" id="address_line2" name="address_line2" value="<?php echo htmlspecialchars($user_address['address_line2'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="town_city" class="form-label">Town/City:</label>
                <input type="text" class="form-control" id="town_city" name="town_city" value="<?php echo htmlspecialchars($user_address['town_city'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="postcode" class="form-label">Postcode:</label>
                <input type="text" class="form-control" id="postcode" name="postcode" value="<?php echo htmlspecialchars($user_address['postcode'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="country" class="form-label">Country:</label>
                <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($user_address['country'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="profileImage" class="form-label">Profile Image:</label>
                <?php if ($user['profile_image']) {
                    echo "<img src='" . htmlspecialchars($user['profile_image'], ENT_QUOTES, 'UTF-8') . "' alt='Profile Image' width='150' class='mb-3'>";
                } ?>
                <input type="file" class="form-control" id="profileImage" name="profileImage" accept="image/*" aria-describedby="imageHelp" disabled>
                <div id="imageHelp" class="form-text">Accepted formats: JPG, JPEG, PNG, GIF</div>
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password:</label>
                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Leave blank to keep current password" readonly>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password:</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Leave blank to keep current password" readonly>
            </div>
            <div class="mb-3">
                <label for="current_password" class="form-label">Current Password (required to save changes):</label>
                <input type="password" class="form-control" id="current_password" name="current_password" required readonly>
            </div>
            <div class="profile-buttons">
                <button type="button" id="editButton" class="btn btn-outline-warning">Edit</button>
                <button type="submit" id="saveButton" class="btn btn-outline-success" style="display:none;">Save</button>
                <button type="button" id="cancelButton" class="btn btn-outline-danger" style="display:none;">Cancel</button>
            </div>
        </form>
    </div>
</div>
<!-- Profile Form ENDS here -->

<!-- Footer STARTS here -->
<?php // include 'footer.php'; ?>
<!-- Footer ENDS here -->

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('editButton').addEventListener('click', function() {
        document.getElementById('email').removeAttribute('readonly');
        document.getElementById('firstname').removeAttribute('readonly');
        document.getElementById('lastname').removeAttribute('readonly');
        document.getElementById('address_line1').removeAttribute('readonly');
        document.getElementById('address_line2').removeAttribute('readonly');
        document.getElementById('town_city').removeAttribute('readonly');
        document.getElementById('postcode').removeAttribute('readonly');
        document.getElementById('country').removeAttribute('readonly');
        document.getElementById('new_password').removeAttribute('readonly');
        document.getElementById('confirm_password').removeAttribute('readonly');
        document.getElementById('current_password').removeAttribute('readonly');
        document.getElementById('profileImage').removeAttribute('disabled');
        document.getElementById('editButton').style.display = 'none';
        document.getElementById('saveButton').style.display = 'inline-block';
        document.getElementById('cancelButton').style.display = 'inline-block';
    });

    document.getElementById('cancelButton').addEventListener('click', function() {
        location.reload(); // Reload the page to reset the form fields and disable them
    });

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
</script>
</body>
</html>
