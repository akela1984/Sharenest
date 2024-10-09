<?php
session_start();

include 'connection.php';

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
    <title>ShareNest - Our Guidelines</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<!-- Guidelines Content STARTS here -->
<div id="content" class="container mt-5">
    <div class="col-md-8 offset-md-2">
        <h2>Our Guidelines</h2>
        <p><strong>KEEP IT FREE, LEGAL & SUITABLE FOR ALL AGES</strong></p>
        <ul>
            <li>No alcohol, tobacco, firearms or other weapons, offensive language or adult content, medications (including over-the-counter supplements, vitamins, etc.).</li>
            <li>Never exchange money for anything offered on ShareNest (this includes money for postage or couriers).</li>
            <li>All copyrighted material must be in its original form.</li>
            <li>If you intend to resell an item, this must be declared upfront.</li>
            <li>No unregistered waste carriers.</li>
        </ul>
        <p><strong>USE WANTED POSTS SPARINGLY</strong></p>
        <ul>
            <li>Limit your Wanted posts to ensure fair access for all users. Numerical limits may vary.</li>
        </ul>
        <p><strong>NO POLITICS, NO SPAM, NO MONEY, NO PERSONAL ATTACKS OR RELIGIOUS SOLICITATION</strong></p>
        <ul>
            <li>Maintain a respectful and welcoming community environment.</li>
        </ul>
        <p><strong>NO SWAPPING OR TRADING</strong></p>
        <ul>
            <li>Please use dedicated swapping or trading platforms for these activities.</li>
        </ul>
        <p><strong>NO PERSONAL ADS</strong></p>
        <ul>
            <li>Posting personal advertisements or details about individuals is not permitted. Use appropriate dating websites for personal ads.</li>
        </ul>
        <p><strong>NO POSTS ABOUT ANIMALS FOR BREEDING OR FOOD PURPOSES</strong></p>
        <ul>
            <li>Some groups may allow posts to find new homes for pets, but breeding and food-related posts are not allowed. Ensure all pet-related posts are legal and considerate.</li>
        </ul>
        <p><strong>BE COURTEOUS</strong></p>
        <ul>
            <li>Practice good manners: be punctual and considerate when arranging to collect items. Remember, members are giving away items for free and your politeness and punctuality are appreciated.</li>
        </ul>
        <p><strong>ARRANGE COLLECTION</strong></p>
        <ul>
            <li>Arrange collections responsibly. Wait for a reasonable number of responses before selecting a recipient. Consider giving preference to local charities. Arrange collection with one person only to avoid confusion and maintain goodwill within the community.</li>
        </ul>
        <p><strong>DISCLAIMER</strong></p>
        <ul>
            <li>ShareNest members use the platform at their own risk. Protect your safety and privacy when posting or participating in exchanges. By using ShareNest, you agree to hold the platform, its owners, and moderators harmless from any issues arising from exchanges or communications.</li>
        </ul>
        <p><strong>SAFETY</strong></p>
        <ul>
            <li>Be cautious when arranging collections. If you are concerned about having strangers come to your home, consider alternative arrangements such as leaving items on the porch or meeting in public places. ShareNest assumes no responsibility for risks associated with exchanges.</li>
        </ul>
        <p><strong>SUSTAINABILITY</strong></p>
        <ul>
            <li>Promote sustainability by reusing and recycling items. Consider the environmental impact of your actions and encourage others to do the same.</li>
        </ul>
        <p><strong>INCLUSIVITY</strong></p>
        <ul>
            <li>Foster an inclusive community. Ensure that your posts and interactions are welcoming to all members, regardless of their background or circumstances.</li>
        </ul>
        <p><strong>FEEDBACK</strong></p>
        <ul>
            <li>Provide feedback to other members to help maintain a trustworthy and reliable community. Positive and constructive feedback can enhance the experience for everyone.</li>
        </ul>
        <p><strong>PRIVACY</strong></p>
        <ul>
            <li>Respect the privacy of other members. Do not share personal information without consent and ensure all exchanges are conducted with discretion.</li>
        </ul>
    </div>
</div>
<!-- Guidelines Content ENDS here -->

<!-- Footer STARTS here -->
<?php include 'footer.php'; ?>
<!-- Footer ENDS here -->

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <button id="install-button" style="display: none;">Install Sharenest</button>
</body>
</html>
