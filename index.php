<?php
session_start(); // Start session
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ShareNest - Coming Soon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://getbootstrap.com/docs/5.3/assets/css/docs.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
  </head>
  <body class="p-3 m-0 border-0 bd-example m-0 border-0">

   <?php include 'connection.php'; ?>

    <!-- Navbar STARTS here -->
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">ShareNest</a> 
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
            <ul class="navbar-nav mb-2 mb-lg-0">
            <?php if(isset($_SESSION['loggedin'])) { ?>
                <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="me-2">
            <!-- Add user icon using Font Awesome -->
            <i class="fa fa-user"></i>
        </span>
        Hi, <?php echo $_SESSION['username']; ?>
    </a>
    <ul class="dropdown-menu">
        <li class="nav-item"><a class="nav-link" href="profile.php">My Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
    </ul>

</li>

            <?php } else { ?>
                <li class="nav-item">
                    <li class="nav-item"><a class="btn btn-outline-success" href="signin.php">Sign in</a></li>
                </li>
            <?php } ?>
            </ul>
        </div>
    </div>
</nav>

    <!-- Navbar ENDS here -->

<!-- Register Modal STARTS here -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="registerModalLabel">Register</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Create your account by filling out the form below -->
                <form method="post" action="register.php">
                    <div class="mb-3">
                        <label for="registerUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="registerUsername" name="registerUsername" aria-describedby="usernameHelp" required>
                        <div id="usernameHelp" class="form-text">Choose a unique username.</div>
                    </div>
                    <div class="mb-3">
                        <label for="registerEmail" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="registerEmail" name="registerEmail" aria-describedby="emailHelp" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$" required>
                        <div id="emailHelp" class="form-text">Enter a valid email address. (e.g., example@example.com)</div>
                    </div>
                    <div class="mb-3">
                        <label for="registerPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="registerPassword" name="registerPassword" aria-describedby="passwordHelp" pattern="(?=.*\d)(?=.*[!@#$%^&*])(?=.*[a-z])(?=.*[A-Z]).{8,}" required>
                        <div id="passwordHelp" class="form-text">Password must be at least 8 characters long and include at least one number, one uppercase and lowercase letter, and one special character (e.g., !@#$%^&*)</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" aria-describedby="confirmPasswordHelp" required>
                        <div id="confirmPasswordHelp" class="form-text">Confirm your password.</div>
                    </div>
                    <!-- Click the button below to create your account -->
                    <button type="submit" class="btn btn-outline-primary">Register</button>
                </form>
            </div>
            <div class="modal-footer">
                <!-- Already have an account? Sign in here -->
                <p>Already have an account? <a href="#signInModal" data-bs-toggle="modal" data-bs-dismiss="modal">Sign in here</a></p>
            </div>
        </div>
    </div>
</div>
<!-- Register Modal ENDS here -->


    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
