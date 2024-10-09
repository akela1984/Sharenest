<?php
include 'session_timeout.php';

// Check if the user has access REMOVE THIS AFTER GO LIVE
if (!isset($_SESSION['access_granted'])) {
    header('Location: comingsoon.php');
    exit();
}

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

// Function to delete images from the filesystem
function delete_listing_images($conn, $user_id) {
    $sql = "SELECT listing_images.image_url FROM listing_images JOIN listings ON listing_images.listing_id = listings.id WHERE listings.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $image_path = 'uploads/listing_images/' . $row['image_url'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
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

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
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

        if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == UPLOAD_ERR_OK) {
            $imageTmpName = $_FILES['profileImage']['tmp_name'];
            $imageName = $_FILES['profileImage']['name'];
            $imageExtension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
            $allowedFormats = array('jpg', 'jpeg', 'png', 'gif');

            if (!in_array($imageExtension, $allowedFormats)) {
                $error = "Only JPG, JPEG, PNG, and GIF files are allowed!";
            } else {
                $newImageName = htmlspecialchars($username . '_' . date('YmdHis') . '.' . $imageExtension, ENT_QUOTES, 'UTF-8');
                $targetFilePath = $uploadDir . $newImageName;

                if (move_uploaded_file($imageTmpName, $targetFilePath)) {
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

        $sql = "UPDATE users SET email = ?, firstname = ?, lastname = ?, profile_image = ? WHERE id = ?";
        $params = [$newEmail, $newFirstName, $newLastName, $profileImage, $user['id']];

        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $sql = "UPDATE users SET email = ?, firstname = ?, lastname = ?, password = ?, profile_image = ? WHERE id = ?";
            $params = [$newEmail, $newFirstName, $newLastName, $hashedPassword, $profileImage, $user['id']];
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('s', count($params) - 1) . 'i', ...$params);

        if ($stmt->execute()) {
            $_SESSION['username'] = $username;
            $_SESSION['user_image'] = $profileImage;
            $profileUpdated = true;

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

        $user = fetch_user_data($conn, $username);
        $user_address = fetch_user_address($conn, $user_id);
        if (!$user) {
            $error = "User not found!";
        }
    }
}

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_account'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $currentPassword = trim($_POST['current_password_delete']);

    if (empty($currentPassword)) {
        $error = "Current password is required to delete account!";
    } elseif (!password_verify($currentPassword, $user['password'])) {
        $error = "Current password is incorrect!";
    } else {
        $email = $user['email'];
        $username = $user['username'];
        delete_listing_images($conn, $user_id);

        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $sql_address = "DELETE FROM users_address WHERE user_id = ?";
        $stmt_address = $conn->prepare($sql_address);
        $stmt_address->bind_param("i", $user_id);
        $stmt_address->execute();

        $smtpUsername = $config['smtp']['username'];
        $smtpPassword = $config['smtp']['password'];

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.livemail.co.uk';
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUsername;
            $mail->Password   = $smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->setFrom('no-reply@sharenest.org', 'Sharenest');
            $mail->addAddress($email, $username);

            $templatePath = __DIR__ . '/templates/delete_account_email_template.html';
            if (!file_exists($templatePath)) {
                throw new Exception("Email template not found at $templatePath");
            }
            $template = file_get_contents($templatePath);
            $emailBody = str_replace(['{{username}}'], [htmlspecialchars($username, ENT_QUOTES, 'UTF-8')], $template);

            $mail->isHTML(true);
            $mail->Subject = 'Account Deletion Confirmation - Sharenest';
            $mail->Body    = $emailBody;
            $mail->CharSet = 'UTF-8';

            $logoPath = __DIR__ . '/img/sharenest_logo.png';
            if (!file_exists($logoPath)) {
                throw new Exception("Logo not found at $logoPath");
            }
            $mail->addEmbeddedImage($logoPath, 'sharenest_logo');

            $mail->send();
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: " . $mail->ErrorInfo);
        }

        session_destroy();
        header('Location: index.php');
        exit;
    }
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

    <!-- Icons for various devices -->
    <link rel="apple-touch-icon" sizes="180x180" href="/img/favicon.png">
    <link rel="apple-touch-icon" sizes="192x192" href="/img/favicon.png">
    <link rel="apple-touch-icon" sizes="512x512" href="/img/favicon.png">
    
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

    <!-- Link to External PWA Script -->
    <script src="/js/pwa.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        .form-control[readonly], .form-control:disabled {
            background-color: #e9ecef;
            opacity: 1;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<?php include 'navbar.php'; ?>

