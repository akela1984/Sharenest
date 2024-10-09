<?php
include 'session_timeout.php';

// Redirect non-logged-in users to the sign-in page
if (!isset($_SESSION['loggedin'])) {
    header('Location: signin.php');
    exit;
}

include 'connection.php';

// Function to fetch user data
function fetch_user_data($conn, $username) {
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to fetch user address
function fetch_user_address($conn, $user_id) {
    $sql = "SELECT * FROM users_address WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to fetch user email preferences
function fetch_user_email_preferences($conn, $user_id) {
    $sql = "SELECT * FROM user_email_preferences WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to delete images from the filesystem
function delete_listing_images($conn, $user_id) {
    $sql = "SELECT listing_images.image_url FROM listing_images JOIN listings ON listing_images.listing_id = listings.id WHERE listings.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $image_path = 'uploads/listing_images/' . $row['image_url'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
}

$username = $_SESSION['username'];
$user = fetch_user_data($conn, $username);
$user_id = $user['id'];
$user_address = fetch_user_address($conn, $user_id);
$email_preferences = fetch_user_email_preferences($conn, $user_id);

if (!$user) {
    $error = "User not found!";
}

$uploadDir = 'uploads/user_profile_img/';
$profileUpdated = false;

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $newEmail = htmlspecialchars(trim($_POST['email']), ENT_QUOTES, 'UTF-8');
    $newFirstName = htmlspecialchars(trim($_POST['firstname']), ENT_QUOTES, 'UTF-8');
    $newLastName = htmlspecialchars(trim($_POST['lastname']), ENT_QUOTES, 'UTF-8');
    $currentPassword = trim($_POST['current_password']);
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);

    $address_line1 = htmlspecialchars(trim($_POST['address_line1']), ENT_QUOTES, 'UTF-8');
    $address_line2 = htmlspecialchars(trim($_POST['address_line2']), ENT_QUOTES, 'UTF-8');
    $town_city = htmlspecialchars(trim($_POST['town_city']), ENT_QUOTES, 'UTF-8');
    $postcode = htmlspecialchars(trim($_POST['postcode']), ENT_QUOTES, 'UTF-8');
    $country = htmlspecialchars(trim($_POST['country']), ENT_QUOTES, 'UTF-8');

    if (empty($newEmail)) {
        $error = "Email is required!";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } elseif (!empty($newPassword) && $newPassword !== $confirmPassword) {
        $error = "New passwords do not match!";
    } elseif (empty($currentPassword)) {
        $error = "Current password is required to save changes!";
    } elseif (!password_verify($currentPassword, $user['password'])) {
        $error = "Current password is incorrect!";
    } else {
        $profileImage = $user['profile_image']; // default to current image

        if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == UPLOAD_ERR_OK) {
            $imageTmpName = $_FILES['profileImage']['tmp_name'];
            $imageName = $_FILES['profileImage']['name'];
            $imageExtension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
            $allowedFormats = array('jpg', 'jpeg', 'png', 'gif');

            if (!in_array($imageExtension, $allowedFormats)) {
                $error = "Only JPG, JPEG, PNG, and GIF files are allowed!";
            } else {
                $newImageName = htmlspecialchars($username . '_' . date('YmdHis') . '.' . $imageExtension, ENT_QUOTES, 'UTF-8');
                $targetFilePath = $uploadDir . $newImageName;

                if (move_uploaded_file($imageTmpName, $targetFilePath)) {
                    if (!empty($profileImage) && file_exists($profileImage)) {
                        unlink($profileImage);
                    }
                    $profileImage = $targetFilePath;
                    $_SESSION['user_image'] = $profileImage; // Update session with new image
                } else {
                    $error = "Failed to upload profile image!";
                }
            }
        }

        $sql = "UPDATE users SET email = ?, firstname = ?, lastname = ?, profile_image = ? WHERE id = ?";
        $params = [$newEmail, $newFirstName, $newLastName, $profileImage, $user['id']];

        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $sql = "UPDATE users SET email = ?, firstname = ?, lastname = ?, password = ?, profile_image = ? WHERE id = ?";
            $params = [$newEmail, $newFirstName, $newLastName, $hashedPassword, $profileImage, $user['id']];
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('s', count($params) - 1) . 'i', ...$params);

        if ($stmt->execute()) {
            $_SESSION['username'] = $username;
            $_SESSION['user_image'] = $profileImage;
            $profileUpdated = true;

            if ($user_address) {
                $sql_address = "UPDATE users_address SET address_line1 = ?, address_line2 = ?, town_city = ?, postcode = ?, country = ? WHERE user_id = ?";
                $params_address = [$address_line1, $address_line2, $town_city, $postcode, $country, $user_id];
                $stmt_address = $conn->prepare($sql_address);
                $stmt_address->bind_param(str_repeat('s', count($params_address) - 1) . 'i', ...$params_address);
                $stmt_address->execute();
            } else {
                $sql_address = "INSERT INTO users_address (user_id, address_line1, address_line2, town_city, postcode, country) VALUES (?, ?, ?, ?, ?, ?)";
                $params_address = [$user_id, $address_line1, $address_line2, $town_city, $postcode, $country];
                $stmt_address = $conn->prepare($sql_address);
                $stmt_address->bind_param('isssss', ...$params_address);
                $stmt_address->execute();
            }
        } else {
            $error = "Profile update failed, please try again!";
        }

        $user = fetch_user_data($conn, $username);
        $user_address = fetch_user_address($conn, $user_id);
        if (!$user) {
            $error = "User not found!";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_email_preferences'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $receive_message_email = isset($_POST['receive_message_email']) ? 1 : 0;

    $sql = "UPDATE user_email_preferences SET receive_message_email = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $receive_message_email, $user_id);
    if (!$stmt->execute()) {
        $error = "Failed to update email preferences, please try again!";
    } else {
        $profileUpdated = true;
    }

    $email_preferences = fetch_user_email_preferences($conn, $user_id);
}

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_account'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $currentPassword = trim($_POST['current_password_delete']);

    if (empty($currentPassword)) {
        $error = "Current password is required to delete account!";
    } elseif (!password_verify($currentPassword, $user['password'])) {
        $error = "Current password is incorrect!";
    } else {
        $email = $user['email'];
        $username = $user['username'];
        delete_listing_images($conn, $user_id);

        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $sql_address = "DELETE FROM users_address WHERE user_id = ?";
        $stmt_address = $conn->prepare($sql_address);
        $stmt_address->bind_param("i", $user_id);
        $stmt_address->execute();

        $smtpUsername = $config['smtp']['username'];
        $smtpPassword = $config['smtp']['password'];

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.livemail.co.uk';
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUsername;
            $mail->Password   = $smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->setFrom('no-reply@sharenest.org', 'Sharenest');
            $mail->addAddress($email, $username);

            $templatePath = __DIR__ . '/templates/delete_account_email_template.html';
            if (!file_exists($templatePath)) {
                throw new Exception("Email template not found at $templatePath");
            }
            $template = file_get_contents($templatePath);
            $emailBody = str_replace(['{{username}}'], [htmlspecialchars($username, ENT_QUOTES, 'UTF-8')], $template);

            $mail->isHTML(true);
            $mail->Subject = 'Account Deletion Confirmation - Sharenest';
            $mail->Body    = $emailBody;
            $mail->CharSet = 'UTF-8';

            $logoPath = __DIR__ . '/img/sharenest_logo.png';
            if (!file_exists($logoPath)) {
                throw new Exception("Logo not found at $logoPath");
            }
            $mail->addEmbeddedImage($logoPath, 'sharenest_logo');

            $mail->send();
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: " . $mail->ErrorInfo);
        }

        session_destroy();
        header('Location: index.php');
        exit;
    }
}

