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
    <title>ShareNest - FAQ</title>
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

<!-- FAQ Content STARTS here -->
<div id="content" class="container mt-5">
    <div class="col-md-8 offset-md-2">
        <h2>Frequently Asked Questions</h2>

        <div class="accordion" id="faqAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                        How do I register for an account on ShareNest?
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        To register for an account, fill out the registration form with your username, email, and password. Ensure your password meets the security requirements and confirm your password. After submission, check your email for a verification link.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        What are the password requirements for registering?
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Your password must be at least 8 characters long, include at least one letter, one number, and one special character from @$!%*#?&.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        I did not receive a verification email. What should I do?
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        If you did not receive a verification email, check your spam or junk mail folder. If you still cannot find the email, please contact our support team for assistance.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFour">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        Can I use the same email address for multiple accounts?
                    </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        No, each email address can only be associated with one ShareNest account.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFive">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                        What should I do if I forgot my password?
                    </button>
                </h2>
                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        If you forgot your password, go to the sign-in page and click on the "Forgot Password" link. Follow the instructions to reset your password.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingSix">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                        How do I verify my account after registration?
                    </button>
                </h2>
                <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        After registering, you will receive an email with a verification link. Click on the link to verify your account. If you do not receive the email, check your spam folder or contact support.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingSeven">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                        What should I do if I receive an error during registration?
                    </button>
                </h2>
                <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        If you encounter an error during registration, ensure all fields are filled out correctly and try again. If the issue persists, please contact our support team for help.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingEight">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                        Can I change my username or email after registration?
                    </button>
                </h2>
                <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Currently, you cannot change your username after registration. However, you can update your email address in your account settings.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
    <h2 class="accordion-header" id="headingMyNestOne">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMyNestOne" aria-expanded="false" aria-controls="collapseMyNestOne">
            How do I view available listings in my area?
        </button>
    </h2>
    <div id="collapseMyNestOne" class="accordion-collapse collapse" aria-labelledby="headingMyNestOne" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            To view available listings, you need to join a location. Once you are part of a location, you can see the listings for sharing and wanted items in that area.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingMyNestTwo">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMyNestTwo" aria-expanded="false" aria-controls="collapseMyNestTwo">
            How do I filter listings by type?
        </button>
    </h2>
    <div id="collapseMyNestTwo" class="accordion-collapse collapse" aria-labelledby="headingMyNestTwo" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            You can filter listings by type using the filter buttons at the top of the listings section. Click "All" to see all listings, "For Sharing" to see items available for sharing, and "Wanted" to see items that are being requested.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingMyNestThree">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMyNestThree" aria-expanded="false" aria-controls="collapseMyNestThree">
            How do I search for specific listings?
        </button>
    </h2>
    <div id="collapseMyNestThree" class="accordion-collapse collapse" aria-labelledby="headingMyNestThree" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            Use the search bar at the top of the listings section to search for specific items. Enter your search term and press Enter. The listings will update to show items that match your search.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingMyNestFour">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMyNestFour" aria-expanded="false" aria-controls="collapseMyNestFour">
            How do I send a message to the lister?
        </button>
    </h2>
    <div id="collapseMyNestFour" class="accordion-collapse collapse" aria-labelledby="headingMyNestFour" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            To send a message to the lister, click on the "See details" button on a listing. In the modal that appears, type your message in the provided textarea and click "Send." Make sure your message is at least 2 characters long.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingMyNestFive">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMyNestFive" aria-expanded="false" aria-controls="collapseMyNestFive">
            What should I do if I encounter an error while sending a message?
        </button>
    </h2>
    <div id="collapseMyNestFive" class="accordion-collapse collapse" aria-labelledby="headingMyNestFive" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            If you encounter an error while sending a message, ensure all required fields are filled out correctly and try again. If the issue persists, please contact our support team for assistance.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingMyNestSix">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMyNestSix" aria-expanded="false" aria-controls="collapseMyNestSix">
            How do I report a listing?
        </button>
    </h2>
    <div id="collapseMyNestSix" class="accordion-collapse collapse" aria-labelledby="headingMyNestSix" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            To report a listing, click on the "Report this listing" link in the listing's modal. Confirm that you want to report the listing. The listing will be reviewed by the admin team.
        </div>
    </div>
</div>
<div class="accordion-item">
    <h2 class="accordion-header" id="headingMyNestSeven">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMyNestSeven" aria-expanded="false" aria-controls="collapseMyNestSeven">
            Why is the map not displayed for every listing?
        </button>
    </h2>
    <div id="collapseMyNestSeven" class="accordion-collapse collapse" aria-labelledby="headingMyNestSeven" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            The map may not be displayed for every listing if the user has not completed their address information in their profile. Ensure that your profile has a complete and accurate address to enable the map feature for your listings.
        </div>
    </div>
