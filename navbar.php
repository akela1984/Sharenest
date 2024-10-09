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
                <?php if (isset($_SESSION['loggedin'])) { ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                           aria-expanded="false">
                            <span class="me-2">
                                <i class="fa fa-user"></i>
                            </span>
                            Hi, <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="nav-item"><a class="nav-link" href="profile.php">My Profile</a></li>
                            <li class="nav-item"><a class="nav-link" href="join_location.php">My Locations</a></li>
                            <li class="nav-item"><a class="nav-link" href="my_listings.php">My Listings</a></li>
                            <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
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