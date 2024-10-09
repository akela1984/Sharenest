<?php
include 'session_timeout.php'; // Ensure session_start() is called here

error_reporting(E_ALL);

// Redirect non-admin users to the homepage
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'] || $_SESSION['is_admin'] !== 'true') {
    header('Location: index.php');
    exit;
}

include 'connection.php'; // Include the connection to your database

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Load PHPMailer at the top of the file
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to fetch data for tables with pagination and sorting
function fetch_data($conn, $table, $page, $perPage, $sortColumn, $sortOrder) {
    $offset = ($page - 1) * $perPage;
    if ($table === 'listings') {
        $sql = "SELECT listings.*, locations.location_name, users.username 
                FROM listings 
                JOIN locations ON listings.location_id = locations.location_id 
                JOIN users ON listings.user_id = users.id 
                ORDER BY $sortColumn $sortOrder 
                LIMIT $offset, $perPage";
    } else {
        $sql = "SELECT * FROM $table ORDER BY $sortColumn $sortOrder LIMIT $offset, $perPage";
    }
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function fetch_total_count($conn, $table) {
    $sql = "SELECT COUNT(*) as count FROM $table";
    return $conn->query($sql)->fetch_assoc()['count'];
}

function fetch_under_review_count($conn) {
    $sql = "SELECT COUNT(*) as count FROM listings WHERE state = 'under_review'";
    return $conn->query($sql)->fetch_assoc()['count'];
}

// Fetch statistics
$total_users = fetch_total_count($conn, 'users');
$total_listings = fetch_total_count($conn, 'listings');
$total_conversations = fetch_total_count($conn, 'conversations');
$total_messages = fetch_total_count($conn, 'messages');

// Fetch listings by state
$sql = "SELECT state, COUNT(*) as count FROM listings GROUP BY state";
$listings_by_state = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Fetch listings by type
$sql = "SELECT listing_type, COUNT(*) as count FROM listings GROUP BY listing_type";
$listings_by_type = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Fetch messages by read status
$sql = "SELECT `read`, COUNT(*) as count FROM messages GROUP BY `read`";
$messages_by_status = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Function to delete images from the filesystem
function delete_listing_images($conn, $user_id) {
    // Fetch all listing images URLs for the user
    $sql = "SELECT listing_images.image_url 
            FROM listing_images 
            JOIN listings ON listing_images.listing_id = listings.id 
            WHERE listings.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Delete each image from the filesystem
    while ($row = $result->fetch_assoc()) {
        $image_path = $row['image_url'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
}

// Function to send email
function send_email($to, $subject, $template, $placeholders = []) {
    global $config;

    $smtpUsername = $config['smtp']['username'];
    $smtpPassword = $config['smtp']['password'];

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.livemail.co.uk';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUsername;
        $mail->Password   = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('no-reply@sharenest.org', 'Sharenest');
        $mail->addAddress($to);

        // Load HTML template
        $templatePath = __DIR__ . "/templates/{$template}.html";
        if (!file_exists($templatePath)) {
            throw new Exception("Email template not found at $templatePath");
        }
        $templateContent = file_get_contents($templatePath);

        // Replace placeholders
        foreach ($placeholders as $key => $value) {
            $templateContent = str_replace("{{{$key}}}", htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $templateContent);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $templateContent;

        // Embed the image
        $logoPath = __DIR__ . '/img/sharenest_logo.png';
        if (!file_exists($logoPath)) {
            throw new Exception("Logo not found at $logoPath");
        }
        $mail->addEmbeddedImage($logoPath, 'sharenest_logo');

        $mail->send();
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: " . htmlspecialchars($mail->ErrorInfo));
        error_log("Exception: " . htmlspecialchars($e->getMessage()));
    }
}

// Pagination and sorting for Users table
$users_page = isset($_GET['users_page']) ? (int)$_GET['users_page'] : 1;
$users_perPage = 10;
$users_sort_column = isset($_GET['users_sort_column']) ? htmlspecialchars($_GET['users_sort_column']) : 'id';
$users_sort_order = isset($_GET['users_sort_order']) ? htmlspecialchars($_GET['users_sort_order']) : 'ASC';

$users = fetch_data($conn, 'users', $users_page, $users_perPage, $users_sort_column, $users_sort_order);
$total_users = fetch_total_count($conn, 'users');
$total_users_pages = ceil($total_users / $users_perPage);

// Pagination and sorting for Listings table
$listings_page = isset($_GET['listings_page']) ? (int)$_GET['listings_page'] : 1;
$listings_perPage = 10;
$listings_sort_column = isset($_GET['listings_sort_column']) ? htmlspecialchars($_GET['listings_sort_column']) : 'id';
$listings_sort_order = isset($_GET['listings_sort_order']) ? htmlspecialchars($_GET['listings_sort_order']) : 'ASC';

$listings = fetch_data($conn, 'listings', $listings_page, $listings_perPage, $listings_sort_column, $listings_sort_order);
$total_listings = fetch_total_count($conn, 'listings');
$total_listings_pages = ceil($total_listings / $listings_perPage);

// Count listings with 'under_review' state
$under_review_count = fetch_under_review_count($conn);

// Function to update user data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    // CSRF token validation
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $status = $_POST['status'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $green_points = $_POST['green_points'];
    $is_admin = $_POST['is_admin'];

    $sql = "UPDATE users SET username=?, email=?, status=?, firstname=?, lastname=?, green_points=?, is_admin=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssi", $username, $email, $status, $firstname, $lastname, $green_points, $is_admin, $user_id);
    $stmt->execute();
    header('Location: admin_panel.php?tab=users&message=User updated successfully');
    exit;
}

// Function to update listing data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_listing'])) {
    // CSRF token validation
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $listing_id = $_POST['listing_id'];
    $title = $_POST['title'];
    $listing_description = $_POST['listing_description'];
    $state = $_POST['state'];
    $listing_type = $_POST['listing_type'];

    $sql = "UPDATE listings SET title=?, listing_description=?, state=?, listing_type=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $title, $listing_description, $state, $listing_type, $listing_id);
    
    if ($stmt->execute()) {
        header('Location: admin_panel.php?tab=listings&message=Listing updated successfully');
        exit;
    } else {
        error_log('Failed to update listing: ' . $stmt->error);
        echo 'Error updating listing.';
    }
}

// Function to delete user data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    // CSRF token validation
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $user_id = $_POST['user_id'];
    $reason = $_POST['reason'];

    // Get user email and username before deletion
    $sql = "SELECT email, username FROM users WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Delete images associated with the user's listings
    delete_listing_images($conn, $user_id);

    // Delete user and cascading deletes will handle the rest
    $sql = "DELETE FROM users WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Send email notification
    $template = $reason === 'User Requested' ? 'user_deleted_user_requested' : 'user_deleted_admin_decision';
    send_email($user['email'], 'Account Deletion Notification', $template, ['username' => $user['username']]);

    header('Location: admin_panel.php?tab=users&message=User deleted successfully');
    exit;
}