</div>
<div class="accordion-item">
    <h2 class="accordion-header" id="headingCreateListingOne">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCreateListingOne" aria-expanded="false" aria-controls="collapseCreateListingOne">
            Why can't I create a listing?
        </button>
    </h2>
    <div id="collapseCreateListingOne" class="accordion-collapse collapse" aria-labelledby="headingCreateListingOne" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            To create a listing, you must be associated with at least one location. If you are not part of any location, please <a href="join_location.php" class="alert-link">join a location</a> first.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingCreateListingTwo">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCreateListingTwo" aria-expanded="false" aria-controls="collapseCreateListingTwo">
            What information is required to create a listing?
        </button>
    </h2>
    <div id="collapseCreateListingTwo" class="accordion-collapse collapse" aria-labelledby="headingCreateListingTwo" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            You need to provide a title, description, location, type (For Sharing or Wanted), and optionally upload images for your listing.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingCreateListingThree">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCreateListingThree" aria-expanded="false" aria-controls="collapseCreateListingThree">
            How many images can I upload for my listing?
        </button>
    </h2>
    <div id="collapseCreateListingThree" class="accordion-collapse collapse" aria-labelledby="headingCreateListingThree" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            You can upload up to 5 images for your listing.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingCreateListingFour">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCreateListingFour" aria-expanded="false" aria-controls="collapseCreateListingFour">
            Why did I receive an error message while creating a listing?
        </button>
    </h2>
    <div id="collapseCreateListingFour" class="accordion-collapse collapse" aria-labelledby="headingCreateListingFour" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            Ensure that all required fields are filled out correctly. The title, description, location, and type are mandatory fields. If you continue to experience issues, please contact our support team.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingCreateListingFive">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCreateListingFive" aria-expanded="false" aria-controls="collapseCreateListingFive">
            How do I earn green points?
        </button>
    </h2>
    <div id="collapseCreateListingFive" class="accordion-collapse collapse" aria-labelledby="headingCreateListingFive" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            You earn green points by creating listings. Each successful listing creation adds one green point to your account.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingCreateListingSix">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCreateListingSix" aria-expanded="false" aria-controls="collapseCreateListingSix">
            How do I choose the right location for my listing?
        </button>
    </h2>
    <div id="collapseCreateListingSix" class="accordion-collapse collapse" aria-labelledby="headingCreateListingSix" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            In the location field, select the location from the dropdown menu where you want your listing to be visible. You must be associated with the chosen location.
        </div>
    </div>
</div>
<div class="accordion-item">
    <h2 class="accordion-header" id="headingEditListingOne">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEditListingOne" aria-expanded="false" aria-controls="collapseEditListingOne">
            Why can't I edit my listing?
        </button>
    </h2>
    <div id="collapseEditListingOne" class="accordion-collapse collapse" aria-labelledby="headingEditListingOne" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            You can only edit your listings if you are logged in and have permission to edit the specific listing. If you are not logged in or do not have permission, you will not be able to edit the listing.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingEditListingTwo">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEditListingTwo" aria-expanded="false" aria-controls="collapseEditListingTwo">
            How do I update the details of my listing?
        </button>
    </h2>
    <div id="collapseEditListingTwo" class="accordion-collapse collapse" aria-labelledby="headingEditListingTwo" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            To update the details of your listing, fill out the form with the new title, description, type, and status. Then, click the "Update Listing" button to save the changes.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingEditListingThree">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEditListingThree" aria-expanded="false" aria-controls="collapseEditListingThree">
            How do I delete my listing?
        </button>
    </h2>
    <div id="collapseEditListingThree" class="accordion-collapse collapse" aria-labelledby="headingEditListingThree" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            To delete your listing, click the "Delete Listing" button. A confirmation modal will appear. Confirm the deletion to permanently remove the listing.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingEditListingFour">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEditListingFour" aria-expanded="false" aria-controls="collapseEditListingFour">
            What happens to the images when I update my listing?
        </button>
    </h2>
    <div id="collapseEditListingFour" class="accordion-collapse collapse" aria-labelledby="headingEditListingFour" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            When you update your listing and upload new images, all previous images will be deleted, even if you upload only one new image. Make sure to upload all the images you want to keep.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingEditListingFive">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEditListingFive" aria-expanded="false" aria-controls="collapseEditListingFive">
            Why did I receive an error message while updating my listing?
        </button>
    </h2>
    <div id="collapseEditListingFive" class="accordion-collapse collapse" aria-labelledby="headingEditListingFive" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            Ensure that all required fields are filled out correctly. The title, description, type, and status are mandatory fields. If you continue to experience issues, please contact our support team.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingEditListingSix">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEditListingSix" aria-expanded="false" aria-controls="collapseEditListingSix">
            Can I change the status of my listing?
        </button>
    </h2>
    <div id="collapseEditListingSix" class="accordion-collapse collapse" aria-labelledby="headingEditListingSix" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            Yes, you can change the status of your listing to available, unavailable, pending collection, or under review by selecting the appropriate option from the status dropdown menu.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingEditListingSeven">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEditListingSeven" aria-expanded="false" aria-controls="collapseEditListingSeven">
            How do I earn green points?
        </button>
    </h2>
    <div id="collapseEditListingSeven" class="accordion-collapse collapse" aria-labelledby="headingEditListingSeven" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            You earn green points by creating and sharing listings. Each successful listing creation adds one green point to your account.
        </div>
    </div>
