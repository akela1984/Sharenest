<!-- Footer STARTS here -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg col-md-6 mb-4 mb-lg-0">
                <div class="footer-logo">
                    <img src="img/sharenest_logo.png" alt="ShareNest Logo" style="height: 40px;">
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam ut tortor nisi.</p>
                </div>
            </div>
            <div class="col-lg col-md-6 mb-4 mb-lg-0">
                <div class="footer-links">
                    <ul class="list-unstyled">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="my_nest.php">My Nest</a></li>
                        <li><a href="create_listing.php">Create Listings</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-lg col-md-6 mb-4 mb-lg-0">
                <div class="footer-links">

                    <ul class="list-unstyled">
                        <li><a href="my_dashboard.php">My Dashboard</a></li>
                        <li><a href="my_messages.php">My messages</a></li>
                        <li><a href="join_location.php">My Locations</a></li>
                        <?php if ($is_admin) { ?>
                        <li><a href="admin_panel.php">Admin Panel</a></li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
            <div class="col-lg col-md-6 mb-4 mb-lg-0">
                <div class="footer-links">
                    <ul class="list-unstyled">
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="privacy_policy.php">Privacy</a></li>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="our_guidelines.php">Our Guidelines</a></li>
                        
                    </ul>
                </div>
            </div>
            <div class="col-lg col-md-6">
                <div class="follow-us">
                    <ul class="list-unstyled">
                        <li><a href="#"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="24" height="24"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64h98.2V334.2H109.4V256h52.8V222.3c0-87.1 39.4-127.5 125-127.5c16.2 0 44.2 3.2 55.7 6.4V172c-6-.6-16.5-1-29.6-1c-42 0-58.2 15.9-58.2 57.2V256h83.6l-14.4 78.2H255V480H384c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64z"/></svg> Facebook</a></li>
                        <li><a href="#"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="24" height="24"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg> Twitter/X</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- Footer ENDS here -->
