<?php
include 'session_timeout.php';
include 'connection.php';

// Function to get the user's IP address
function getUserIP() {
    // Check for shared internet IPs
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// Get user IP address
$ip_address = getUserIP();

// Check if the IP address has already participated in the survey
$query = "SELECT id FROM SurveyResponses WHERE ip_address = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $ip_address);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    header("Location: already_participated.php");
    exit();
}

$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $consent = $_POST['consent'];
    
    // Check if user gave consent
    if ($consent === 'No' || $_POST['confirmation'] === "NO, this sounds like a lot of things to do. I've changed my mind") {
        header("Location: no_consent.php");
        exit();
    }
    
    // Collect form inputs
    $recycle_frequency = $_POST['recycle_frequency'] ?? null;
    $awareness_scale = $_POST['awareness_scale'] ?? null;
    $materials_recycled = implode(",", $_POST['materials_recycled'] ?? []);
    $dispose_items = implode(",", $_POST['dispose_items'] ?? []);
    $used_platform = $_POST['used_platform'] ?? null;
    $platforms_used = $_POST['platforms_used'] ?? null;
    $motivation = implode(",", $_POST['motivation'] ?? []);
    $barriers = implode(",", $_POST['barriers'] ?? []);
    $co2_awareness = $_POST['co2_awareness'] ?? null;
    $confirmation = $_POST['confirmation'] ?? null;
    $device_used = $_POST['device_used'] ?? null;
    $navigation_rating = $_POST['navigation_rating'] ?? null;
    $visual_appeal_rating = $_POST['visual_appeal_rating'] ?? null;
    $content_clarity_rating = $_POST['content_clarity_rating'] ?? null;
    $design_purpose_rating = $_POST['design_purpose_rating'] ?? null;
    $color_palette_rating = $_POST['color_palette_rating'] ?? null;
    $layout_organisation_rating = $_POST['layout_organisation_rating'] ?? null;
    $aesthetic_rating = $_POST['aesthetic_rating'] ?? null;
    $recommend_rating = $_POST['recommend_rating'] ?? null;
    $difficulties = $_POST['difficulties'] ?? null;
    $search_usefulness = $_POST['search_usefulness'] ?? null;
    $listing_creation = $_POST['listing_creation'] ?? null;
    $edit_listing = $_POST['edit_listing'] ?? null;
    $map_usefulness = $_POST['map_usefulness'] ?? null;
    $messaging_usefulness = $_POST['messaging_usefulness'] ?? null;
    $gps_button_usefulness = $_POST['gps_button_usefulness'] ?? null;
    $points_system = $_POST['points_system'] ?? null;
    $reuse_recycle = $_POST['reuse_recycle'] ?? null;
    $encourage_others = $_POST['encourage_others'] ?? null;
    $effective_features = $_POST['effective_features'] ?? null;
    $location_map = $_POST['location_map'] ?? null;
    $points_system_motivation = $_POST['points_system_motivation'] ?? null;
    $co2_motivation = $_POST['co2_motivation'] ?? null;
    $suggestions = $_POST['suggestions'] ?? null;
    $total_time_spent = $_POST['total_time_spent'] ?? 0; // Time captured by the timer

    // Insert data into the database
    $query = "INSERT INTO SurveyResponses (
        ip_address, consent, recycle_frequency, awareness_scale, materials_recycled, dispose_items, 
        used_platform, platforms_used, motivation, barriers, co2_awareness, confirmation, device_used, 
        navigation_rating, visual_appeal_rating, content_clarity_rating, design_purpose_rating, 
        color_palette_rating, layout_organisation_rating, aesthetic_rating, recommend_rating, 
        difficulties, search_usefulness, listing_creation, edit_listing, map_usefulness, 
        messaging_usefulness, gps_button_usefulness, points_system, reuse_recycle, encourage_others, 
        effective_features, location_map, points_system_motivation, co2_motivation, suggestions, 
        total_time_spent
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "sssssssssssssssssssssssssssssssssssis", 
        $ip_address, $consent, $recycle_frequency, $awareness_scale, $materials_recycled, 
        $dispose_items, $used_platform, $platforms_used, $motivation, $barriers, $co2_awareness, 
        $confirmation, $device_used, $navigation_rating, $visual_appeal_rating, 
        $content_clarity_rating, $design_purpose_rating, $color_palette_rating, 
        $layout_organisation_rating, $aesthetic_rating, $recommend_rating, $difficulties, 
        $search_usefulness, $listing_creation, $edit_listing, $map_usefulness, 
        $messaging_usefulness, $gps_button_usefulness, $points_system, $reuse_recycle, 
        $encourage_others, $effective_features, $location_map, $points_system_motivation, 
        $co2_motivation, $suggestions, $total_time_spent
    );
    
    if ($stmt->execute()) {
        header("Location: thank_you_page.php");
        exit();
    } else {
        echo "<script>alert('An error occurred while saving your response. Please try again.');</script>";
    }
    
    $stmt->close();
}
?>

