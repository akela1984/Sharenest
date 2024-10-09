<?php
include 'session_timeout.php';
include 'connection.php';

// Load PHPMailer
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

    // Sanitize user input
    $forgotEmail = htmlspecialchars($_POST['forgotEmail'], ENT_QUOTES, 'UTF-8');

    // Use prepared statements to prevent SQL injection
    $sql = "SELECT id, username, email FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $forgotEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User found
        $row = $result->fetch_assoc();
        $username = $row['username'];
        $email = $row['email'];
        $userId = $row['id'];

        // Generate reset token
        $resetToken = bin2hex(random_bytes(16));

        // Update user with reset token
        $updateSql = "UPDATE users SET token = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $resetToken, $userId);
        $updateStmt->execute();

        // Load email template
        $templatePath = __DIR__ . '/templates/reset_password_email.html';
        if (!file_exists($templatePath)) {
            die("Email template not found at $templatePath");
        }
        $template = file_get_contents($templatePath);

        // Replace placeholders in the template
        $resetLink = "https://www.sharenest.org/reset_password.php?token=$resetToken";
        $emailBody = str_replace(
            ['{{username}}', '{{resetLink}}'],
            [htmlspecialchars($username), htmlspecialchars($resetLink)],
            $template
        );

        $mail = new PHPMailer(true);

        try {
            // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.livemail.co.uk';
                $mail->SMTPAuth   = true;
                $mail->Username   = $config['smtp']['username'];   // Use from config.ini
                $mail->Password   = $config['smtp']['password'];   // Use from config.ini
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

            // Recipients
            $mail->setFrom('no-reply@sharenest.org', 'Sharenest');
            $mail->addAddress($email, $username);

            // Load HTML template
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = $emailBody;

            // Embed the logo image
            $logoPath = __DIR__ . '/img/sharenest_logo.png';
            if (file_exists($logoPath)) {
                $mail->addEmbeddedImage($logoPath, 'sharenest_logo');
            } else {
                throw new Exception("Logo image not found at $logoPath");
            }

            // Send email
            $mail->send();

            // Inform user about sending email
            $_SESSION['message'] = "If the address is registered, we will send an email to that email address.";
        } catch (Exception $e) {
            $_SESSION['message'] = "Failed to send the email. Please try again. Error: {$mail->ErrorInfo}";
        }
    } else {
        $_SESSION['message'] = "If the address is registered, we will send an email to that email address.";
    }

    $stmt->close();
    $conn->close();

    // Redirect back to the signin page
    header('Location: signin.php');
    exit;
}
?>
