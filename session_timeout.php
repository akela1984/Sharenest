<?php
// Set the temporary upload directory and session save path
if (session_status() == PHP_SESSION_NONE) {
    //ini_set('session.save_path', '/home/storage/497/4304497/user/htdocs/sessions');
    session_start();
}

// Set the session timeout duration (in seconds)
$timeout_duration = 600; // 10 minutes

// Check if the user is logged in
if (isset($_SESSION['loggedin'])) {
    // Check if "last_activity" is set
    if (isset($_SESSION['last_activity'])) {
        // Calculate the session's lifetime
        $elapsed_time = time() - $_SESSION['last_activity'];
        
        // If the session has expired, destroy it and redirect to the sign-in page
        if ($elapsed_time >= $timeout_duration) {
            session_unset(); // Unset all session variables
            session_destroy(); // Destroy the session
            header('Location: signin.php');
            exit;
        }
    }

    // Update "last_activity" timestamp
    $_SESSION['last_activity'] = time();
}
?>