<!doctype html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="css/styles.css" rel="stylesheet">
<style>
    .accordion-button:not(.collapsed) {
        color: #ffffff;
        background-color: #5cb85c;
    }
    .section-title {
        font-weight: bold;
        color: #4CAF50;
        margin-bottom: 20px;
        border-bottom: 2px solid #ddd;
        padding-bottom: 10px;
    }
    .consent-text {
        margin-bottom: 15px;
        font-size: 14px;
        line-height: 1.6;
    }
    .question {
        margin-top: 15px;
        padding: 10px;
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    .instructions {
        background-color: #eef3f7;
        padding: 15px;
        border-left: 4px solid #4CAF50;
        margin-bottom: 20px;
        line-height: 1.6;
    }
    .hidden {
        display: none;
    }
</style>
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Privacy Questionnaire Content STARTS here -->
<div id="content" class="container mt-5">
    <form method="POST" action="">
        <div class="col-md-8 offset-md-2">
            <h1 class="section-title">Assessing User Perceptions and Behavioural Impacts of the ShareNest Platform on Sustainable Waste Management</h1>

            <!-- Section 1: Consent Form -->
            <div id="section1" class="section">
                <h2 class="section-title">Consent Form</h2>
                <div class="consent-text">
                    <p><strong>Edinburgh Napier University Research Consent Form</strong></p>
                    <p>Evaluating the ShareNest Platform for Promoting Sustainable Waste Management</p>
                    <p><strong>Website:</strong> <a href="https://sharenest.org" target="_blank">https://sharenest.org</a></p>
                    <p>You are invited to participate in a research study conducted by Attila Z. Gajdos, an undergraduate student at Edinburgh Napier University. This study aims to evaluate the ShareNest website, a platform designed to facilitate the exchange of unwanted goods and promote sustainable waste management practices. Your participation will help us understand how the website’s design and features influence user behaviour and contribute to waste reduction efforts.</p>
                    <p>If you agree to participate, you will be asked to complete an online survey. The survey will involve answering questions about your recycling habits, awareness of the environmental benefits of reusing goods, and your perceptions of the ShareNest website’s design and features. You will also be asked to navigate through the website and provide feedback on its usability and effectiveness in encouraging sustainable behaviours. Your responses will be kept confidential and used only for research purposes.</p>
                    <p>Edinburgh Napier University requires that all persons who participate in research studies give their written consent to do so. Please read the following and sign if you agree with what it says:</p>
                    <ul>
                        <li>I freely and voluntarily consent to be a participant in the research project on the topic of evaluating the ShareNest Platform, conducted by Attila Z. Gajdos, an undergraduate student at Edinburgh Napier University.</li>
                        <li>The broad goal of this research study is to explore how digital platforms can contribute to sustainable waste management practices. Specifically, I have been asked to complete an online survey and navigate the ShareNest website, which should take no longer than 10 to 15 minutes to complete.</li>
                        <li>I have been informed that my responses will be anonymised. My name will not be linked with the research materials, and I will not be identified or identifiable in any report produced by the researcher.</li>
                        <li>I understand that my participation is completely voluntary. I may withdraw from the study at any time without negative consequences. However, once data has been anonymised or after publication of the results, it will not be possible to remove my data as it would be untraceable at that point.</li>
                        <li>I understand that I am free to decline to answer any particular question or questions.</li>
                        <li>I have been given the opportunity to ask questions regarding the survey and my participation, and these have been answered to my satisfaction.</li>
                        <li>I have read and understood the above and consent to participate in this study. My signature does not waive any legal rights. I understand that I will be able to keep a copy of this consent form for my records.</li>
                    </ul>
                    <p>By proceeding to the survey, you indicate that you have read and understood the information provided in this consent form and voluntarily agree to participate in the study under the terms outlined above.</p>
                    <p>By answering the first question below, you confirm your consent to participate in this research study.</p>
                    <p><strong>Researcher’s signature:</strong> Attila Z. Gajdos</p>
                    <p><strong>Date:</strong> 01/10/2024</p>
                </div>

                <div class="question">
                    <label for="consent">Do you consent to take part in the study? *</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="consent" id="consentYes" value="Yes" required>
                        <label class="form-check-label" for="consentYes">Yes</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="consent" id="consentNo" value="No" required>
                        <label class="form-check-label" for="consentNo">No</label>
                    </div>
                </div>
                <button class="btn btn-primary mt-3" id="consentNext">Next</button>
            </div>

            <!-- Section 2: Recycling Habits and Awareness -->
            <div id="section2" class="section hidden">
                <h2 class="section-title">Recycling Habits and Awareness</h2>
                <div class="question">
                    <label for="recycle_frequency">How often do you recycle household waste?</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="recycle_frequency" id="always" value="Always" required>
                        <label class="form-check-label" for="always">Always</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="recycle_frequency" id="often" value="Often">
                        <label class="form-check-label" for="often">Often</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="recycle_frequency" id="sometimes" value="Sometimes">
                        <label class="form-check-label" for="sometimes">Sometimes</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="recycle_frequency" id="rarely" value="Rarely">
                        <label class="form-check-label" for="rarely">Rarely</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="recycle_frequency" id="never" value="Never">
                        <label class="form-check-label" for="never">Never</label>
                    </div>
                </div>

                <div class="question">
                    <label for="awareness_scale">On a scale of 1 to 5, how would you rate your awareness of the environmental benefits of reusing goods?</label>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Not at all aware</span>
                        <input type="range" class="form-range" name="awareness_scale" min="1" max="5" step="1" required>
                        <span>Very aware</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>1</span>
                        <span>2</span>
                        <span>3</span>
                        <span>4</span>
                        <span>5</span>
                    </div>
                </div>

                <div class="question">
                    <label for="materials_recycled">What types of materials do you typically recycle at home? (Select all that apply)</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="materials_recycled[]" id="paper" value="Paper and cardboard">
                        <label class="form-check-label" for="paper">Paper and cardboard</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="materials_recycled[]" id="plastic" value="Plastic bottles and containers">
                        <label class="form-check-label" for="plastic">Plastic bottles and containers</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="materials_recycled[]" id="glass" value="Glass bottles and jars">
                        <label class="form-check-label" for="glass">Glass bottles and jars</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="materials_recycled[]" id="metal" value="Metal cans">
                        <label class="form-check-label" for="metal">Metal cans</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="materials_recycled[]" id="food_waste" value="Food waste">
                        <label class="form-check-label" for="food_waste">Food waste</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="materials_recycled[]" id="electronics" value="Electronics">
                        <label class="form-check-label" for="electronics">Electronics</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="materials_recycled[]" id="other" value="Other">
                        <label class="form-check-label" for="other">Other</label>
                    </div>
                </div>

                <div class="question">
                    <label for="dispose_items">How do you typically dispose of items that you no longer need but are still in good condition? (Select all that apply)</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="dispose_items[]" id="donate" value="Donate to charity">
                        <label class="form-check-label" for="donate">Donate to charity</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="dispose_items[]" id="give_to_friends" value="Give to friends or family">
                        <label class="form-check-label" for="give_to_friends">Give to friends or family</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="dispose_items[]" id="sell_online" value="Sell online or at a garage/carboot sale">
                        <label class="form-check-label" for="sell_online">Sell online or at a garage/carboot sale</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="dispose_items[]" id="throw_away" value="Throw away">
                        <label class="form-check-label" for="throw_away">Throw away</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="dispose_items[]" id="dispose_other" value="Other">
                        <label class="form-check-label" for="dispose_other">Other</label>
                    </div>
                </div>

                <div class="question">
                    <label for="used_platform">Have you ever used an online platform or app to exchange or donate unwanted goods?</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="used_platform" id="used_yes" value="Yes" required>
                        <label class="form-check-label" for="used_yes">Yes</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="used_platform" id="used_no" value="No">
                        <label class="form-check-label" for="used_no">No</label>
                    </div>
                </div>

                <div class="question">
                    <label for="platforms_used">If yes, which platform(s) have you used?</label>
                    <textarea class="form-control" name="platforms_used" id="platforms_used" rows="2"></textarea>
                </div>

                <div class="question">
                    <label for="motivation">What motivates you to recycle or reuse items? (Select all that apply)</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="motivation[]" id="environmental_impact" value="Reducing environmental impact">
                        <label class="form-check-label" for="environmental_impact">Reducing environmental impact</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="motivation[]" id="saving_money" value="Saving money">
                        <label class="form-check-label" for="saving_money">Saving money</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="motivation[]" id="decluttering" value="Decluttering">
                        <label class="form-check-label" for="decluttering">Decluttering</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="motivation[]" id="helping_others" value="Helping others">
                        <label class="form-check-label" for="helping_others">Helping others</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="motivation[]" id="motivation_other" value="Other">
                        <label class="form-check-label" for="motivation_other">Other</label>
                    </div>
                </div>

                <div class="question">
                    <label for="barriers">What challenges or barriers prevent you from recycling or reusing items more frequently? (Select all that apply)</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="barriers[]" id="lack_of_time" value="Lack of time">
                        <label class="form-check-label" for="lack_of_time">Lack of time</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="barriers[]" id="inconvenience" value="Inconvenience">
                        <label class="form-check-label" for="inconvenience">Inconvenience</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="barriers[]" id="lack_of_awareness" value="Lack of awareness about recycling options">
                        <label class="form-check-label" for="lack_of_awareness">Lack of awareness about recycling options</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="barriers[]" id="lack_of_access" value="Lack of access to recycling facilities">
                        <label class="form-check-label" for="lack_of_access">Lack of access to recycling facilities</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="barriers[]" id="barriers_other" value="Other">
                        <label class="form-check-label" for="barriers_other">Other</label>
                    </div>
                </div>

                <div class="question">
                    <label for="co2_awareness">On a scale of 1 to 5, how aware are you of the positive impact recycling and reusing items has on reducing CO2 emissions?</label>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Not at all aware</span>
                        <input type="range" class="form-range" name="co2_awareness" min="1" max="5" step="1" required>
                        <span>Very aware</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>1</span>
                        <span>2</span>
                        <span>3</span>
                        <span>4</span>
                        <span>5</span>
                    </div>
                </div>

                <button class="btn btn-secondary mt-3" id="section2Prev">Previous</button>
                <button class="btn btn-primary mt-3" id="section2Next">Next</button>
            </div>

            <!-- Section 3: Instructions -->
            <div id="section3" class="section hidden">
                <h2 class="section-title">Instructions</h2>
                <br><div class="instructions">
    <p>To provide the most accurate and meaningful feedback on the design and functionality of ShareNest, please take a few minutes to explore the website. Familiarise yourself with its features, navigate through various pages, and engage with the overall user experience. You can keep the website open in a separate tab while completing the survey. Access the website here: <a href="https://www.sharenest.org">ShareNest</a>.</p>

    <h3>Getting Started:</h3>
    <p>To fully experience the features and evaluate the website’s functionality, you’ll need to access pages that are exclusive to registered users.</p>

    <ul>
        <li><strong>Register an Account:</strong> For the best experience, register using your own details. This will give you access to all features and allow you to explore ShareNest as a genuine user. Make sure to use a valid email address, as a confirmation link will be sent to complete your registration. Check your junk or spam folder if you don't see the confirmation email in your inbox.</li>
        <li><strong>Demo Accounts Available:</strong> If you prefer not to register your own details, you can log in using one of the demo accounts below:</li>
        <ul>
            <li>Username: <strong>test_user</strong> | Password: <strong>Akela1984@</strong></li>
            <li>Username: <strong>test_user_2</strong> | Password: <strong>Akela1984@</strong></li>
        </ul>
    </ul>

    <h3>Tasks to Complete:</h3>
    <p>To gain a comprehensive understanding of ShareNest’s design and functionality, please complete the following tasks:</p>

    <ul>
        <li><strong>Register and Log In:</strong> Create your own account or use the demo account to log in.</li>
        <li><strong>Join the Edinburgh Group:</strong> Navigate to the group section and join the Edinburgh location, which contains demo listings.</li>
        <li><strong>Search for a Specific Item:</strong> Use the search feature to look for a specific item, such as a table. There are multiple demo listings related to tables to explore.</li>
        <li><strong>Send a Message:</strong> Contact a user by sending a message to one of the listings. This will help you experience the communication feature firsthand.</li>
    </ul>

    <h3>Additional Features to Explore (Optional):</h3>
    <ul>
        <li><strong>Create a Listing:</strong> List an item you no longer need to see how easy it is to add content.</li>
        <li><strong>Edit Your Profile:</strong> Update your profile details and preferences.</li>
        <li><strong>Request an Item:</strong> Use the messaging feature to request an item from another user.</li>
        <li><strong>Report a Listing:</strong> Try out the reporting feature if you find any inappropriate or suspicious content.</li>
        <li><strong>Delete Your Profile:</strong> Test the account deletion process if you decide you no longer want to keep your account.</li>
    </ul>

    <p><strong>Important:</strong> Exploring these features will help you understand the platform’s functionality, enabling you to provide detailed and insightful feedback in the survey. If you run into any issues during registration or while using the site, please contact me for assistance.</p>
</div>


                <div class="question">
                    <label for="confirmation">Confirmation *</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="confirmation" id="confirm_yes" value="I confirm that I have visited the ShareNest website" required>
                        <label class="form-check-label" for="confirm_yes">I confirm that I have visited the ShareNest website, either by registering an account or using the provided demo account, and have explored some or all of the recommended features.</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="confirmation" id="confirm_no" value="NO, this sounds like a lot of things to do. I've changed my mind">
                        <label class="form-check-label" for="confirm_no">NO, this sounds like a lot of things to do. I've changed my mind, and I don't want to participate in this survey anymore.</label>
                    </div>
                </div>

                <button class="btn btn-secondary mt-3" id="section3Prev">Previous</button>
                <button class="btn btn-primary mt-3" id="section3Next">Next</button>
            </div>

            <!-- Section 4: General Usability and User Experience -->
            <div id="section4" class="section hidden">
                <h2 class="section-title">General Usability and User Experience</h2>
                <div class="question">
                    <label for="device_used">Which device did you primarily use to access and navigate the ShareNest website?</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="device_used" id="desktop" value="Desktop computer" required>
                        <label class="form-check-label" for="desktop">Desktop computer</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="device_used" id="laptop" value="Laptop">
                        <label class="form-check-label" for="laptop">Laptop</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="device_used" id="tablet" value="Tablet">
                        <label class="form-check-label" for="tablet">Tablet</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="device_used" id="smartphone" value="Smartphone">
                        <label class="form-check-label" for="smartphone">Smartphone</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="device_used" id="other_device" value="Other">
                        <label class="form-check-label" for="other_device">Other</label>
                    </div>
                </div>

                <div class="question">
                    <label>Please rate the following aspects of the ShareNest website on a scale of 1 to 5, where 1 is 'Strongly Disagree' and 5 is 'Strongly Agree':</label>
                    <div class="form-group">
                        <label>The website is easy to navigate.</label>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Strongly Disagree</span>
                            <input type="range" class="form-range" name="navigation_rating" min="1" max="5" step="1" required>
                            <span>Strongly Agree</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>1</span>
                            <span>2</span>
                            <span>3</span>
                            <span>4</span>
                            <span>5</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>The website is visually appealing.</label>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Strongly Disagree</span>
                            <input type="range" class="form-range" name="visual_appeal_rating" min="1" max="5" step="1" required>
                            <span>Strongly Agree</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>1</span>
                            <span>2</span>
                            <span>3</span>
                            <span>4</span>
                            <span>5</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>The website's content is clear and informative.</label>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Strongly Disagree</span>
                            <input type="range" class="form-range" name="content_clarity_rating" min="1" max="5" step="1" required>
                            <span>Strongly Agree</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>1</span>
                            <span>2</span>
                            <span>3</span>
                            <span>4</span>
                            <span>5</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>The website's design effectively reflects its purpose of promoting sustainable waste management.</label>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Strongly Disagree</span>
                            <input type="range" class="form-range" name="design_purpose_rating" min="1" max="5" step="1" required>
                            <span>Strongly Agree</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>1</span>
                            <span>2</span>
                            <span>3</span>
                            <span>4</span>
                            <span>5</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>The colour palette used on the website is visually pleasing and appropriate for the theme.</label>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Strongly Disagree</span>
                            <input type="range" class="form-range" name="color_palette_rating" min="1" max="5" step="1" required>
                            <span>Strongly Agree</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>1</span>
                            <span>2</span>
                            <span>3</span>
                            <span>4</span>
                            <span>5</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>The website's layout and organisation make it easy to find the information I need.</label>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Strongly Disagree</span>
                            <input type="range" class="form-range" name="layout_organisation_rating" min="1" max="5" step="1" required>
                            <span>Strongly Agree</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>1</span>
                            <span>2</span>
                            <span>3</span>
                            <span>4</span>
                            <span>5</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>The website's overall aesthetic contributes to a positive user experience.</label>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Strongly Disagree</span>
                            <input type="range" class="form-range" name="aesthetic_rating" min="1" max="5" step="1" required>
                            <span>Strongly Agree</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>1</span>
                            <span>2</span>
                            <span>3</span>
                            <span>4</span>
                            <span>5</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>I would recommend this website to others based on its design and usability.</label>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Strongly Disagree</span>
                            <input type="range" class="form-range" name="recommend_rating" min="1" max="5" step="1" required>
                            <span>Strongly Agree</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>1</span>
                            <span>2</span>
                            <span>3</span>
                            <span>4</span>
                            <span>5</span>
                        </div>
                    </div>
                </div>

                <div class="question">
                    <label for="difficulties">Did you encounter any difficulties or frustrations while using the ShareNest website? If so, please describe them briefly.</label>
                    <textarea class="form-control" name="difficulties" id="difficulties" rows="3"></textarea>
                </div>

                <button class="btn btn-secondary mt-3" id="section4Prev">Previous</button>
                <button class="btn btn-primary mt-3" id="section4Next">Next</button>
            </div>

            <!-- Section 5: Specific Design Elements and Functionalities -->
            <div id="section5" class="section hidden">
                <h2 class="section-title">Specific Design Elements and Functionalities</h2>

                <br><div class="instructions">
    <p><strong>Search Functionality:</strong> The search bar is located on the "My Nest" page. As you type into the search bar, the system dynamically displays the top 5 listings that match your keywords under the heading "Top findings for you." Clicking on any of these suggestions will take you directly to the corresponding listing. To view all listings related to your search query, simply press the "Enter" key.</p>
    <img src="img/survey_images/search_function.png" alt="Search Functionality Example" class="img-fluid" />
</div>


                <div class="question">
                    <label for="search_usefulness">The search function helped me find relevant items quickly and easily. (1 = Not at all useful, 5 = Very useful)</label>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Not at all useful</span>
                        <input type="range" class="form-range" name="search_usefulness" min="1" max="5" step="1" required>
                        <span>Very useful</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>1</span>
                        <span>2</span>
                        <span>3</span>
                        <span>4</span>
                        <span>5</span>
                    </div>
                </div>

                <br><div class="instructions">
                    <p><strong>Listings:</strong> You can create a new listing by clicking the "Create Listing" button in the navigation bar. To manage your existing listings (edit or delete), go to the Dashboard page. You can access the Dashboard by either clicking the dashboard icon in the navigation bar or selecting the "Dashboard" link from the dropdown menu. On the Dashboard, you'll see a list of your listings, each with an "Edit" and "Delete" button. To view details of any listing on the "My Nest" page, simply click the "See Details" button associated with that listing.</p>
                    <img src="img/survey_images/create_listing_function.png" alt="Listigs Functionality Example" class="img-fluid" />
                </div>

                <div class="question">
                    <label for="listing_creation">Creating a new listing was straightforward and intuitive. (1 = Not at all useful, 5 = Very useful)</label>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Not at all useful</span>
                        <input type="range" class="form-range" name="listing_creation" min="1" max="5" step="1" required>
                        <span>Very useful</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>1</span>
                        <span>2</span>
                        <span>3</span>
                        <span>4</span>
                        <span>5</span>
                    </div>
                </div>

                <div class="question">
                    <label for="edit_listing">How easy did you find it to edit or delete your own listings on the ShareNest website? (1 = Very difficult, 5 = Very easy)</label>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Very difficult</span>
                        <input type="range" class="form-range" name="edit_listing" min="1" max="5" step="1" required>
                        <span>Very easy</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>1</span>
                        <span>2</span>
                        <span>3</span>
                        <span>4</span>
                        <span>5</span>
                    </div>
                </div>

                <br><div class="instructions">
    <p><strong>Map Functionality:</strong> When viewing the details of a listing on the "My Nest" page, you will see an integrated map displaying the approximate location of the listed item. This feature enhances the usability of the platform by allowing users to quickly assess the proximity of items. Please note that the map will only be visible if the user who created the listing has specified their postcode in their user profile. To view the map, simply click the "See Details" button associated with the listing.</p>
    <img src="img/survey_images/map_function.png" alt="Map Functionality Example" class="img-fluid" />
</div>


                <div class="question">
                    <label for="map_usefulness">How useful do you find the integrated map on the listings page, which shows the location of items available for exchange or request within a 3-mile radius of your location? (1 = Not at all useful, 5 = Very useful)</label>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Not at all useful</span>
                        <input type="range" class="form-range" name="map_usefulness" min="1" max="5" step="1" required>
                        <span>Very useful</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>1</span>
                        <span>2</span>
                        <span>3</span>
                        <span>4</span>
                        <span>5</span>
                    </div>
                </div>


                <br><div class="instructions">
                    <p><strong>Messaging System:</strong> You can access the messaging system by clicking on the envelope icon in the navbar or by selecting "My Messages" from the dropdown menu. On the "My Messages" page, conversations are sorted with the newest messages at the top. A red dot with a number on the envelope icon indicates how many new conversations you have.</p>
                    <p>Clicking "View Conversation" on the "My Messages" page opens a popup displaying the full conversation between the two users. For the user who owns the listing, buttons like "Share My Address," "Mark as Pending Collection," and "Mark as Available Again" are provided within the conversation.</p>
                    <img src="img/survey_images/messaging_feature.png" alt="Messagig Functionality Example" class="img-fluid" />

                </div>

                <div class="question">
                    <label for="messaging_usefulness">The messaging system made it easy to communicate with other users. (1 = Not at all useful, 5 = Very useful)</label>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Not at all useful</span>
                        <input type="range" class="form-range" name="messaging_usefulness" min="1" max="5" step="1" required>
                        <span>Very useful</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>1</span>
                        <span>2</span>
                        <span>3</span>
                        <span>4</span>
                        <span>5</span>
                    </div>
                </div>

                <div class="question">
                    <label for="gps_button_usefulness">How useful do you find the button in the messaging system that allows you to share your GPS location with the requester? (1 = Not at all useful, 5 = Very useful)</label>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Not at all useful</span>
                        <input type="range" class="form-range" name="gps_button_usefulness" min="1" max="5" step="1" required>
                        <span>Very useful</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>1</span>
                        <span>2</span>
                        <span>3</span>
                        <span>4</span>
                        <span>5</span>
                    </div>
                </div>

                <br><div class="instructions">
                    <p><strong>Points-Based Reward System:</strong> Users earn Green Points for each "Wanted" or "Offered" listing they create (1 point per listing). In the future, these points can be redeemed for vouchers or gift cards. Your current Green Point balance is displayed in the navbar next to a green leaf icon.</p>
                    <img src="img/survey_images/green_points_function.png" alt="Green Poits Functionality Example" class="img-fluid" />
                </div>

                <div class="question">
                    <label for="points_system">The points system motivates me to use the website more actively. (1-5 scale)</label>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Not at all useful</span>
                        <input type="range" class="form-range" name="points_system" min="1" max="5" step="1" required>
                        <span>Very useful</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>1</span>
                        <span>2</span>
                        <span>3</span>
                        <span>4</span>
                        <span>5</span>
                    </div>
                </div>

                <button class="btn btn-secondary mt-3" id="section5Prev">Previous</button>
                <button class="btn btn-primary mt-3" id="section5Next">Next</button>
            </div>

            <!-- Section 6: Effectiveness in Encouraging Sustainable Behaviors -->
            <div id="section6" class="section hidden">
                <h2 class="section-title">Effectiveness in Encouraging Sustainable Behaviors</h2>

                <div class="question">
                    <label for="reuse_recycle">Do you believe that using ShareNest would make you more likely to reuse or recycle items instead of throwing them away?</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="reuse_recycle" id="reuse_yes" value="Yes" required>
                        <label class="form-check-label" for="reuse_yes">Yes</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="reuse_recycle" id="reuse_no" value="No">
                        <label class="form-check-label" for="reuse_no">No</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="reuse_recycle" id="reuse_unsure" value="Unsure">
                        <label class="form-check-label" for="reuse_unsure">Unsure</label>
                    </div>
                </div>

                <div class="question">
                    <label for="encourage_others">Do you think ShareNest could effectively encourage other people to adopt more sustainable waste management practices?</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="encourage_others" id="encourage_yes" value="Yes" required>
                        <label class="form-check-label" for="encourage_yes">Yes</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="encourage_others" id="encourage_no" value="No">
                        <label class="form-check-label" for="encourage_no">No</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="encourage_others" id="encourage_unsure" value="Unsure">
                        <label class="form-check-label" for="encourage_unsure">Unsure</label>
                    </div>
                </div>

                <div class="question">
                    <label for="effective_features">Which features of ShareNest do you think are most effective in promoting sustainable behaviors?</label>
                    <textarea class="form-control" name="effective_features" id="effective_features" rows="3"></textarea>
                </div>

                <div class="question">
                    <label for="location_map">Do you think the ability to see the location of items on a map would make you more likely to request or offer items on ShareNest?</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="location_map" id="map_yes" value="Yes" required>
                        <label class="form-check-label" for="map_yes">Yes</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="location_map" id="map_no" value="No">
                        <label class="form-check-label" for="map_no">No</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="location_map" id="map_maybe" value="Maybe">
                        <label class="form-check-label" for="map_maybe">Maybe</label>
                    </div>
                </div>

                <div class="question">
                    <label for="points_system_motivation">Does the points-based reward system motivate you to engage more with the platform?</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="points_system_motivation" id="points_yes" value="Yes" required>
                        <label class="form-check-label" for="points_yes">Yes</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="points_system_motivation" id="points_no" value="No">
                        <label class="form-check-label" for="points_no">No</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="points_system_motivation" id="points_maybe" value="Maybe">
                        <label class="form-check-label" for="points_maybe">Maybe</label>
                    </div>
                </div>

                <div class="question">
                    <label for="co2_motivation">Does seeing the total amount of CO2 emissions saved by the ShareNest community motivate you to use the platform more actively (e.g., list or request more items)? (1 = Not at all motivating, 5 = Very motivating)</label>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Not at all motivating</span>
                        <input type="range" class="form-range" name="co2_motivation" min="1" max="5" step="1" required>
                        <span>Very motivating</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>1</span>
                        <span>2</span>
                        <span>3</span>
                        <span>4</span>
                        <span>5</span>
                    </div>
                </div>

                <div class="question">
                    <label for="suggestions">Are there any additional features or improvements you would suggest to make ShareNest even more effective in promoting sustainability?</label>
                    <textarea class="form-control" name="suggestions" id="suggestions" rows="3"></textarea>
                </div>

                <button class="btn btn-secondary mt-3" id="section6Prev">Previous</button>
                <button class="btn btn-primary mt-3" id="section6Next">Finish</button>
            </div>

            

        </div>
    </form>
</div>
<!-- Privacy Questionnaire Content ENDS here -->

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Variables to track current section
        let currentSection = 1;

        // Section navigation functions
        function showSection(sectionNumber) {
            document.querySelectorAll('.section').forEach(section => section.classList.add('hidden'));
            document.getElementById(`section${sectionNumber}`).classList.remove('hidden');
            currentSection = sectionNumber;
        }

        // Consent section next button
        document.getElementById('consentNext').addEventListener('click', function() {
            const consentValue = document.querySelector('input[name="consent"]:checked').value;
            if (consentValue === 'Yes') {
                showSection(2);
            } else {
                window.location.href = 'no_consent.php';
            }
        });

        // Section navigation buttons
        document.getElementById('section2Prev').addEventListener('click', () => showSection(1));
        document.getElementById('section2Next').addEventListener('click', () => showSection(3));

        document.getElementById('section3Prev').addEventListener('click', () => showSection(2));
        document.getElementById('section3Next').addEventListener('click', () => showSection(4));

        document.getElementById('section4Prev').addEventListener('click', () => showSection(3));
        document.getElementById('section4Next').addEventListener('click', () => showSection(5));

        document.getElementById('section5Prev').addEventListener('click', () => showSection(4));
        document.getElementById('section5Next').addEventListener('click', () => showSection(6));

        document.getElementById('section6Prev').addEventListener('click', () => showSection(5));
        
    });
</script>
</body>
</html>
