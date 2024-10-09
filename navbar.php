<?php
session_start(); // Ensure session is started

include 'connection.php'; // Include the connection to your database

$unreadConversationsCount = 0;

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    $userId = $_SESSION['user_id'];
    $sql_unread_count = "SELECT COUNT(DISTINCT conversation_id) AS unread_count 
                         FROM messages 
                         WHERE recipient_id = ? AND `read` = FALSE";
    $stmt_unread_count = $conn->prepare($sql_unread_count);
    $stmt_unread_count->bind_param("i", $userId);
    $stmt_unread_count->execute();
    $result_unread_count = $stmt_unread_count->get_result();
    $unreadConversationsCount = $result_unread_count->fetch_assoc()['unread_count'];

    // Check if the user is an admin
    $sql_is_admin = "SELECT is_admin FROM users WHERE id = ?";
    $stmt_is_admin = $conn->prepare($sql_is_admin);
    $stmt_is_admin->bind_param("i", $userId);
    $stmt_is_admin->execute();
    $result_is_admin = $stmt_is_admin->get_result();
    $is_admin = $result_is_admin->fetch_assoc()['is_admin'] === 'true';
} else {
    $is_admin = false;
}
?>

<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <img src="img/sharenest_logo.png" alt="ShareNest Logo" style="height: 40px;">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
            <ul class="navbar-nav mb-2 mb-lg-0 horizontal-buttons">
                <li class="nav-item spacer">
                    <a class="btn btn-outline-success" href="my_nest.php">My Nest</a>
                </li>
                <li class="nav-item spacer">
                    <a class="btn btn-outline-success" href="create_listing.php">Create Listing</a>
                </li>
                <?php if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) { ?>
                <li class="nav-item spacer">
                    <a class="btn btn-outline-success" href="signin.php">Sign in</a>
                </li>
                <?php } ?>
            </ul>
            <ul class="navbar-nav mb-2 mb-lg-0">
                <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) { ?>
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="my_messages.php">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width: 24px; height: 24px; fill: currentColor;">
                                <path d="M64 112c-8.8 0-16 7.2-16 16v22.1L220.5 291.7c20.7 17 50.4 17 71.1 0L464 150.1V128c0-8.8-7.2-16-16-16H64zM48 212.2V384c0 8.8 7.2 16 16 16H448c8.8 0 16-7.2 16-16V212.2L322 328.8c-38.4 31.5-93.7 31.5-132 0L48 212.2zM0 128C0 92.7 28.7 64 64 64H448c35.3 0 64 28.7 64 64V384c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128z"/>
                            </svg>
                            <?php if ($unreadConversationsCount > 0) { ?>
                                <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">
                                    <?php echo htmlspecialchars($unreadConversationsCount); ?>
                                </span>
                            <?php } ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_dashboard.php">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width: 24px; height: 24px; fill: currentColor;">
                                <path d="M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm320 96c0-26.9-16.5-49.9-40-59.3V88c0-13.3-10.7-24-24-24s-24 10.7-24 24V292.7c-23.5 9.5-40 32.5-40 59.3c0 35.3 28.7 64 64 64s64-28.7 64-64zM144 176a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm-16 80a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64zM400 144a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/>
                            </svg>
                        </a>
                    </li>
                    
                    <?php if ($is_admin) { ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_panel.php">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width: 24px; height: 24px; fill: currentColor;">
                                <path d="M78.6 5C69.1-2.4 55.6-1.5 47 7L7 47c-8.5 8.5-9.4 22-2.1 31.6l80 104c4.5 5.9 11.6 9.4 19 9.4h54.1l109 109c-14.7 29-10 65.4 14.3 89.6l112 112c12.5 12.5 32.8 12.5 45.3 0l64-64c12.5-12.5 12.5-32.8 0-45.3l-112-112c-24.2-24.2-60.6-29-89.6-14.3l-109-109V104c0-7.5-3.5-14.5-9.4-19L78.6 5zM19.9 396.1C7.2 408.8 0 426.1 0 444.1C0 481.6 30.4 512 67.9 512c18 0 35.3-7.2 48-19.9L233.7 374.3c-7.8-20.9-9-43.6-3.6-65.1l-61.7-61.7L19.9 396.1zM512 144c0-10.5-1.1-20.7-3.2-30.5c-2.4-11.2-16.1-14.1-24.2-6l-63.9 63.9c-3 3-7.1 4.7-11.3 4.7H352c-8.8 0-16-7.2-16-16V102.6c0-4.2 1.7-8.3 4.7-11.3l63.9-63.9c8.1-8.1 5.2-21.8-6-24.2C388.7 1.1 378.5 0 368 0C288.5 0 224 64.5 224 144l0 .8 85.3 85.3c36-9.1 75.8 .5 104 28.7L429 274.5c49-23 83-72.8 83-130.5zM56 432a24 24 0 1 1 48 0 24 24 0 1 1 -48 0z"/>
                            </svg>
                        </a>
                    </li>
                    <?php } ?>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown"
                           aria-expanded="false">
                            <?php if (!empty($_SESSION['user_image'])) { ?>
                                <img src="<?php echo htmlspecialchars($_SESSION['user_image']); ?>" alt="User Image" class="rounded-circle me-2 user-icon">
                            <?php } else { ?>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="rounded-circle me-2 user-icon" style="width: 24px; height: 24px; fill: currentColor;">
                                    <path d="M399 384.2C376.9 345.8 335.4 320 288 320H224c-47.4 0-88.9 25.8-111 64.2c35.2 39.2 86.2 63.8 143 63.8s107.8-24.7 143-63.8zM0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm256 16a72 72 0 1 0 0-144 72 72 0 1 0 0 144z"/>
                                </svg>
                            <?php } ?>
                            <span class="ms-1">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="nav-item"><a class="dropdown-item" href="my_dashboard.php">Dashboard</a></li>
                            <li class="nav-item"><a class="dropdown-item" href="my_messages.php">My Messages</a></li>
                            <li class="nav-item"><a class="dropdown-item" href="profile.php">My Profile</a></li>
                            <li class="nav-item"><a class="dropdown-item" href="join_location.php">My Locations</a></li>
                            <?php if ($is_admin) { ?>
                            <li class="nav-item"><a class="dropdown-item" href="admin_panel.php">Admin Panel</a></li>
                            <?php } ?>
                            <li class="nav-item"><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
</nav>