</div>
<div class="accordion-item">
    <h2 class="accordion-header" id="headingJoinLocationOne">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseJoinLocationOne" aria-expanded="false" aria-controls="collapseJoinLocationOne">
            Why can't I join a location?
        </button>
    </h2>
    <div id="collapseJoinLocationOne" class="accordion-collapse collapse" aria-labelledby="headingJoinLocationOne" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            You need to be logged in to join a location. If you are not logged in, please <a href="signin.php">sign in</a> first. Ensure that you are not already a member of the location you are trying to join.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingJoinLocationTwo">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseJoinLocationTwo" aria-expanded="false" aria-controls="collapseJoinLocationTwo">
            How do I join a location?
        </button>
    </h2>
    <div id="collapseJoinLocationTwo" class="accordion-collapse collapse" aria-labelledby="headingJoinLocationTwo" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            To join a location, find the location you want to join in the list and click the "Join" button. Ensure you are logged in and not already a member of that location.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingJoinLocationThree">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseJoinLocationThree" aria-expanded="false" aria-controls="collapseJoinLocationThree">
            How do I leave a location?
        </button>
    </h2>
    <div id="collapseJoinLocationThree" class="accordion-collapse collapse" aria-labelledby="headingJoinLocationThree" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            To leave a location, find the location you want to leave in the list and click the "Leave" button. Ensure you are logged in and a current member of that location.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingJoinLocationFour">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseJoinLocationFour" aria-expanded="false" aria-controls="collapseJoinLocationFour">
            What should I do if I receive an error while joining or leaving a location?
        </button>
    </h2>
    <div id="collapseJoinLocationFour" class="accordion-collapse collapse" aria-labelledby="headingJoinLocationFour" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            Ensure that you are logged in and have a valid session. Check that you are not already a member of the location you are trying to join or that you are a member of the location you are trying to leave. If the issue persists, please contact our support team.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingDashboardOne">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDashboardOne" aria-expanded="false" aria-controls="collapseDashboardOne">
            How do I delete a listing?
        </button>
    </h2>
    <div id="collapseDashboardOne" class="accordion-collapse collapse" aria-labelledby="headingDashboardOne" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            To delete a listing, click the "Delete" button next to the listing you want to remove. A confirmation message will appear asking if you are sure you want to delete the listing. Confirm to proceed with the deletion.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingDashboardTwo">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDashboardTwo" aria-expanded="false" aria-controls="collapseDashboardTwo">
            How do I edit a listing?
        </button>
    </h2>
    <div id="collapseDashboardTwo" class="accordion-collapse collapse" aria-labelledby="headingDashboardTwo" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            To edit a listing, click the "Edit" button next to the listing you want to modify. You will be redirected to an edit page where you can update the listing's details.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingDashboardThree">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDashboardThree" aria-expanded="false" aria-controls="collapseDashboardThree">
            How are the listings sorted?
        </button>
    </h2>
    <div id="collapseDashboardThree" class="accordion-collapse collapse" aria-labelledby="headingDashboardThree" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            Listings can be sorted by title, type, or date added. Click the column headers in the table to sort the listings accordingly. The sort order can be toggled between ascending and descending.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingDashboardFour">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDashboardFour" aria-expanded="false" aria-controls="collapseDashboardFour">
            What do the status badges mean?
        </button>
    </h2>
    <div id="collapseDashboardFour" class="accordion-collapse collapse" aria-labelledby="headingDashboardFour" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            The status badges indicate the current state of your listing:
            <ul>
                <li><span class="badge badge-available">Available</span> - The listing is active and available for others to view.</li>
                <li><span class="badge badge-unavailable">Unavailable</span> - The listing is currently not available.</li>
                <li><span class="badge badge-pending">Pending Collection</span> - The listing is pending collection.</li>
                <li><span class="badge badge-review">Under Review</span> - The listing is under review by the admin team.</li>
            </ul>
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingDashboardFive">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDashboardFive" aria-expanded="false" aria-controls="collapseDashboardFive">
            How do I earn green points?
        </button>
    </h2>
    <div id="collapseDashboardFive" class="accordion-collapse collapse" aria-labelledby="headingDashboardFive" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            You earn green points by creating and sharing listings. Each successful listing creation adds one green point to your account.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingDashboardSix">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDashboardSix" aria-expanded="false" aria-controls="collapseDashboardSix">
            How do I view my conversations?
        </button>
    </h2>
    <div id="collapseDashboardSix" class="accordion-collapse collapse" aria-labelledby="headingDashboardSix" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            To view your conversations, navigate to the "Conversations" section in your dashboard. Here you can see all your conversations, including unread messages.
        </div>
    </div>
