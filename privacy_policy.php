<?php
include 'session_timeout.php';
include 'connection.php';

// -----------------------------------------------------------------------------------
// Check if the user has access REMOVE THIS AFTER GO LIVE
if (!isset($_SESSION['access_granted'])) {
    header('Location: comingsoon.php');
    exit();
}
// -----------------------------------------------------------------------------------

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
        .accordion-button:not(.collapsed) {
            color: #ffffff;
            background-color: #5cb85c;
        }
    </style>
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<!-- Privacy Policy Content STARTS here -->
<div id="content" class="container mt-5">
    <div class="col-md-8 offset-md-2">
        <h2>Privacy Policy</h2>
        <p><strong>Last updated 30 June, 2024</strong></p>

        <h3>Privacy Policy FAQ</h3>

        <div class="accordion" id="privacyFAQ">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                        Why is ShareNest updating its privacy statement?
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#privacyFAQ">
                    <div class="accordion-body">
                        We have updated our privacy statement to comply with the European Union's General Data Protection Regulation (GDPR) and to make our privacy practices clearer and more transparent.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        What is the GDPR?
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#privacyFAQ">
                    <div class="accordion-body">
                        The GDPR is a regulation aimed at strengthening and unifying data protection for all individuals within the European Union (EU). We believe that all our members can benefit from its requirements, so we are implementing it globally. The GDPR mandates greater openness and transparency from organisations regarding how they collect, store, and use personal data, and it imposes stricter limits on data usage.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        How often will you update your privacy statement?
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#privacyFAQ">
                    <div class="accordion-body">
                        We will update our privacy statement as necessary to comply with international regulations and to reflect changes in our services and activities.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFour">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        What information does ShareNest collect about me?
                    </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#privacyFAQ">
                    <div class="accordion-body">
                        When you visit the ShareNest website or use our mobile app, you remain anonymous unless you choose to provide personally identifiable information, such as when you register as a member or make a donation.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFive">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                        Why does ShareNest need my information?
                    </button>
                </h2>
                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#privacyFAQ">
                    <div class="accordion-body">
                        We require an email address and a user ID so that you can participate in sharing items via email on ShareNest.org and our mobile app. Additional information may be collected if you make a donation to ShareNest.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingSix">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                        How is my information used?
                    </button>
                </h2>
                <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#privacyFAQ">
                    <div class="accordion-body">
                        ShareNest uses the data we collect for the following purposes:
                        <ul>
                            <li>Member/User Support</li>
                            <li>Service Improvement</li>
                            <li>Development of Future Services</li>
                            <li>Security, Safety, and Dispute Resolution</li>
                            <li>Service Performance Analysis</li>
                            <li>Communications, Fundraising, and Marketing</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingSeven">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                        How does collecting my information help ShareNest improve its services?
                    </button>
                </h2>
                <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#privacyFAQ">
                    <div class="accordion-body">
                        We use the information to ensure that the ShareNest.org web and mobile app community operates smoothly and to offer you the best opportunities to share based on your location. We also collect error reports to make improvements and combat fraud.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingEight">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                        Can I opt out of sharing my information with ShareNest?
                    </button>
                </h2>
                <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#privacyFAQ">
                    <div class="accordion-body">
                        You can view postings on ShareNest.org and our mobile app without sharing any personal information. However, we require a basic level of data (username, email address) for membership and to interact with our services.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingNine">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNine" aria-expanded="false" aria-controls="collapseNine">
                        How do I delete my personal data from ShareNest and what are the consequences?
                    </button>
                </h2>
                <div id="collapseNine" class="accordion-collapse collapse" aria-labelledby="headingNine" data-bs-parent="#privacyFAQ">
                    <div class="accordion-body">
                        You can alter your personal data or delete your account by editing your account settings. If you have further issues, you can contact our support team. Note that deleting your personal data will render your membership non-functional.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTen">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTen" aria-expanded="false" aria-controls="collapseTen">
                        How long does ShareNest keep my information?
                    </button>
                </h2>
                <div id="collapseTen" class="accordion-collapse collapse" aria-labelledby="headingTen" data-bs-parent="#privacyFAQ">
                    <div class="accordion-body">
                        ShareNest retains personal data for as long as necessary to provide the services and fulfil the interactions you have requested, or for other essential purposes such as complying with legal obligations and resolving disputes.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingEleven">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEleven" aria-expanded="false" aria-controls="collapseEleven">
                        How is information stored and secured on ShareNest's servers?
                    </button>
                </h2>
                <div id="collapseEleven" class="accordion-collapse collapse" aria-labelledby="headingEleven" data-bs-parent="#privacyFAQ">
                    <div class="accordion-body">
                        ShareNest is committed to protecting your personal data. ShareNest.org uses HTTPS to ensure secure communications between your browser and our website. We also use various security technologies and procedures to protect your personal data from unauthorised access, use, or disclosure.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwelve">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwelve" aria-expanded="false" aria-controls="collapseTwelve">
                        Is ShareNest using cookies to gather information?
                    </button>
                </h2>
                <div id="collapseTwelve" class="accordion-collapse collapse" aria-labelledby="headingTwelve" data-bs-parent="#privacyFAQ">
                    <div class="accordion-body">
                        Yes. We use cookies to verify visitors' identities and provide services related to the use of member tools and options. By visiting our website or using our mobile app, you agree to the use of cookies for these purposes. You can adjust your browser or device settings to not accept cookies, but this may limit the functionality of our website for you.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingThirteen">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThirteen" aria-expanded="false" aria-controls="collapseThirteen">
                        Does ShareNest use interest-based advertising?
                    </button>
                </h2>
                <div id="collapseThirteen" class="accordion-collapse collapse" aria-labelledby="headingThirteen" data-bs-parent="#privacyFAQ">
                    <div class="accordion-body">
                        Yes. We work with third-party advertisers that use cookies to provide more relevant advertising about ShareNest on our website and across the internet. Advertisers combine non-personal data about your online activities over time to customise the advertising delivered to you. We do not share your personal information to do this.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFourteen">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFourteen" aria-expanded="false" aria-controls="collapseFourteen">
                        What safeguards are in place for children who have access to ShareNest services?
                    </button>
                </h2>
                <div id="collapseFourteen" class="accordion-collapse collapse" aria-labelledby="headingFourteen" data-bs-parent="#privacyFAQ">
                    <div class="accordion-body">
                        Children aged thirteen or older may participate only with the permission and supervision of their parents or guardians. If a child has registered improperly, we will cancel the child's account and delete the child's personal information.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFifteen">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFifteen" aria-expanded="false" aria-controls="collapseFifteen">
                        How can I contact ShareNest if I have questions regarding ShareNest's privacy statement?
                    </button>
                </h2>
                <div id="collapseFifteen" class="accordion-collapse collapse" aria-labelledby="headingFifteen" data-bs-parent="#privacyFAQ">
                    <div class="accordion-body">
                        If you have any questions about the use of your personal information, please contact us via our support page or at the address provided on our website.
                    </div>
                </div>
            </div>
        </div>

        <h3 class="mt-4">ShareNest Privacy Policy</h3>
        
        <p><strong>Our Commitment to Privacy</strong></p>
        <p>ShareNest respects your right to privacy. This section summarises the personally identifiable information we may collect, how we use this information, and other important topics relating to your privacy. ShareNest reserves the right to modify this privacy policy at any time, and we will promptly reflect any changes on our website.</p>
        
        <p><strong>Information Collection</strong></p>
        <p>When you visit the ShareNest website or use our mobile application, you remain anonymous unless you choose to provide personally identifiable information. We may ask for additional information when you register as a member or make a donation. We will only collect personally identifiable information that you voluntarily provide.</p>
        
        <p>We will not sell or exchange your personal information. We collect general information to improve our website and mobile app.</p>
        
        <p><strong>Use of Information</strong></p>
        <p>An email address and a user name are required for members so that you can participate in sharing items via email, the mobile app, or directly on ShareNest.org. We do not store any data beyond your email address, user name, and, optionally, your phone number. Further personal information would only be gathered if you make a donation to ShareNest. If required by law or pertinent to investigations, we may release your personal information. You can request the removal or modification of your personal information by contacting us.</p>
        
        <p><strong>Use of Cookies</strong></p>
        <p>The ShareNest website and mobile application may use cookies and similar technologies to verify visitors' identities and provide services. By using our website or mobile app, you agree to the use of cookies. You can adjust your browser or device settings to not accept cookies, but this may limit functionality.</p>
        
        <p><strong>Children</strong></p>
        <p>Children may participate only with the permission and supervision of their parents or guardians. We do not knowingly collect personal information from children under thirteen. If we discover that a child has registered improperly, we will cancel the account and delete the child's personal information.</p>
        
        <p><strong>Links</strong></p>
        <p>We are selective about the sites we link to, but we do not control these websites. We encourage you to review the privacy policies posted on third-party sites.</p>
    </div>
</div>
<!-- Privacy Policy Content ENDS here -->

<!-- Footer STARTS here -->
<?php include 'footer.php'; ?>
<!-- Footer ENDS here -->

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<button id="install-button" style="display: none;">Install Sharenest</button>
</body>
</html>