// Function to create a new user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_username'])) {
    // CSRF token validation
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $new_username = htmlspecialchars(trim($_POST['new_username']), ENT_QUOTES, 'UTF-8');
    $new_email = htmlspecialchars(trim($_POST['new_email']), ENT_QUOTES, 'UTF-8');
    $new_password = $_POST['new_password'];
    $confirmPassword = $_POST['confirmPassword'];
    $new_firstname = htmlspecialchars(trim($_POST['new_firstname']), ENT_QUOTES, 'UTF-8');
    $new_lastname = htmlspecialchars(trim($_POST['new_lastname']), ENT_QUOTES, 'UTF-8');
    $new_status = $_POST['new_status'];
    $new_is_admin = $_POST['new_is_admin'];
    $token = bin2hex(random_bytes(16));

    // Validate input
    $error = '';
    if (empty($new_username) || empty($new_email) || empty($new_password) || empty($confirmPassword)) {
        $error = "All fields are required!";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } elseif ($new_password !== $confirmPassword) {
        $error = "Passwords do not match!";
    } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/', $new_password)) {
        $error = "Password must be at least 8 characters long, include at least one letter, one number, and one special character from @$!%*#?&.";
    } else {
        // Check if username or email already exists
        $sql = "SELECT * FROM users WHERE email = ? OR username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $new_email, $new_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Username or email already taken!";
        } else {
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

            // Insert new user into the database with a token and status as inactive
            $sql = "INSERT INTO users (username, email, password, firstname, lastname, status, is_admin, token) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssss", $new_username, $new_email, $hashedPassword, $new_firstname, $new_lastname, $new_status, $new_is_admin, $token);

            if ($stmt->execute()) {
                // If the new user is inactive, send a verification email
                if ($new_status === 'inactive') {
                    send_email($new_email, 'Email Verification - Sharenest', 'register_email_template', [
                        'username' => $new_username,
                        'verification_link' => "http://sharenest.org/verify.php?token=" . urlencode($token)
                    ]);
                }
                header('Location: admin_panel.php?tab=create_user&message=User created successfully');
                exit;
            } else {
                $error = 'Failed to create user: ' . $stmt->error;
            }
        }
    }
}
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

    <!-- Hotjar Tracking Code for Sharenest.org -->
<script>
    (function(h,o,t,j,a,r){
        h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
        h._hjSettings={hjid:5057424,hjsv:6};
        a=o.getElementsByTagName('head')[0];
        r=o.createElement('script');r.async=1;
        r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
        a.appendChild(r);
    })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
</script>

    <!--  Google Ad blocking recovery script -->

<script async src="https://fundingchoicesmessages.google.com/i/pub-7451119341261729?ers=1" nonce="rOmr667MK6arcexjSTnhMg"></script><script nonce="rOmr667MK6arcexjSTnhMg">(function() {function signalGooglefcPresent() {if (!window.frames['googlefcPresent']) {if (document.body) {const iframe = document.createElement('iframe'); iframe.style = 'width: 0; height: 0; border: none; z-index: -1000; left: -1000px; top: -1000px;'; iframe.style.display = 'none'; iframe.name = 'googlefcPresent'; document.body.appendChild(iframe);} else {setTimeout(signalGooglefcPresent, 0);}}}signalGooglefcPresent();})();</script>

<!--  Google Ad blocking recovery Error protection message script -->