<div id="content" class="container mt-5 d-flex align-items-center justify-content-center">
    <div class="col-md-6 col-sm-8">
        <h2>Profile</h2>
        <?php if (isset($error) && $error) { echo "<div class='alert alert-danger' role='alert'>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</div>"; } ?>
        <?php if ($profileUpdated) { echo "<div class='alert alert-success' role='alert'>Profile updated successfully!</div>"; } ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="update_profile" value="1">
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                <small class="form-text text-muted">This can't be edited.</small>
            </div>

            <!-- Address lookup fields -->
            <div id="address-lookup" class="mb-3">
                <label for="houseNumber" class="form-label">Enter House Number or Name:</label>
                <input type="text" class="form-control" id="houseNumber" placeholder="E.g., 46 or Buckingham Palace" disabled>
                <label for="postcodeLookup" class="form-label">Enter Postcode:</label>
                <input type="text" class="form-control" id="postcodeLookup" placeholder="E.g., SW1A 1AA" disabled>
                <button type="button" class="btn btn-primary mt-2" onclick="getPostcodeInfo()" disabled data-bs-toggle="tooltip" title="Adding your address is optional. However, if provided, it will display your approximate location (within a 3-mile radius) in your listing details. Additionally, you can use the 'Send My Address' button in conversations to quickly share your address with a link to open it in the main map application, saving time and effort.">Find Address</button>
                <button type="button" class="btn btn-secondary mt-2" id="addManuallyButton" onclick="showAddressFields()" disabled style="display:none;">Add Manually</button>
            </div>

            <!-- Address fields (hidden by default, shown if already filled) -->
            <div id="address-fields" class="hidden mt-3">
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
        <hr>
        <div class="mt-3">
            <h3>Delete Account</h3>
            <form id="deleteAccountForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="delete_account" value="1">
                <div class="mb-3">
                    <label for="current_password_delete" class="form-label">Current Password:</label>
                    <input type="password" class="form-control" id="current_password_delete" name="current_password_delete" required>
                </div>
                <button type="button" id="deleteAccountButton" class="btn btn-danger">Delete Account</button>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
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
        document.getElementById('houseNumber').removeAttribute('disabled');
        document.getElementById('postcodeLookup').removeAttribute('disabled');
        document.querySelector('#address-lookup button').removeAttribute('disabled');
        document.getElementById('addManuallyButton').removeAttribute('disabled');
        document.getElementById('editButton').style.display = 'none';
        document.getElementById('saveButton').style.display = 'inline-block';
        document.getElementById('cancelButton').style.display = 'inline-block';
    });

    document.getElementById('cancelButton').addEventListener('click', function() {
        location.reload(); // Reload the page to reset the form fields and disable them
    });

    document.getElementById('deleteAccountButton').addEventListener('click', function() {
        if (confirm("Are you sure you want to delete your account? This action cannot be undone.")) {
            document.getElementById('deleteAccountForm').submit();
        }
    });

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Address lookup functionality
    function getPostcodeInfo() {
        const houseNumber = $('#houseNumber').val();
        const postcode = $('#postcodeLookup').val();
        if (!houseNumber) {
            alert("Please enter a house number or name.");
            return;
        }
        if (!postcode) {
            alert("Please enter a postcode.");
            return;
        }

        const apiUrl = `https://api.postcodes.io/postcodes/${encodeURIComponent(postcode)}`;
        $.get(apiUrl, function(response) {
            if (response.result) {
                const result = response.result;
                const lat = result.latitude;
                const lon = result.longitude;
                getStreetAddress(lat, lon, houseNumber);
            } else {
                alert("No address found for the given postcode.");
            }
        }).fail(function() {
            alert("Failed to fetch postcode information.");
        });
    }

    function getStreetAddress(lat, lon, houseInput) {
        const geocodeUrl = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&accept-language=en`;
        $.get(geocodeUrl, function(response) {
            if (response.address) {
                const address = response.address;
                const isNumber = /^\d+$/.test(houseInput);
                const houseInfo = isNumber ? houseInput : houseInput;

                $('#address_line1').val(houseInfo + (address.road ? ' ' + address.road : ''));
                $('#address_line2').val(address.suburb || '');
                $('#town_city').val(address.city || address.town || '');
                $('#postcode').val(address.postcode || '');
                $('#country').val(address.country || '');

                if ($('#country').val().toLowerCase() === 'egyesült királyság') {
                    $('#country').val('United Kingdom');
                }

                $('#address-fields').removeClass('hidden');
            } else {
                alert("No address found for the given coordinates.");
            }
        }).fail(function() {
            alert("Failed to fetch street address.");
        });
    }

    function showAddressFields() {
        $('#address-fields').removeClass('hidden');
    }

    $(document).ready(function() {
        if ($('#address_line1').val() || $('#address_line2').val() || $('#town_city').val() || $('#postcode').val() || $('#country').val()) {
            $('#address-fields').removeClass('hidden');
        } else {
            $('#addManuallyButton').show();
        }
    });
</script>
<button id="install-button" style="display: none;">Install Sharenest</button>
</body>
</html>
