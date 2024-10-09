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
}
?>

<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">ShareNest</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item spacer">
                    <a class="btn btn-outline-success" href="my_nest.php">My Nest</a>
                </li>
                <li class="nav-item spacer">
                    <a class="btn btn-outline-success" href="create_listing.php">Create Listing</a>
                </li>
                <!-- Add a spacer -->
                <li class="nav-item spacer"></li>
                <!-- Spacer end -->
                <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) { ?>
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="my_messages.php">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width: 24px; height: 24px; fill: currentColor;">
                                <path d="M64 112c-8.8 0-16 7.2-16 16v22.1L220.5 291.7c20.7 17 50.4 17 71.1 0L464 150.1V128c0-8.8-7.2-16-16-16H64zM48 212.2V384c0 8.8 7.2 16 16 16H448c8.8 0 16-7.2 16-16V212.2L322 328.8c-38.4 31.5-93.7 31.5-132 0L48 212.2zM0 128C0 92.7 28.7 64 64 64H448c35.3 0 64 28.7 64 64V384c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128z"/>
                            </svg>
                            <?php if ($unreadConversationsCount > 0) { ?>
                                <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">
                                    <?php echo $unreadConversationsCount; ?>
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
                            <li class="nav-item"><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php } else { ?>
                    <li class="nav-item">
                        <a class="btn btn-outline-success" href="signin.php">Sign in</a>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
</nav>

<style>
    .dropdown-item {
        padding: 10px 20px; /* Increase padding for touch friendliness */
        color: #5cb85c !important; /* Set text color to #5cb85c */
    }
    .dropdown-menu .nav-item {
        display: block;
    }
    /* Align user icon with other icons */
    .user-icon {
        width: 24px;
        height: 24px;
    }
    /* Custom styles for the dropdown in mobile view */
    @media (max-width: 992px) {
        .dropdown-menu {
            width: 100%; /* Full width */
            padding: 10px; /* Padding for touch friendliness */
            font-size: 1.1rem; /* Increase font size */
        }
        .dropdown-item {
            padding: 10px 20px; /* Increase padding for touch friendliness */
            color: #5cb85c !important; /* Set text color to #5cb85c */
        }
        .dropdown-menu .nav-item {
            display: block;
        }
    }
    .badge {
        padding: 0.5em 0.75em;
        font-size: 0.75rem;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.375rem;
    }
    .position-absolute {
        position: absolute !important;
    }
    .top-0 {
        top: 0 !important;
    }
    .start-100 {
        left: 100% !important;
    }
    .translate-middle {
        transform: translate(-50%, -50%) !important;
    }
    .rounded-circle {
        border-radius: 50% !important;
    }
    .bg-danger {
        background-color: #dc3545 !important;
    }
</style>