</div>
<div class="accordion-item">
    <h2 class="accordion-header" id="headingProfileOne">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProfileOne" aria-expanded="false" aria-controls="collapseProfileOne">
            How do I update my profile?
        </button>
    </h2>
    <div id="collapseProfileOne" class="accordion-collapse collapse" aria-labelledby="headingProfileOne" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            To update your profile, click the "Edit" button. This will make the fields editable. After making changes, click the "Save" button to save the updates. You must provide your current password to confirm changes.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingProfileTwo">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProfileTwo" aria-expanded="false" aria-controls="collapseProfileTwo">
            How do I change my password?
        </button>
    </h2>
    <div id="collapseProfileTwo" class="accordion-collapse collapse" aria-labelledby="headingProfileTwo" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            To change your password, enter your new password in the "New Password" field and confirm it in the "Confirm New Password" field. You must also provide your current password to save the new password.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingProfileThree">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProfileThree" aria-expanded="false" aria-controls="collapseProfileThree">
            How do I upload a new profile image?
        </button>
    </h2>
    <div id="collapseProfileThree" class="accordion-collapse collapse" aria-labelledby="headingProfileThree" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            To upload a new profile image, click the "Edit" button to enable the file input. Select your new image and it will be uploaded when you save your profile changes. Accepted formats are JPG, JPEG, PNG, and GIF.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingProfileFour">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProfileFour" aria-expanded="false" aria-controls="collapseProfileFour">
            What do I do if I receive an error while updating my profile?
        </button>
    </h2>
    <div id="collapseProfileFour" class="accordion-collapse collapse" aria-labelledby="headingProfileFour" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            Ensure all required fields are filled out correctly and your current password is provided. If you continue to experience issues, please contact our support team.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingProfileFive">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProfileFive" aria-expanded="false" aria-controls="collapseProfileFive">
            How do I delete my account?
        </button>
    </h2>
    <div id="collapseProfileFive" class="accordion-collapse collapse" aria-labelledby="headingProfileFive" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            To delete your account, enter your current password in the "Delete Account" section and click the "Delete Account" button. This action is irreversible and will permanently remove your account and all associated data.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingProfileSix">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProfileSix" aria-expanded="false" aria-controls="collapseProfileSix">
            Why do I need to provide my current password to save changes?
        </button>
    </h2>
    <div id="collapseProfileSix" class="accordion-collapse collapse" aria-labelledby="headingProfileSix" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            Providing your current password ensures that only you can make changes to your profile. This is a security measure to protect your account from unauthorized access.
        </div>
    </div>
</div>

<div class="accordion-item">
    <h2 class="accordion-header" id="headingProfileSeven">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProfileSeven" aria-expanded="false" aria-controls="collapseProfileSeven">
            What file formats are accepted for profile images?
        </button>
    </h2>
    <div id="collapseProfileSeven" class="accordion-collapse collapse" aria-labelledby="headingProfileSeven" data-bs-parent="#faqAccordion">
        <div class="accordion-body">
            Accepted file formats for profile images are JPG, JPEG, PNG, and GIF. Ensure your image is in one of these formats before uploading.
        </div>
    </div>
</div>




            
        </div>
    </div>
</div>
<!-- FAQ Content ENDS here -->

<!-- Footer STARTS here -->
<?php include 'footer.php'; ?>
<!-- Footer ENDS here -->

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