<script>(function(){'use strict';function aa(a){var b=0;return function(){return b<a.length?{done:!1,value:a[b++]}:{done:!0}}}var ba="function"==typeof Object.defineProperties?Object.defineProperty:function(a,b,c){if(a==Array.prototype||a==Object.prototype)return a;a[b]=c.value;return a};
     function ca(a){a=["object"==typeof globalThis&&globalThis,a,"object"==typeof window&&window,"object"==typeof self&&self,"object"==typeof global&&global];for(var b=0;b<a.length;++b){var c=a[b];if(c&&c.Math==Math)return c}throw Error("Cannot find global object");}var da=ca(this);function k(a,b){if(b)a:{var c=da;a=a.split(".");for(var d=0;d<a.length-1;d++){var e=a[d];if(!(e in c))break a;c=c[e]}a=a[a.length-1];d=c[a];b=b(d);b!=d&&null!=b&&ba(c,a,{configurable:!0,writable:!0,value:b})}}
     function ea(a){return a.raw=a}function m(a){var b="undefined"!=typeof Symbol&&Symbol.iterator&&a[Symbol.iterator];if(b)return b.call(a);if("number"==typeof a.length)return{next:aa(a)};throw Error(String(a)+" is not an iterable or ArrayLike");}function fa(a){for(var b,c=[];!(b=a.next()).done;)c.push(b.value);return c}var ha="function"==typeof Object.create?Object.create:function(a){function b(){}b.prototype=a;return new b},n;
     if("function"==typeof Object.setPrototypeOf)n=Object.setPrototypeOf;else{var q;a:{var ia={a:!0},ja={};try{ja.__proto__=ia;q=ja.a;break a}catch(a){}q=!1}n=q?function(a,b){a.__proto__=b;if(a.__proto__!==b)throw new TypeError(a+" is not extensible");return a}:null}var ka=n;
     function r(a,b){a.prototype=ha(b.prototype);a.prototype.constructor=a;if(ka)ka(a,b);else for(var c in b)if("prototype"!=c)if(Object.defineProperties){var d=Object.getOwnPropertyDescriptor(b,c);d&&Object.defineProperty(a,c,d)}else a[c]=b[c];a.A=b.prototype}function la(){for(var a=Number(this),b=[],c=a;c<arguments.length;c++)b[c-a]=arguments[c];return b}k("Number.MAX_SAFE_INTEGER",function(){return 9007199254740991});
     k("Number.isFinite",function(a){return a?a:function(b){return"number"!==typeof b?!1:!isNaN(b)&&Infinity!==b&&-Infinity!==b}});k("Number.isInteger",function(a){return a?a:function(b){return Number.isFinite(b)?b===Math.floor(b):!1}});k("Number.isSafeInteger",function(a){return a?a:function(b){return Number.isInteger(b)&&Math.abs(b)<=Number.MAX_SAFE_INTEGER}});
     k("Math.trunc",function(a){return a?a:function(b){b=Number(b);if(isNaN(b)||Infinity===b||-Infinity===b||0===b)return b;var c=Math.floor(Math.abs(b));return 0>b?-c:c}});k("Object.is",function(a){return a?a:function(b,c){return b===c?0!==b||1/b===1/c:b!==b&&c!==c}});k("Array.prototype.includes",function(a){return a?a:function(b,c){var d=this;d instanceof String&&(d=String(d));var e=d.length;c=c||0;for(0>c&&(c=Math.max(c+e,0));c<e;c++){var f=d[c];if(f===b||Object.is(f,b))return!0}return!1}});
     k("String.prototype.includes",function(a){return a?a:function(b,c){if(null==this)throw new TypeError("The 'this' value for String.prototype.includes must not be null or undefined");if(b instanceof RegExp)throw new TypeError("First argument to String.prototype.includes must not be a regular expression");return-1!==this.indexOf(b,c||0)}});/*
     
      Copyright The Closure Library Authors.
      SPDX-License-Identifier: Apache-2.0
     */
     var t=this||self;function v(a){return a};var w,x;a:{for(var ma=["CLOSURE_FLAGS"],y=t,z=0;z<ma.length;z++)if(y=y[ma[z]],null==y){x=null;break a}x=y}var na=x&&x[610401301];w=null!=na?na:!1;var A,oa=t.navigator;A=oa?oa.userAgentData||null:null;function B(a){return w?A?A.brands.some(function(b){return(b=b.brand)&&-1!=b.indexOf(a)}):!1:!1}function C(a){var b;a:{if(b=t.navigator)if(b=b.userAgent)break a;b=""}return-1!=b.indexOf(a)};function D(){return w?!!A&&0<A.brands.length:!1}function E(){return D()?B("Chromium"):(C("Chrome")||C("CriOS"))&&!(D()?0:C("Edge"))||C("Silk")};var pa=D()?!1:C("Trident")||C("MSIE");!C("Android")||E();E();C("Safari")&&(E()||(D()?0:C("Coast"))||(D()?0:C("Opera"))||(D()?0:C("Edge"))||(D()?B("Microsoft Edge"):C("Edg/"))||D()&&B("Opera"));var qa={},F=null;var ra="undefined"!==typeof Uint8Array,sa=!pa&&"function"===typeof btoa;function G(){return"function"===typeof BigInt};var H=0,I=0;function ta(a){var b=0>a;a=Math.abs(a);var c=a>>>0;a=Math.floor((a-c)/4294967296);b&&(c=m(ua(c,a)),b=c.next().value,a=c.next().value,c=b);H=c>>>0;I=a>>>0}function va(a,b){b>>>=0;a>>>=0;if(2097151>=b)var c=""+(4294967296*b+a);else G()?c=""+(BigInt(b)<<BigInt(32)|BigInt(a)):(c=(a>>>24|b<<8)&16777215,b=b>>16&65535,a=(a&16777215)+6777216*c+6710656*b,c+=8147497*b,b*=2,1E7<=a&&(c+=Math.floor(a/1E7),a%=1E7),1E7<=c&&(b+=Math.floor(c/1E7),c%=1E7),c=b+wa(c)+wa(a));return c}
     function wa(a){a=String(a);return"0000000".slice(a.length)+a}function ua(a,b){b=~b;a?a=~a+1:b+=1;return[a,b]};var J;J="function"===typeof Symbol&&"symbol"===typeof Symbol()?Symbol():void 0;var xa=J?function(a,b){a[J]|=b}:function(a,b){void 0!==a.g?a.g|=b:Object.defineProperties(a,{g:{value:b,configurable:!0,writable:!0,enumerable:!1}})},K=J?function(a){return a[J]|0}:function(a){return a.g|0},L=J?function(a){return a[J]}:function(a){return a.g},M=J?function(a,b){a[J]=b;return a}:function(a,b){void 0!==a.g?a.g=b:Object.defineProperties(a,{g:{value:b,configurable:!0,writable:!0,enumerable:!1}});return a};function ya(a,b){M(b,(a|0)&-14591)}function za(a,b){M(b,(a|34)&-14557)}
     function Aa(a){a=a>>14&1023;return 0===a?536870912:a};var N={},Ba={};function Ca(a){return!(!a||"object"!==typeof a||a.g!==Ba)}function Da(a){return null!==a&&"object"===typeof a&&!Array.isArray(a)&&a.constructor===Object}function P(a,b,c){if(!Array.isArray(a)||a.length)return!1;var d=K(a);if(d&1)return!0;if(!(b&&(Array.isArray(b)?b.includes(c):b.has(c))))return!1;M(a,d|1);return!0}Object.freeze(new function(){});Object.freeze(new function(){});var Ea=/^-?([1-9][0-9]*|0)(\.[0-9]+)?$/;var Q;function Fa(a,b){Q=b;a=new a(b);Q=void 0;return a}
     function R(a,b,c){null==a&&(a=Q);Q=void 0;if(null==a){var d=96;c?(a=[c],d|=512):a=[];b&&(d=d&-16760833|(b&1023)<<14)}else{if(!Array.isArray(a))throw Error();d=K(a);if(d&64)return a;d|=64;if(c&&(d|=512,c!==a[0]))throw Error();a:{c=a;var e=c.length;if(e){var f=e-1;if(Da(c[f])){d|=256;b=f-(+!!(d&512)-1);if(1024<=b)throw Error();d=d&-16760833|(b&1023)<<14;break a}}if(b){b=Math.max(b,e-(+!!(d&512)-1));if(1024<b)throw Error();d=d&-16760833|(b&1023)<<14}}}M(a,d);return a};function Ga(a){switch(typeof a){case "number":return isFinite(a)?a:String(a);case "boolean":return a?1:0;case "object":if(a)if(Array.isArray(a)){if(P(a,void 0,0))return}else if(ra&&null!=a&&a instanceof Uint8Array){if(sa){for(var b="",c=0,d=a.length-10240;c<d;)b+=String.fromCharCode.apply(null,a.subarray(c,c+=10240));b+=String.fromCharCode.apply(null,c?a.subarray(c):a);a=btoa(b)}else{void 0===b&&(b=0);if(!F){F={};c="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789".split("");d=["+/=",
     "+/","-_=","-_.","-_"];for(var e=0;5>e;e++){var f=c.concat(d[e].split(""));qa[e]=f;for(var g=0;g<f.length;g++){var h=f[g];void 0===F[h]&&(F[h]=g)}}}b=qa[b];c=Array(Math.floor(a.length/3));d=b[64]||"";for(e=f=0;f<a.length-2;f+=3){var l=a[f],p=a[f+1];h=a[f+2];g=b[l>>2];l=b[(l&3)<<4|p>>4];p=b[(p&15)<<2|h>>6];h=b[h&63];c[e++]=g+l+p+h}g=0;h=d;switch(a.length-f){case 2:g=a[f+1],h=b[(g&15)<<2]||d;case 1:a=a[f],c[e]=b[a>>2]+b[(a&3)<<4|g>>4]+h+d}a=c.join("")}return a}}return a};function Ha(a,b,c){a=Array.prototype.slice.call(a);var d=a.length,e=b&256?a[d-1]:void 0;d+=e?-1:0;for(b=b&512?1:0;b<d;b++)a[b]=c(a[b]);if(e){b=a[b]={};for(var f in e)Object.prototype.hasOwnProperty.call(e,f)&&(b[f]=c(e[f]))}return a}function Ia(a,b,c,d,e){if(null!=a){if(Array.isArray(a))a=P(a,void 0,0)?void 0:e&&K(a)&2?a:Ja(a,b,c,void 0!==d,e);else if(Da(a)){var f={},g;for(g in a)Object.prototype.hasOwnProperty.call(a,g)&&(f[g]=Ia(a[g],b,c,d,e));a=f}else a=b(a,d);return a}}
     function Ja(a,b,c,d,e){var f=d||c?K(a):0;d=d?!!(f&32):void 0;a=Array.prototype.slice.call(a);for(var g=0;g<a.length;g++)a[g]=Ia(a[g],b,c,d,e);c&&c(f,a);return a}function Ka(a){return a.s===N?a.toJSON():Ga(a)};function La(a,b,c){c=void 0===c?za:c;if(null!=a){if(ra&&a instanceof Uint8Array)return b?a:new Uint8Array(a);if(Array.isArray(a)){var d=K(a);if(d&2)return a;b&&(b=0===d||!!(d&32)&&!(d&64||!(d&16)));return b?M(a,(d|34)&-12293):Ja(a,La,d&4?za:c,!0,!0)}a.s===N&&(c=a.h,d=L(c),a=d&2?a:Fa(a.constructor,Ma(c,d,!0)));return a}}function Ma(a,b,c){var d=c||b&2?za:ya,e=!!(b&32);a=Ha(a,b,function(f){return La(f,e,d)});xa(a,32|(c?2:0));return a};function Na(a,b){a=a.h;return Oa(a,L(a),b)}function Oa(a,b,c,d){if(-1===c)return null;if(c>=Aa(b)){if(b&256)return a[a.length-1][c]}else{var e=a.length;if(d&&b&256&&(d=a[e-1][c],null!=d))return d;b=c+(+!!(b&512)-1);if(b<e)return a[b]}}function Pa(a,b,c,d,e){var f=Aa(b);if(c>=f||e){var g=b;if(b&256)e=a[a.length-1];else{if(null==d)return;e=a[f+(+!!(b&512)-1)]={};g|=256}e[c]=d;c<f&&(a[c+(+!!(b&512)-1)]=void 0);g!==b&&M(a,g)}else a[c+(+!!(b&512)-1)]=d,b&256&&(a=a[a.length-1],c in a&&delete a[c])}
     function Qa(a,b){var c=Ra;var d=void 0===d?!1:d;var e=a.h;var f=L(e),g=Oa(e,f,b,d);if(null!=g&&"object"===typeof g&&g.s===N)c=g;else if(Array.isArray(g)){var h=K(g),l=h;0===l&&(l|=f&32);l|=f&2;l!==h&&M(g,l);c=new c(g)}else c=void 0;c!==g&&null!=c&&Pa(e,f,b,c,d);e=c;if(null==e)return e;a=a.h;f=L(a);f&2||(g=e,c=g.h,h=L(c),g=h&2?Fa(g.constructor,Ma(c,h,!1)):g,g!==e&&(e=g,Pa(a,f,b,e,d)));return e}function Sa(a,b){a=Na(a,b);return null==a||"string"===typeof a?a:void 0}
     function Ta(a,b){var c=void 0===c?0:c;a=Na(a,b);if(null!=a)if(b=typeof a,"number"===b?Number.isFinite(a):"string"!==b?0:Ea.test(a))if("number"===typeof a){if(a=Math.trunc(a),!Number.isSafeInteger(a)){ta(a);b=H;var d=I;if(a=d&2147483648)b=~b+1>>>0,d=~d>>>0,0==b&&(d=d+1>>>0);b=4294967296*d+(b>>>0);a=a?-b:b}}else if(b=Math.trunc(Number(a)),Number.isSafeInteger(b))a=String(b);else{if(b=a.indexOf("."),-1!==b&&(a=a.substring(0,b)),!("-"===a[0]?20>a.length||20===a.length&&-922337<Number(a.substring(0,7)):
     19>a.length||19===a.length&&922337>Number(a.substring(0,6)))){if(16>a.length)ta(Number(a));else if(G())a=BigInt(a),H=Number(a&BigInt(4294967295))>>>0,I=Number(a>>BigInt(32)&BigInt(4294967295));else{b=+("-"===a[0]);I=H=0;d=a.length;for(var e=b,f=(d-b)%6+b;f<=d;e=f,f+=6)e=Number(a.slice(e,f)),I*=1E6,H=1E6*H+e,4294967296<=H&&(I+=Math.trunc(H/4294967296),I>>>=0,H>>>=0);b&&(b=m(ua(H,I)),a=b.next().value,b=b.next().value,H=a,I=b)}a=H;b=I;b&2147483648?G()?a=""+(BigInt(b|0)<<BigInt(32)|BigInt(a>>>0)):(b=
     m(ua(a,b)),a=b.next().value,b=b.next().value,a="-"+va(a,b)):a=va(a,b)}}else a=void 0;return null!=a?a:c}function S(a,b){a=Sa(a,b);return null!=a?a:""};function T(a,b,c){this.h=R(a,b,c)}T.prototype.toJSON=function(){return Ua(this,Ja(this.h,Ka,void 0,void 0,!1),!0)};T.prototype.s=N;T.prototype.toString=function(){return Ua(this,this.h,!1).toString()};
     function Ua(a,b,c){var d=a.constructor.v,e=L(c?a.h:b);a=b.length;if(!a)return b;var f;if(Da(c=b[a-1])){a:{var g=c;var h={},l=!1,p;for(p in g)if(Object.prototype.hasOwnProperty.call(g,p)){var u=g[p];if(Array.isArray(u)){var jb=u;if(P(u,d,+p)||Ca(u)&&0===u.size)u=null;u!=jb&&(l=!0)}null!=u?h[p]=u:l=!0}if(l){for(var O in h){g=h;break a}g=null}}g!=c&&(f=!0);a--}for(p=+!!(e&512)-1;0<a;a--){O=a-1;c=b[O];O-=p;if(!(null==c||P(c,d,O)||Ca(c)&&0===c.size))break;var kb=!0}if(!f&&!kb)return b;b=Array.prototype.slice.call(b,
     0,a);g&&b.push(g);return b};function Va(a){return function(b){if(null==b||""==b)b=new a;else{b=JSON.parse(b);if(!Array.isArray(b))throw Error(void 0);xa(b,32);b=Fa(a,b)}return b}};function Wa(a){this.h=R(a)}r(Wa,T);var Xa=Va(Wa);var U;function V(a){this.g=a}V.prototype.toString=function(){return this.g+""};var Ya={};function Za(a){if(void 0===U){var b=null;var c=t.trustedTypes;if(c&&c.createPolicy){try{b=c.createPolicy("goog#html",{createHTML:v,createScript:v,createScriptURL:v})}catch(d){t.console&&t.console.error(d.message)}U=b}else U=b}a=(b=U)?b.createScriptURL(a):a;return new V(a,Ya)};function $a(){return Math.floor(2147483648*Math.random()).toString(36)+Math.abs(Math.floor(2147483648*Math.random())^Date.now()).toString(36)};function ab(a,b){b=String(b);"application/xhtml+xml"===a.contentType&&(b=b.toLowerCase());return a.createElement(b)}function bb(a){this.g=a||t.document||document};/*
     
      SPDX-License-Identifier: Apache-2.0
     */
     function cb(a,b){a.src=b instanceof V&&b.constructor===V?b.g:"type_error:TrustedResourceUrl";var c,d;(c=(b=null==(d=(c=(a.ownerDocument&&a.ownerDocument.defaultView||window).document).querySelector)?void 0:d.call(c,"script[nonce]"))?b.nonce||b.getAttribute("nonce")||"":"")&&a.setAttribute("nonce",c)};function db(a){a=void 0===a?document:a;return a.createElement("script")};function eb(a,b,c,d,e,f){try{var g=a.g,h=db(g);h.async=!0;cb(h,b);g.head.appendChild(h);h.addEventListener("load",function(){e();d&&g.head.removeChild(h)});h.addEventListener("error",function(){0<c?eb(a,b,c-1,d,e,f):(d&&g.head.removeChild(h),f())})}catch(l){f()}};var fb=t.atob("aHR0cHM6Ly93d3cuZ3N0YXRpYy5jb20vaW1hZ2VzL2ljb25zL21hdGVyaWFsL3N5c3RlbS8xeC93YXJuaW5nX2FtYmVyXzI0ZHAucG5n"),gb=t.atob("WW91IGFyZSBzZWVpbmcgdGhpcyBtZXNzYWdlIGJlY2F1c2UgYWQgb3Igc2NyaXB0IGJsb2NraW5nIHNvZnR3YXJlIGlzIGludGVyZmVyaW5nIHdpdGggdGhpcyBwYWdlLg=="),hb=t.atob("RGlzYWJsZSBhbnkgYWQgb3Igc2NyaXB0IGJsb2NraW5nIHNvZnR3YXJlLCB0aGVuIHJlbG9hZCB0aGlzIHBhZ2Uu");function ib(a,b,c){this.i=a;this.u=b;this.o=c;this.g=null;this.j=[];this.m=!1;this.l=new bb(this.i)}
     function lb(a){if(a.i.body&&!a.m){var b=function(){mb(a);t.setTimeout(function(){nb(a,3)},50)};eb(a.l,a.u,2,!0,function(){t[a.o]||b()},b);a.m=!0}}
     function mb(a){for(var b=W(1,5),c=0;c<b;c++){var d=X(a);a.i.body.appendChild(d);a.j.push(d)}b=X(a);b.style.bottom="0";b.style.left="0";b.style.position="fixed";b.style.width=W(100,110).toString()+"%";b.style.zIndex=W(2147483544,2147483644).toString();b.style.backgroundColor=ob(249,259,242,252,219,229);b.style.boxShadow="0 0 12px #888";b.style.color=ob(0,10,0,10,0,10);b.style.display="flex";b.style.justifyContent="center";b.style.fontFamily="Roboto, Arial";c=X(a);c.style.width=W(80,85).toString()+
     "%";c.style.maxWidth=W(750,775).toString()+"px";c.style.margin="24px";c.style.display="flex";c.style.alignItems="flex-start";c.style.justifyContent="center";d=ab(a.l.g,"IMG");d.className=$a();d.src=fb;d.alt="Warning icon";d.style.height="24px";d.style.width="24px";d.style.paddingRight="16px";var e=X(a),f=X(a);f.style.fontWeight="bold";f.textContent=gb;var g=X(a);g.textContent=hb;Y(a,e,f);Y(a,e,g);Y(a,c,d);Y(a,c,e);Y(a,b,c);a.g=b;a.i.body.appendChild(a.g);b=W(1,5);for(c=0;c<b;c++)d=X(a),a.i.body.appendChild(d),
     a.j.push(d)}function Y(a,b,c){for(var d=W(1,5),e=0;e<d;e++){var f=X(a);b.appendChild(f)}b.appendChild(c);c=W(1,5);for(d=0;d<c;d++)e=X(a),b.appendChild(e)}function W(a,b){return Math.floor(a+Math.random()*(b-a))}function ob(a,b,c,d,e,f){return"rgb("+W(Math.max(a,0),Math.min(b,255)).toString()+","+W(Math.max(c,0),Math.min(d,255)).toString()+","+W(Math.max(e,0),Math.min(f,255)).toString()+")"}function X(a){a=ab(a.l.g,"DIV");a.className=$a();return a}
     function nb(a,b){0>=b||null!=a.g&&0!==a.g.offsetHeight&&0!==a.g.offsetWidth||(pb(a),mb(a),t.setTimeout(function(){nb(a,b-1)},50))}function pb(a){for(var b=m(a.j),c=b.next();!c.done;c=b.next())(c=c.value)&&c.parentNode&&c.parentNode.removeChild(c);a.j=[];(b=a.g)&&b.parentNode&&b.parentNode.removeChild(b);a.g=null};function qb(a,b,c,d,e){function f(l){document.body?g(document.body):0<l?t.setTimeout(function(){f(l-1)},e):b()}function g(l){l.appendChild(h);t.setTimeout(function(){h?(0!==h.offsetHeight&&0!==h.offsetWidth?b():a(),h.parentNode&&h.parentNode.removeChild(h)):a()},d)}var h=rb(c);f(3)}function rb(a){var b=document.createElement("div");b.className=a;b.style.width="1px";b.style.height="1px";b.style.position="absolute";b.style.left="-10000px";b.style.top="-10000px";b.style.zIndex="-10000";return b};function Ra(a){this.h=R(a)}r(Ra,T);function sb(a){this.h=R(a)}r(sb,T);var tb=Va(sb);function ub(a){var b=la.apply(1,arguments);if(0===b.length)return Za(a[0]);for(var c=a[0],d=0;d<b.length;d++)c+=encodeURIComponent(b[d])+a[d+1];return Za(c)};function vb(a){if(!a)return null;a=Sa(a,4);var b;null===a||void 0===a?b=null:b=Za(a);return b};var wb=ea([""]),xb=ea([""]);function yb(a,b){this.m=a;this.o=new bb(a.document);this.g=b;this.j=S(this.g,1);this.u=vb(Qa(this.g,2))||ub(wb);this.i=!1;b=vb(Qa(this.g,13))||ub(xb);this.l=new ib(a.document,b,S(this.g,12))}yb.prototype.start=function(){zb(this)};
     function zb(a){Ab(a);eb(a.o,a.u,3,!1,function(){a:{var b=a.j;var c=t.btoa(b);if(c=t[c]){try{var d=Xa(t.atob(c))}catch(e){b=!1;break a}b=b===Sa(d,1)}else b=!1}b?Z(a,S(a.g,14)):(Z(a,S(a.g,8)),lb(a.l))},function(){qb(function(){Z(a,S(a.g,7));lb(a.l)},function(){return Z(a,S(a.g,6))},S(a.g,9),Ta(a.g,10),Ta(a.g,11))})}function Z(a,b){a.i||(a.i=!0,a=new a.m.XMLHttpRequest,a.open("GET",b,!0),a.send())}function Ab(a){var b=t.btoa(a.j);a.m[b]&&Z(a,S(a.g,5))};(function(a,b){t[a]=function(){var c=la.apply(0,arguments);t[a]=function(){};b.call.apply(b,[null].concat(c instanceof Array?c:fa(m(c))))}})("__h82AlnkH6D91__",function(a){"function"===typeof window.atob&&(new yb(window,tb(window.atob(a)))).start()});}).call(this);
     
     window.__h82AlnkH6D91__("WyJwdWItNzQ1MTExOTM0MTI2MTcyOSIsW251bGwsbnVsbCxudWxsLCJodHRwczovL2Z1bmRpbmdjaG9pY2VzbWVzc2FnZXMuZ29vZ2xlLmNvbS9iL3B1Yi03NDUxMTE5MzQxMjYxNzI5Il0sbnVsbCxudWxsLCJodHRwczovL2Z1bmRpbmdjaG9pY2VzbWVzc2FnZXMuZ29vZ2xlLmNvbS9lbC9BR1NLV3hVQWhuWll6bVlUUmxXNFJ5eC12UEVrc1pUdVZfWmVreklxeUI5SHVtUnNhRnpMLU9wa05RaXFXNjU4ZlZ4NFJlbUlMYlFDYUNYTHhVUmpzdWZ4UVJFdXRRXHUwMDNkXHUwMDNkP3RlXHUwMDNkVE9LRU5fRVhQT1NFRCIsImh0dHBzOi8vZnVuZGluZ2Nob2ljZXNtZXNzYWdlcy5nb29nbGUuY29tL2VsL0FHU0tXeFZTUE1pTUl3TFI5RDNEM044d0J2cENGS1pfVUp5UU1BSl84c1ZqVzV6b2VaUnNGQnJoaHNxUjVDVGVPQWhhQkpuanJuVHkxMGhXX3EydUZzWnp2UFE4ZlFcdTAwM2RcdTAwM2Q/YWJcdTAwM2QxXHUwMDI2c2JmXHUwMDNkMSIsImh0dHBzOi8vZnVuZGluZ2Nob2ljZXNtZXNzYWdlcy5nb29nbGUuY29tL2VsL0FHU0tXeFVTallVa1R0eUtqMUhOeFNvRWhnVF9LZE5CeWdqS1VNd3JtelBTOGxfd2FOX0dHWV9uckRicUg1bVYtRVhXR1IwYm9XSS1jWDdNbFI3dmxNSnBXb1pCQXdcdTAwM2RcdTAwM2Q/YWJcdTAwM2QyXHUwMDI2c2JmXHUwMDNkMSIsImh0dHBzOi8vZnVuZGluZ2Nob2ljZXNtZXNzYWdlcy5nb29nbGUuY29tL2VsL0FHU0tXeFVwLWpNdjU4d1V6ZkZzQnhtcTBOYmJ2QW5qQU5iYWcwcnp0UGlLUE84aUFzWGp5UWhxVTJXU3hJcldlRVZ5eUw1SGJlZWlxUURvQVE1ckpHaU9IMExJaFFcdTAwM2RcdTAwM2Q/c2JmXHUwMDNkMiIsImRpdi1ncHQtYWQiLDIwLDEwMCwiY0hWaUxUYzBOVEV4TVRrek5ERXlOakUzTWprXHUwMDNkIixbbnVsbCxudWxsLG51bGwsImh0dHBzOi8vd3d3LmdzdGF0aWMuY29tLzBlbW4vZi9wL3B1Yi03NDUxMTE5MzQxMjYxNzI5LmpzP3VzcXBcdTAwM2RDQkEiXSwiaHR0cHM6Ly9mdW5kaW5nY2hvaWNlc21lc3NhZ2VzLmdvb2dsZS5jb20vZWwvQUdTS1d4VklxZzlRNldSSTNMMGxZd2t1dEZYVXg1S1NiTGNZaG1hbmZKdktJYV83QTdJVFA0cGZJQ1FsQWtsVUY1Tl82NjRIZy0yRHV2aU83VzhsanRCSTNaaHhfQVx1MDAzZFx1MDAzZCJd");</script>


     <!-- SEO Meta Tags -->
     <meta name="description" content="Join ShareNest, the community platform for sharing and discovering unwanted goods for free across the UK. Connect with neighbours and give a second life to items you no longer need.">