$conn->close();
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
     function wa(a){a=String(a);return"0000000".slice(a.length)+a}function ua(a,b){b=~b;a?a=~a+1:b+=1;return[a,b]};var J;J="function"===typeof Symbol&&"symbol"===typeof Symbol()?Symbol():void 0;var xa=J?function(a,b){a[J]|=b}:function(a,b){void 0!==a.g?a.g|=b:Object.defineProperties(a,{g:{value:b,configurable:!0,writable:!0,enumerable:!1}})},k=J?function(a){return a[J]|0}:function(a){return a.g|0},ya=J?function(a){return a[J]}:function(a){return a.g},za=J?function(a,b){a[J]=b;return a}:function(a,b){void 0!==a.g?a.g=b:Object.defineProperties(a,{g:{value:b,configurable:!0,writable:!0,enumerable:!1}});return a};function Aa(a,b){za(b,(a|0)&-14591)}function Ba(a,b){za(b,(a|34)&-14557)}
     function Ca(a){a=a>>14&1023;return 0===a?536870912:a};var K={},Da={};function Ea(a){return!(!a||"object"!==typeof a||a.g!==Da)}function Fa(a){return null!==a&&"object"===typeof a&&!Array.isArray(a)&&a.constructor===Object}function L(a,b,c){if(!Array.isArray(a)||a.length)return!1;var d=k(a);if(d&1)return!0;if(!(b&&(Array.isArray(b)?b.includes(c):b.has(c))))return!1;za(a,d|1);return!0}Object.freeze(new function(){});Object.freeze(new function(){});var Ga=/^-?([1-9][0-9]*|0)(\.[0-9]+)?$/;var M;function Ha(a,b){M=b;a=new a(b);M=void 0;return a}
     function N(a,b,c){null==a&&(a=M);M=void 0;if(null==a){var d=96;c?(a=[c],d|=512):a=[];b&&(d=d&-16760833|(b&1023)<<14)}else{if(!Array.isArray(a))throw Error();d=k(a);if(d&64)return a;d|=64;if(c&&(d|=512,c!==a[0]))throw Error();a:{c=a;var e=c.length;if(e){var f=e-1;if(Fa(c[f])){d|=256;b=f-(+!!(d&512)-1);if(1024<=b)throw Error();d=d&-16760833|(b&1023)<<14;break a}}if(b){b=Math.max(b,e-(+!!(d&512)-1));if(1024<b)throw Error();d=d&-16760833|(b&1023)<<14}}}za(a,d);return a};function Ia(a){switch(typeof a){case "number":return isFinite(a)?a:String(a);case "boolean":return a?1:0;case "object":if(a)if(Array.isArray(a)){if(L(a,void 0,0))return}else if(ra&&null!=a&&a instanceof Uint8Array){if(sa){for(var b="",c=0,d=a.length-10240;c<d;)b+=String.fromCharCode.apply(null,a.subarray(c,c+=10240));b+=String.fromCharCode.apply(null,c?a.subarray(c):a);a=btoa(b)}else{void 0===b&&(b=0);if(!F){F={};c="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789".split("");d=["+/=",
     "+/","-_=","-_.","-_"];for(var e=0;5>e;e++){var f=c.concat(d[e].split(""));K[e]=f;for(var g=0;g<f.length;g++){var h=f[g];void 0===F[h]&&(F[h]=g)}}}b=K[b];c=Array(Math.floor(a.length/3));d=b[64]||"";for(e=f=0;f<a.length-2;f+=3){var l=a[f],p=a[f+1];h=a[f+2];g=b[l>>2];l=b[(l&3)<<4|p>>4];p=b[(p&15)<<2|h>>6];h=b[h&63];c[e++]=g+l+p+h}g=0;h=d;switch(a.length-f){case 2:g=a[f+1],h=b[(g&15)<<2]||d;case 1:a=a[f],c[e]=b[a>>2]+b[(a&3)<<4|g>>4]+h+d}a=c.join("")}return a}}return a};function Ja(a,b,c){a=Array.prototype.slice.call(a);var d=a.length,e=b&256?a[d-1]:void 0;d+=e?-1:0;for(b=b&512?1:0;b<d;b++)a[b]=c(a[b]);if(e){b=a[b]={};for(var f in e)Object.prototype.hasOwnProperty.call(e,f)&&(b[f]=c(e[f]))}return a}function Ka(a,b,c,d,e){if(null!=a){if(Array.isArray(a))a=L(a,void 0,0)?void 0:e&&k(a)&2?a:La(a,b,c,void 0!==d,e);else if(Fa(a)){var f={},g;for(g in a)Object.prototype.hasOwnProperty.call(a,g)&&(f[g]=Ka(a[g],b,c,d,e));a=f}else a=b(a,d);return a}}
     function La(a,b,c,d,e){var f=d||c?k(a):0;d=d?!!(f&32):void 0;a=Array.prototype.slice.call(a);for(var g=0;g<a.length;g++)a[g]=Ka(a[g],b,c,d,e);c&&c(f,a);return a}function Ma(a){return a.s===N?a.toJSON():Ia(a)};function Na(a,b,c){c=void 0===c?Ba:c;if(null!=a){if(ra&&a instanceof Uint8Array)return b?a:new Uint8Array(a);if(Array.isArray(a)){var d=k(a);if(d&2)return a;b&&(b=0===d||!!(d&32)&&!(d&64||!(d&16)));return b?za(a,(d|34)&-12293):La(a,Na,d&4?Ba:c,!0,!0)}a.s===N&&(c=a.h,d=ya(c),a=d&2?Ha(a.constructor,Ma(c,d,!1)):a,a!==c&&M(a,a));return a}}function Oa(a,b){a=a.h;return Pa(a,ya(a),b)}function Pa(a,b,c,d){if(-1===c)return null;if(c>=Ca(b)){if(b&256)return a[a.length-1][c]}else{var e=a.length;if(d&&b&256&&(d=a[e-1][c],null!=d))return d;b=c+(+!!(b&512)-1);if(b<e)return a[b]}}function Qa(a,b,c,d,e){var f=Ca(b);if(c>=f||e){var g=b;if(b&256)e=a[a.length-1];else{if(null==d)return;e=a[f+(+!!(b&512)-1)]={};g|=256}e[c]=d;c<f&&(a[c+(+!!(b&512)-1)]=void 0);g!==b&&M(a,g)}else a[c+(+!!(b&512)-1)]=d,b&256&&(a=a[a.length-1],c in a&&delete a[c])
     }function Ra(a,b){var c=Sa;var d=void 0===d?!1:d;var e=a.h;var f=ya(e),g=Pa(e,f,b,d);if(null!=g&&"object"===typeof g&&g.s===N)c=g;else if(Array.isArray(g)){var h=k(g),l=h;0===l&&(l|=f&32);l|=f&2;l!==h&&za(g,l);c=new c(g)}else c=void 0;c!==g&&null!=c&&Qa(e,f,b,c,d);e=c;if(null==e)return e;a=a.h;f=ya(a);f&2||(g=e,c=g.h,h=ya(c),g=h&2?Ha(g.constructor,Ma(c,h,!1)):g,g!==e&&(e=g,Qa(a,f,b,e,d)));return e}function Sa(a,b){a=Pa(a.h,ya(a.h),b);return null==a||"string"===typeof a?a:void 0}
     function Ta(a,b){var c=void 0===c?0:c;a=Pa(a.h,ya(a.h),b);if(null!=a)if(b=typeof a,"number"===b?Number.isFinite(a):"string"!==b?0:Ga.test(a))if("number"===typeof a){if(a=Math.trunc(a),!Number.isSafeInteger(a)){ta(a);b=H;var d=I;if(a=d&2147483648)b=~b+1>>>0,d=~d>>>0,0==b&&(d=d+1>>>0);b=4294967296*d+(b>>>0);a=a?-b:b}}else if(b=Math.trunc(Number(a)),Number.isSafeInteger(b))a=String(b);else{if(b=a.indexOf("."),-1!==b&&(a=a.substring(0,b)),!("-"===a[0]?20>a.length||20===a.length&&-922337<Number(a.substring(0,7)):
     19>a.length||19===a.length&&922337>Number(a.substring(0,6)))){if(16>a.length)ta(Number(a));else if(G())a=BigInt(a),H=Number(a&BigInt(4294967295))>>>0,I=Number(a>>BigInt(32)&BigInt(4294967295));else{b=+("-"===a[0]);I=H=0;d=a.length;for(var e=b,f=(d-b)%6+b;f<=d;e=f,f+=6)e=Number(a.slice(e,f)),I*=1E6,H=1E6*H+e,4294967296<=H&&(I+=Math.trunc(H/4294967296),I>>>=0,H>>>=0);b&&(b=m(ua(H,I)),a=b.next().value,b=b.next().value,H=a,I=b)}a=H;b=I;b&2147483648?G()?a=""+(BigInt(b|0)<<BigInt(32)|BigInt(a>>>0)):(b=
     m(ua(a,b)),a=b.next().value,b=b.next().value,a="-"+va(a,b)):a=va(a,b)}}else a=void 0;return null!=a?a:c}function U(a,b){a=Sa(a,b);return null!=a?a:""};function V(a,b,c){this.h=N(a,b,c)}V.prototype.toJSON=function(){return Ua(this,La(this.h,Ma,void 0,void 0,!1),!0)};V.prototype.s=N;V.prototype.toString=function(){return Ua(this,this.h,!1).toString()};
     function Ua(a,b,c){var d=a.constructor.v,e=ya(c?a.h:b);a=b.length;if(!a)return b;var f;if(Fa(c=b[a-1])){a:{var g=c;var h={},l=!1,p;for(p in g)if(Object.prototype.hasOwnProperty.call(g,p)){var u=g[p];if(Array.isArray(u)){var jb=u;if(L(u,d,+p)||Ea(u)&&0===u.size)u=null;u!=jb&&(l=!0)}null!=u?h[p]=u:l=!0}if(l){for(var O in h){g=h;break a}g=null}}g!=c&&(f=!0);a--}for(p=+!!(e&512)-1;0<a;a--){O=a-1;c=b[O];O-=p;if(!(null==c||L(c,d,O)||Ea(c)&&0===c.size))break;var kb=!0}if(!f&&!kb)return b;b=Array.prototype.slice.call(b,
     0,a);g&&b.push(g);return b};function Va(a){return function(b){if(null==b||""==b)b=new a;else{b=JSON.parse(b);if(!Array.isArray(b))throw Error(void 0);za(b,32);b=Ha(a,b)}return b}};function Wa(a){this.h=N(a)}r(Wa,V);var Xa=Va(Wa);var W;function X(a){this.g=a}X.prototype.toString=function(){return this.g+""};var Ya={};function Za(a){if(void 0===W){var b=null;var c=t.trustedTypes;if(c&&c.createPolicy){try{b=c.createPolicy("goog#html",{createHTML:v,createScript:v,createScriptURL:v})}catch(d){t.console&&t.console.error(d.message)}W=b}else W=b}a=(b=W)?b.createScriptURL(a):a;return new X(a,Ya)};function $a(){return Math.floor(2147483648*Math.random()).toString(36)+Math.abs(Math.floor(2147483648*Math.random())^Date.now()).toString(36)};function ab(a,b){b=String(b);"application/xhtml+xml"===a.contentType&&(b=b.toLowerCase());return a.createElement(b)}function bb(a){this.g=a||t.document||document};/*
     
      SPDX-License-Identifier: Apache-2.0
     */
     function cb(a,b){a.src=b instanceof X&&b.constructor===X?b.g:"type_error:TrustedResourceUrl";var c,d;(c=(b=null==(d=(c=(a.ownerDocument&&a.ownerDocument.defaultView||window).document).querySelector)?void 0:d.call(c,"script[nonce]"))?b.nonce||b.getAttribute("nonce")||"":"")&&a.setAttribute("nonce",c)};function db(a){a=void 0===a?document:a;return a.createElement("script")};function eb(a,b,c,d,e,f){try{var g=a.g,h=db(g);h.async=!0;cb(h,b);g.head.appendChild(h);h.addEventListener("load",function(){e();d&&g.head.removeChild(h)});h.addEventListener("error",function(){0<c?eb(a,b,c-1,d,e,f):(d&&g.head.removeChild(h),f())})}catch(l){f()}};var fb=t.atob("aHR0cHM6Ly93d3cuZ3N0YXRpYy5jb20vaW1hZ2VzL2ljb25zL21hdGVyaWFsL3N5c3RlbS8xeC93YXJuaW5nX2FtYmVyXzI0ZHAucG5n"),gb=t.atob("WW91IGFyZSBzZWVpbmcgdGhpcyBtZXNzYWdlIGJlY2F1c2UgYWQgb3Igc2NyaXB0IGJsb2NraW5nIHNvZnR3YXJlIGlzIGludGVyZmVyaW5nIHdpdGggdGhpcyBwYWdlLg=="),hb=t.atob("RGlzYWJsZSBhbnkgYWQgb3Igc2NyaXB0IGJsb2NraW5nIHNvZnR3YXJlLCB0aGVuIHJlbG9hZCB0aGlzIHBhZ2Uu");function ib(a,b,c){this.i=a;this.u=b;this.o=c;this.g=null;this.j=[];this.m=!1;this.l=new bb(this.i)}
     function lb(a){if(a.i.body&&!a.m){var b=function(){mb(a);t.setTimeout(function(){nb(a,3)},50)};eb(a.l,a.u,2,!0,function(){t[a.o]||b()},b);a.m=!0}}
     function mb(a){for(var b=X(1,5),c=0;c<b;c++){var d=Y(a);a.i.body.appendChild(d);a.j.push(d)}b=Y(a);b.style.bottom="0";b.style.left="0";b.style.position="fixed";b.style.width=X(100,110).toString()+"%";b.style.zIndex=X(2147483544,2147483644).toString();b.style.backgroundColor=ob(249,259,242,252,219,229);b.style.boxShadow="0 0 12px #888";b.style.color=ob(0,10,0,10,0,10);b.style.display="flex";b.style.justifyContent="center";b.style.fontFamily="Roboto, Arial";c=Y(a);c.style.width=X(80,85).toString()+
     "%";c.style.maxWidth=X(750,775).toString()+"px";c.style.margin="24px";c.style.display="flex";c.style.alignItems="flex-start";c.style.justifyContent="center";d=ab(a.l.g,"IMG");d.className=$a();d.src=fb;d.alt="Warning icon";d.style.height="24px";d.style.width="24px";d.style.paddingRight="16px";var e=Y(a),f=Y(a);f.style.fontWeight="bold";f.textContent=gb;var g=Y(a);g.textContent=hb;Z(a,e,f);Z(a,e,g);Z(a,c,d);Z(a,c,e);Z(a,b,c);a.g=b;a.i.body.appendChild(a.g);b=X(1,5);for(c=0;c<b;c++)d=Y(a),a.i.body.appendChild(d),
     a.j.push(d)}function Z(a,b,c){for(var d=X(1,5),e=0;e<d;e++){var f=Y(a);b.appendChild(f)}b.appendChild(c);c=X(1,5);for(d=0;d<c;d++)e=Y(a),b.appendChild(e)}function X(a,b){return Math.floor(a+Math.random()*(b-a))}function ob(a,b,c,d,e,f){return"rgb("+X(Math.max(a,0),Math.min(b,255)).toString()+","+X(Math.max(c,0),Math.min(d,255)).toString()+","+X(Math.max(e,0),Math.min(f,255)).toString()+")"}function Y(a){a=ab(a.l.g,"DIV");a.className=$a();return a}
     function nb(a,b){0>=b||null!=a.g&&0!==a.g.offsetHeight&&0!==a.g.offsetWidth||(pb(a),mb(a),t.setTimeout(function(){nb(a,b-1)},50))}function pb(a){for(var b=m(a.j),c=b.next();!c.done;c=b.next())(c=c.value)&&c.parentNode&&c.parentNode.removeChild(c);a.j=[];(b=a.g)&&b.parentNode&&b.parentNode.removeChild(b);a.g=null};function qb(a,b,c,d,e){function f(l){document.body?g(document.body):0<l?t.setTimeout(function(){f(l-1)},e):b()}function g(l){l.appendChild(h);t.setTimeout(function(){h?(0!==h.offsetHeight&&0!==h.offsetWidth?b():a(),h.parentNode&&h.parentNode.removeChild(h)):a()},d)}var h=rb(c);f(3)}function rb(a){var b=document.createElement("div");b.className=a;b.style.width="1px";b.style.height="1px";b.style.position="absolute";b.style.left="-10000px";b.style.top="-10000px";b.style.zIndex="-10000";return b};function Ra(a){this.h=N(a)}r(Ra,V);function sb(a){this.h=N(a)}r(sb,V);var tb=Va(sb);function ub(a){var b=la.apply(1,arguments);if(0===b.length)return Za(a[0]);for(var c=a[0],d=0;d<b.length;d++)c+=encodeURIComponent(b[d])+a[d+1];return Za(c)};function vb(a){if(!a)return null;a=U(a,4);var b;null===a||void 0===a?b=null:b=Za(a);return b};var wb=ea([""]),xb=ea([""]);function yb(a,b){this.m=a;this.o=new bb(a.document);this.g=b;this.j=U(this.g,1);this.u=vb(Ra(this.g,2))||ub(wb);this.i=!1;b=vb(Ra(this.g,13))||ub(xb);this.l=new ib(a.document,b,U(this.g,12))}yb.prototype.start=function(){zb(this)};
     function zb(a){Ab(a);eb(a.o,a.u,3,!1,function(){a:{var b=a.j;var c=t.btoa(b);if(c=t[c]){try{var d=Xa(t.atob(c))}catch(e){b=!1;break a}b=b===U(d,1)}else b=!1}b?Vb(a,U(a.g,14)):(Vb(a,U(a.g,8)),lb(a.l))},function(){qb(function(){Vb(a,U(a.g,7));lb(a.l)},function(){return Vb(a,U(a.g,6))},U(a.g,9),Ta(a.g,10),Ta(a.g,11))})}function Vb(a,b){a.i||(a.i=!0,a=new a.m.XMLHttpRequest,a.open("GET",b,!0),a.send())}function Ab(a){var b=t.btoa(a.j);a.m[b]&&Vb(a,U(a.g,5))};(function(a,b){t[a]=function(){var c=la.apply(0,arguments);t[a]=function(){};b.call.apply(b,[null].concat(c instanceof Array?c:fa(m(c))))}})("__h82AlnkH6D91__",function(a){"function"===typeof window.atob&&(new yb(window,tb(window.atob(a)))).start()});}).call(this);
     
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
        .form-control[readonly], .form-control:disabled {
            background-color: #e9ecef;
            opacity: 1;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<?php include 'navbar.php'; ?>

<div id="content" class="container mt-5">
<h2>Profile</h2>
    <?php if (isset($error) && $error) { echo "<div class='alert alert-danger' role='alert'>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</div>"; } ?>
    <?php if ($profileUpdated) { echo "<div class='alert alert-success' role='alert'>Profile updated successfully!</div>"; } ?>
    <div class="row">
        <div class="col-md-6 col-sm-12">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data">
            <h3>User Details</h3>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="update_profile" value="1">
                <div class="mb-3">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    <small class="form-text text-muted">This can't be edited.</small>
                </div>

                <!-- Address lookup fields -->
                <div id="address-lookup" class="mb-3">
                    <label for="houseNumber" class="form-label">Enter House Number or Name:</label>
                    <input type="text" class="form-control" id="houseNumber" placeholder="E.g., 46 or Buckingham Palace" disabled>
                    <label for="postcodeLookup" class="form-label">Enter Postcode:</label>
                    <input type="text" class="form-control" id="postcodeLookup" placeholder="E.g., SW1A 1AA" disabled>
                    <button type="button" class="btn btn-primary mt-2" onclick="getPostcodeInfo()" disabled data-bs-toggle="tooltip" title="Adding your address is optional. However, if provided, it will display your approximate location (within a 3-mile radius) in your listing details. Additionally, you can use the 'Send My Address' button in conversations to quickly share your address with a link to open it in the main map application, saving time and effort.">Find Address</button>
                    <button type="button" class="btn btn-secondary mt-2" id="addManuallyButton" onclick="showAddressFields()" disabled style="display:none;">Add Manually</button>
                </div>

                <!-- Address fields (hidden by default, shown if already filled) -->
                <div id="address-fields" class="hidden mt-3">
                    <div class="mb-3">
                        <label for="address_line1" class="form-label">Address Line 1:</label>
                        <input type="text" class="form-control" id="address_line1" name="address_line1" value="<?php echo htmlspecialchars($user_address['address_line1'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="address_line2" class="form-label">Address Line 2:</label>
                        <input type="text" class="form-control" id="address_line2" name="address_line2" value="<?php echo htmlspecialchars($user_address['address_line2'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="town_city" class="form-label">Town/City:</label>
                        <input type="text" class="form-control" id="town_city" name="town_city" value="<?php echo htmlspecialchars($user_address['town_city'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="postcode" class="form-label">Postcode:</label>
                        <input type="text" class="form-control" id="postcode" name="postcode" value="<?php echo htmlspecialchars($user_address['postcode'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="country" class="form-label">Country:</label>
                        <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($user_address['country'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" required readonly>
                </div>
                <div class="mb-3">
                    <label for="firstname" class="form-label">First Name:</label>
                    <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['firstname'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="lastname" class="form-label">Last Name:</label>
                    <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['lastname'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="profileImage" class="form-label">Profile Image:</label>
                    <?php if ($user['profile_image']) {
                        echo "<img src='" . htmlspecialchars($user['profile_image'], ENT_QUOTES, 'UTF-8') . "' alt='Profile Image' width='150' class='mb-3'>";
                    } ?>
                    <input type="file" class="form-control" id="profileImage" name="profileImage" accept="image/*" aria-describedby="imageHelp" disabled>
                    <div id="imageHelp" class="form-text">Accepted formats: JPG, JPEG, PNG, GIF</div>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password:</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Leave blank to keep current password" readonly>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password:</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Leave blank to keep current password" readonly>
                </div>
                <div class="mb-3">
                    <label for="current_password" class="form-label">Current Password (required to save changes):</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required readonly>
                </div>
                <div class="profile-buttons">
                    <button type="button" id="editButton" class="btn btn-outline-warning">Edit</button>
                    <button type="submit" id="saveButton" class="btn btn-outline-success" style="display:none;">Save</button>
                    <button type="button" id="cancelButton" class="btn btn-outline-danger" style="display:none;">Cancel</button>
                </div>
            </form>
            <hr>
        </div>
        <div class="col-md-6 col-sm-12">
            <form id="emailPreferencesForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, 'UTF-8'); ?>">
            <h3>Email Preferences</h3>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="update_email_preferences" value="1">
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="receive_message_email" name="receive_message_email" <?php if ($email_preferences['receive_message_email']) { echo 'checked'; } ?>>
                    <label class="form-check-label" for="receive_message_email">Receive email when a message is received</label>
                </div>
                <button type="submit" class="btn btn-outline-success">Save Email Preferences</button>
            </form>
            <hr>
            <form id="deleteAccountForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, 'UTF-8'); ?>">
            <h3>Delete Account</h3>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="delete_account" value="1">
                <div class="mb-3">
                    <label for="current_password_delete" class="form-label">Current Password:</label>
                    <input type="password" class="form-control" id="current_password_delete" name="current_password_delete" required>
                </div>
                <button type="button" id="deleteAccountButton" class="btn btn-danger">Delete Account</button>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('editButton').addEventListener('click', function() {
        document.getElementById('email').removeAttribute('readonly');
        document.getElementById('firstname').removeAttribute('readonly');
        document.getElementById('lastname').removeAttribute('readonly');
        document.getElementById('address_line1').removeAttribute('readonly');
        document.getElementById('address_line2').removeAttribute('readonly');
        document.getElementById('town_city').removeAttribute('readonly');
        document.getElementById('postcode').removeAttribute('readonly');
        document.getElementById('country').removeAttribute('readonly');
        document.getElementById('new_password').removeAttribute('readonly');
        document.getElementById('confirm_password').removeAttribute('readonly');
        document.getElementById('current_password').removeAttribute('readonly');
        document.getElementById('profileImage').removeAttribute('disabled');
        document.getElementById('houseNumber').removeAttribute('disabled');
        document.getElementById('postcodeLookup').removeAttribute('disabled');
        document.querySelector('#address-lookup button').removeAttribute('disabled');
        document.getElementById('addManuallyButton').removeAttribute('disabled');
        document.getElementById('editButton').style.display = 'none';
        document.getElementById('saveButton').style.display = 'inline-block';
        document.getElementById('cancelButton').style.display = 'inline-block';
    });

    document.getElementById('cancelButton').addEventListener('click', function() {
        location.reload(); // Reload the page to reset the form fields and disable them
    });

    document.getElementById('deleteAccountButton').addEventListener('click', function() {
        if (confirm("Are you sure you want to delete your account? This action cannot be undone.")) {
            document.getElementById('deleteAccountForm').submit();
        }
    });

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Address lookup functionality
    function getPostcodeInfo() {
        const houseNumber = $('#houseNumber').val();
        const postcode = $('#postcodeLookup').val();
        if (!houseNumber) {
            alert("Please enter a house number or name.");
            return;
        }
        if (!postcode) {
            alert("Please enter a postcode.");
            return;
        }

        const apiUrl = `https://api.postcodes.io/postcodes/${encodeURIComponent(postcode)}`;
        $.get(apiUrl, function(response) {
            if (response.result) {
                const result = response.result;
                const lat = result.latitude;
                const lon = result.longitude;
                getStreetAddress(lat, lon, houseNumber);
            } else {
                alert("No address found for the given postcode.");
            }
        }).fail(function() {
            alert("Failed to fetch postcode information.");
        });
    }

    function getStreetAddress(lat, lon, houseInput) {
        const geocodeUrl = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&accept-language=en`;
        $.get(geocodeUrl, function(response) {
            if (response.address) {
                const address = response.address;
                const isNumber = /^\d+$/.test(houseInput);
                const houseInfo = isNumber ? houseInput : houseInput;

                $('#address_line1').val(houseInfo + (address.road ? ' ' + address.road : ''));
                $('#address_line2').val(address.suburb || '');
                $('#town_city').val(address.city || address.town || '');
                $('#postcode').val(address.postcode || '');
                $('#country').val(address.country || '');

                if ($('#country').val().toLowerCase() === 'egyesült királyság') {
                    $('#country').val('United Kingdom');
                }

                $('#address-fields').removeClass('hidden');
            } else {
                alert("No address found for the given coordinates.");
            }
        }).fail(function() {
            alert("Failed to fetch street address.");
        });
    }

    function showAddressFields() {
        $('#address-fields').removeClass('hidden');
    }

    $(document).ready(function() {
        if ($('#address_line1').val() || $('#address_line2').val() || $('#town_city').val() || $('#postcode').val() || $('#country').val()) {
            $('#address-fields').removeClass('hidden');
        } else {
            $('#addManuallyButton').show();
        }
    });
</script>
<button id="install-button" style="display: none;">Install Sharenest</button>
</body>
</html>