<meta name="keywords" content="share, unwanted goods, free items, community sharing, UK, give away, second hand, recycle, reuse">
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
<meta property="og:title" content="ShareNest - Community for Sharing Unwanted Goods across the UK">
<meta property="og:description" content="Join ShareNest, the community platform for sharing and discovering unwanted goods for free across the UK. Connect with neighbours and give a second life to items you no longer need.">
<meta property="og:image" content="/icons/icon-512x512.png">
<meta property="og:url" content="https://www.sharenest.org">
<meta property="og:type" content="website">

<!-- Twitter Card Meta Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="ShareNest - Community for Sharing Unwanted Goods across the UK">
<meta name="twitter:description" content="Join ShareNest, the community platform for sharing and discovering unwanted goods for free across the UK. Connect with neighbours and give a second life to items you no longer need.">
<meta name="twitter:image" content="/icons/icon-512x512.png">

<!-- Link to External PWA Script -->
<script src="/js/pwa.js" defer></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link href="https://getbootstrap.com/docs/5.3/assets/css/docs.css" rel="stylesheet">
<link href="css/styles.css" rel="stylesheet">
    <style>
        .nav-link {
            color: green !important;
        }
        .table-responsive {
            max-width: 100%;
            overflow-x: auto;
        }
        .under-review {
            background-color: lightcoral !important;
        }
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        .stats-card {
            flex: 1 1 calc(25% - 20px);
            padding: 20px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            text-align: center;
        }
        .stats-card h3 {
            margin-bottom: 20px;
            color: #5cb85c;
        }
        .stats-card p {
            font-size: 1.5rem;
        }
    </style>
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<div id="content" class="container mt-5">
    <h2>Admin Panel</h2>
    
    <?php if ($under_review_count > 0): ?>
    <div class="alert alert-info" role="alert">
        Important, there are <?php echo $under_review_count; ?> listings reported which need to be reviewed.
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['message'])): ?>
    <div class="alert alert-success" role="alert">
        <?php echo htmlspecialchars($_GET['message']); ?>
    </div>
    <?php endif; ?>

    <!-- Tabs navigation -->
    <ul class="nav nav-tabs" id="adminTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'users') ? 'active' : ''; ?>" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="<?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'users') ? 'true' : 'false'; ?>">Users</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'listings') ? 'active' : ''; ?>" id="listings-tab" data-bs-toggle="tab" data-bs-target="#listings" type="button" role="tab" aria-controls="listings" aria-selected="<?php echo (isset($_GET['tab']) && $_GET['tab'] == 'listings') ? 'true' : 'false'; ?>">Listings</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'create_user') ? 'active' : ''; ?>" id="create_user-tab" data-bs-toggle="tab" data-bs-target="#create_user" type="button" role="tab" aria-controls="create_user" aria-selected="<?php echo (isset($_GET['tab']) && $_GET['tab'] == 'create_user') ? 'true' : 'false'; ?>">Create User</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'stats') ? 'active' : ''; ?>" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab" aria-controls="stats" aria-selected="<?php echo (isset($_GET['tab']) && $_GET['tab'] == 'stats') ? 'true' : 'false'; ?>">Stats</button>
        </li>
    </ul>

    <!-- Tabs content -->
    <div class="tab-content" id="adminTabContent">
        <!-- Users Tab -->
        <div class="tab-pane fade <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'users') ? 'show active' : ''; ?>" id="users" role="tabpanel" aria-labelledby="users-tab">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th><a href="?tab=users&users_sort_column=id&users_sort_order=<?php echo $users_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">ID</a></th>
                            <th><a href="?tab=users&users_sort_column=created_at&users_sort_order=<?php echo $users_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Created</a></th>
                            <th><a href="?tab=users&users_sort_column=username&users_sort_order=<?php echo $users_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Username</a></th>
                            <th><a href="?tab=users&users_sort_column=email&users_sort_order=<?php echo $users_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Email</a></th>
                            <th><a href="?tab=users&users_sort_column=status&users_sort_order=<?php echo $users_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Status</a></th>
                            <th><a href="?tab=users&users_sort_column=firstname&users_sort_order=<?php echo $users_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">First Name</a></th>
                            <th><a href="?tab=users&users_sort_column=lastname&users_sort_order=<?php echo $users_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Last Name</a></th>
                            <th><a href="?tab=users&users_sort_column=green_points&users_sort_order=<?php echo $users_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Green Points</a></th>
                            <th><a href="?tab=users&users_sort_column=is_admin&users_sort_order=<?php echo $users_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Admin</a></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user) { ?>
                        <tr>
                            <form method="post" action="admin_panel.php?tab=users" class="user-form">
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                <td><input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="form-control" disabled></td>
                                <td><input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-control" disabled></td>
                                <td>
                                    <select name="status" class="form-select" disabled>
                                        <option value="inactive" <?php echo $user['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                    </select>
                                </td>
                                <td><input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" class="form-control" disabled></td>
                                <td><input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" class="form-control" disabled></td>
                                <td><input type="number" name="green_points" value="<?php echo htmlspecialchars($user['green_points']); ?>" class="form-control" disabled></td>
                                <td>
                                    <select name="is_admin" class="form-select" disabled>
                                        <option value="false" <?php echo $user['is_admin'] == 'false' ? 'selected' : ''; ?>>False</option>
                                        <option value="true" <?php echo $user['is_admin'] == 'true' ? 'selected' : ''; ?>>True</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="delete_user" value="">
                                    <div class="d-flex">
                                        <button type="button" class="btn btn-warning edit-btn me-2">Edit</button>
                                        <button type="submit" name="update_user" class="btn btn-primary save-btn d-none me-2">Save</button>
                                        <button type="button" class="btn btn-danger delete-btn d-none me-2" data-bs-toggle="modal" data-bs-target="#deleteModal" data-user-id="<?php echo htmlspecialchars($user['id']); ?>">Delete</button>
                                        <span class="processing-text text-success d-none">Processing...</span>
                                        <button type="button" class="btn btn-secondary cancel-btn d-none">Cancel</button>
                                    </div>
                                </td>
                            </form>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <nav>
                <ul class="pagination">
                    <?php if ($users_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?tab=users&users_page=<?php echo htmlspecialchars($users_page - 1); ?>&users_sort_column=<?php echo htmlspecialchars($users_sort_column); ?>&users_sort_order=<?php echo htmlspecialchars($users_sort_order); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_users_pages; $i++) { ?>
                    <li class="page-item <?php if ($users_page == $i) echo 'active'; ?>"><a class="page-link" href="?tab=users&users_page=<?php echo htmlspecialchars($i); ?>&users_sort_column=<?php echo htmlspecialchars($users_sort_column); ?>&users_sort_order=<?php echo htmlspecialchars($users_sort_order); ?>"><?php echo htmlspecialchars($i); ?></a></li>
                    <?php } ?>
                    <?php if ($users_page < $total_users_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?tab=users&users_page=<?php echo htmlspecialchars($users_page + 1); ?>&users_sort_column=<?php echo htmlspecialchars($users_sort_column); ?>&users_sort_order=<?php echo htmlspecialchars($users_sort_order); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        
        <!-- Listings Tab -->
        <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'listings') ? 'show active' : ''; ?>" id="listings" role="tabpanel" aria-labelledby="listings-tab">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th><a href="?tab=listings&listings_sort_column=id&listings_sort_order=<?php echo $listings_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">ID</a></th>
                            <th><a href="?tab=listings&listings_sort_column=time_added&listings_sort_order=<?php echo $listings_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Time Added</a></th>
                            <th><a href="?tab=listings&listings_sort_column=title&listings_sort_order=<?php echo $listings_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Title</a></th>
                            <th><a href="?tab=listings&listings_sort_column=location_name&listings_sort_order=<?php echo $listings_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Location</a></th>
                            <th><a href="?tab=listings&listings_sort_column=listing_description&listings_sort_order=<?php echo $listings_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Description</a></th>
                            <th><a href="?tab=listings&listings_sort_column=username&listings_sort_order=<?php echo $listings_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">User</a></th>
                            <th><a href="?tab=listings&listings_sort_column=state&listings_sort_order=<?php echo $listings_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">State</a></th>
                            <th><a href="?tab=listings&listings_sort_column=listing_type&listings_sort_order=<?php echo $listings_sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>">Type</a></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listings as $listing) { 
                            // Convert state and listing_type to human-readable format
                            $state_readable = ucwords(str_replace('_', ' ', $listing['state']));
                            $type_readable = ucwords(str_replace('_', ' ', $listing['listing_type']));
                        ?>
                        <tr class="<?php echo $listing['state'] == 'under_review' ? 'under-review' : ''; ?>">
                            <form method="post" action="admin_panel.php?tab=listings" class="listing-form">
                                <td><?php echo htmlspecialchars($listing['id']); ?></td>
                                <td><?php echo htmlspecialchars($listing['time_added']); ?></td>
                                <td><input type="text" name="title" value="<?php echo htmlspecialchars($listing['title']); ?>" class="form-control" disabled></td>
                                <td><?php echo htmlspecialchars($listing['location_name']); ?></td>
                                <td><input type="text" name="listing_description" value="<?php echo htmlspecialchars($listing['listing_description']); ?>" class="form-control" disabled></td>
                                <td><?php echo htmlspecialchars($listing['username']); ?></td>
                                <td>
                                    <select name="state" class="form-select" disabled>
                                        <option value="available" <?php echo $listing['state'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="unavailable" <?php echo $listing['state'] == 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                                        <option value="pending_collection" <?php echo $listing['state'] == 'pending_collection' ? 'selected' : ''; ?>>Pending Collection</option>
                                        <option value="under_review" <?php echo $listing['state'] == 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="listing_type" class="form-select" disabled>
                                        <option value="sharing" <?php echo $listing['listing_type'] == 'sharing' ? 'selected' : ''; ?>>Sharing</option>
                                        <option value="wanted" <?php echo $listing['listing_type'] == 'wanted' ? 'selected' : ''; ?>>Wanted</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="listing_id" value="<?php echo htmlspecialchars($listing['id']); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="delete_listing" value="">
                                    <div class="d-flex">
                                        <button type="button" class="btn btn-warning edit-btn me-2">Edit</button>
                                        <button type="submit" name="update_listing" class="btn btn-primary save-btn d-none me-2">Save</button>
                                        <button type="button" class="btn btn-danger delete-btn d-none me-2" data-bs-toggle="modal" data-bs-target="#deleteModalListing" data-listing-id="<?php echo htmlspecialchars($listing['id']); ?>">Delete</button>
                                        <span class="processing-text text-success d-none">Processing...</span>
                                        <button type="button" class="btn btn-secondary cancel-btn d-none">Cancel</button>
                                    </div>
                                </td>
                            </form>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <nav>
                <ul class="pagination">
                    <?php if ($listings_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?tab=listings&listings_page=<?php echo htmlspecialchars($listings_page - 1); ?>&listings_sort_column=<?php echo htmlspecialchars($listings_sort_column); ?>&listings_sort_order=<?php echo htmlspecialchars($listings_sort_order); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_listings_pages; $i++) { ?>
                    <li class="page-item <?php if ($listings_page == $i) echo 'active'; ?>"><a class="page-link" href="?tab=listings&listings_page=<?php echo htmlspecialchars($i); ?>&listings_sort_column=<?php echo htmlspecialchars($listings_sort_column); ?>&listings_sort_order=<?php echo htmlspecialchars($listings_sort_order); ?>"><?php echo htmlspecialchars($i); ?></a></li>
                    <?php } ?>
                    <?php if ($listings_page < $total_listings_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?tab=listings&listings_page=<?php echo htmlspecialchars($listings_page + 1); ?>&listings_sort_column=<?php echo htmlspecialchars($listings_sort_column); ?>&listings_sort_order=<?php echo htmlspecialchars($listings_sort_order); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>

        <!-- Create User Tab -->
        <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'create_user') ? 'show active' : ''; ?>" id="create_user" role="tabpanel" aria-labelledby="create_user-tab">
            <div class="container mt-5">
                <h3>Create New User</h3>
                <?php if (isset($error) && !empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <form id="createUserForm" method="post" action="admin_panel.php?tab=create_user">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-3">
                        <label for="new_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="new_username" name="new_username" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="new_email" name="new_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <small id="passwordHelp" class="form-text text-muted">
                            Password must be at least 8 characters long, include at least one letter, one number, and one special character. 
                            Allowed special characters: @$!%*#?&.
                        </small>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_firstname" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="new_firstname" name="new_firstname" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_lastname" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="new_lastname" name="new_lastname" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_status" class="form-label">Status</label>
                        <select id="new_status" name="new_status" class="form-select" required>
                            <option value="inactive">Inactive</option>
                            <option value="active">Active</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="new_is_admin" class="form-label">Admin</label>
                        <select id="new_is_admin" name="new_is_admin" class="form-select" required>
                            <option value="false">False</option>
                            <option value="true">True</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline-success create-user-btn">Create User</button>
                    <span id="creatingUserText" class="text-success d-none">Creating user...</span>
                </form>
            </div>
        </div>

        <!-- Stats Tab -->
        <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'stats') ? 'show active' : ''; ?>" id="stats" role="tabpanel" aria-labelledby="stats-tab">
            <div class="stats-container">
                <div class="stats-card">
                    <h3>Total Users</h3>
                    <p><?php echo htmlspecialchars($total_users); ?></p>
                </div>
                <div class="stats-card">
                    <h3>Total Listings</h3>
                    <p><?php echo htmlspecialchars($total_listings); ?></p>
                </div>
                <div class="stats-card">
                    <h3>Total Conversations</h3>
                    <p><?php echo htmlspecialchars($total_conversations); ?></p>
                </div>
                <div class="stats-card">
                    <h3>Total Messages</h3>
                    <p><?php echo htmlspecialchars($total_messages); ?></p>
                </div>
                <div class="stats-card">
                    <h3>Listings by State</h3>
                    <?php foreach ($listings_by_state as $state) { ?>
                        <p><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $state['state']))) . ': ' . htmlspecialchars($state['count']); ?></p>
                    <?php } ?>
                </div>
                <div class="stats-card">
                    <h3>Listings by Type</h3>
                    <?php foreach ($listings_by_type as $type) { ?>
                        <p><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $type['listing_type']))) . ': ' . htmlspecialchars($type['count']); ?></p>
                    <?php } ?>
                </div>
                <div class="stats-card">
                    <h3>Messages by Status</h3>
                    <?php foreach ($messages_by_status as $status) { ?>
                        <p><?php echo $status['read'] ? 'Read: ' : 'Unread: '; ?><?php echo htmlspecialchars($status['count']); ?></p>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deleteForm" method="post" action="admin_panel.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this user?
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="delete_user" value="true">
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Deletion:</label>
                        <select name="reason" id="reason" class="form-select" required>
                            <option value="">Select Reason</option>
                            <option value="User Requested">User Requested</option>
                            <option value="Admin Decision">Admin Decision</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger delete-btn-modal">Delete</button>
                    <span class="processing-text-modal text-success d-none">Processing...</span>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Listing Modal -->
<div class="modal fade" id="deleteModalListing" tabindex="-1" aria-labelledby="deleteModalListingLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deleteFormListing" method="post" action="admin_panel.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalListingLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this listing?
                    <input type="hidden" name="listing_id" id="deleteListingId">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="delete_listing" value="true">
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Deletion:</label>
                        <select name="reason" id="reason" class="form-select" required>
                            <option value="">Select Reason</option>
                            <option value="User Requested">User Requested</option>
                            <option value="Admin Decision">Admin Decision</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger delete-btn-modal">Delete</button>
                    <span class="processing-text-modal text-success d-none">Processing...</span>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Footer STARTS here -->
<?php include 'footer.php'; ?>
<!-- Footer ENDS here -->

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get the current URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    // Get the active tab parameter
    const activeTab = urlParams.get('tab') || 'users';
    // Activate the correct tab
    const tabElement = document.querySelector(`#${activeTab}-tab`);
    const tabInstance = new bootstrap.Tab(tabElement);
    tabInstance.show();
    
    // Update the URL parameter when a tab is clicked
    document.querySelectorAll('.nav-link').forEach(function(tab) {
        tab.addEventListener('shown.bs.tab', function(event) {
            const newTab = event.target.id.split('-')[0];
            urlParams.set('tab', newTab);
            window.history.replaceState({}, '', `${window.location.pathname}?${urlParams.toString()}`);
        });
    });

    // Make fields editable and show buttons
    document.querySelectorAll('.edit-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            row.querySelectorAll('.form-control, .form-select').forEach(function(input) {
                input.disabled = false;
            });
            row.querySelector('.save-btn').classList.remove('d-none');
            row.querySelector('.delete-btn').classList.remove('d-none');
            row.querySelector('.cancel-btn').classList.remove('d-none');
            this.classList.add('d-none');
        });
    });

    // Confirm save action
    document.querySelectorAll('.save-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to save changes?')) {
                this.closest('form').submit();
            }
        });
    });

    // Confirm delete action
    document.querySelectorAll('.delete-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const listingId = this.getAttribute('data-listing-id');
            if (userId) {
                document.getElementById('deleteUserId').value = userId;
            }
            if (listingId) {
                document.getElementById('deleteListingId').value = listingId;
            }
        });
    });

    // Cancel edit action
    document.querySelectorAll('.cancel-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            row.querySelectorAll('.form-control, .form-select').forEach(function(input) {
                input.disabled = true;
            });
            row.querySelector('.save-btn').classList.add('d-none');
            row.querySelector('.delete-btn').classList.add('d-none');
            row.querySelector('.edit-btn').classList.remove('d-none');
            this.classList.add('d-none');
        });
    });

    // Handle form submission for delete actions
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            const deleteButton = form.querySelector('.delete-btn-modal');
            const processingText = form.querySelector('.processing-text-modal');

            if (deleteButton && processingText) {
                deleteButton.classList.add('d-none');
                processingText.classList.remove('d-none');
            }
        });
    });

    // Handle form submission for creating users
    const createUserForm = document.getElementById('createUserForm');
    createUserForm.addEventListener('submit', function(event) {
        const new_password = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const errorMessage = document.getElementById('errorMessage');
        const regex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/;

        errorMessage.style.display = 'none';

        if (!regex.test(new_password)) {
            errorMessage.textContent = 'Password must be at least 8 characters long, include at least one letter, one number, and one special character from @$!%*#?&.';
            errorMessage.style.display = 'block';
            event.preventDefault();
        } else if (new_password !== confirmPassword) {
            errorMessage.textContent = 'Passwords do not match!';
            errorMessage.style.display = 'block';
            event.preventDefault();
        } else {
            const createUserButton = createUserForm.querySelector('.create-user-btn');
            const creatingUserText = document.getElementById('creatingUserText');

            createUserButton.disabled = true;
            createUserButton.style.display = 'none';
            creatingUserText.style.display = 'inline';
        }
    });
});
</script>


<button id="install-button" style="display: none;">Install Sharenest</button>
</body>
</html>
