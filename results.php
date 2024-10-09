<?php
include 'session_timeout.php'; // Ensure session_start() is called here

error_reporting(E_ALL);

// Redirect non-admin users to the homepage
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'] || $_SESSION['is_admin'] !== 'true') {
    header('Location: index.php');
    exit;
}

include 'connection.php'; // Include the connection to your database

// Fetch total responses
$totalResponsesQuery = "SELECT COUNT(*) as total FROM SurveyResponses";
$totalResponsesResult = $conn->query($totalResponsesQuery);
$totalResponses = $totalResponsesResult->fetch_assoc()['total'];

// Function to fetch counts for a specific question
function fetchCounts($conn, $column) {
    $query = "SELECT $column, COUNT(*) as count FROM SurveyResponses GROUP BY $column";
    $result = $conn->query($query);
    $stats = [];
    while ($row = $result->fetch_assoc()) {
        $stats[$row[$column]] = $row['count'];
    }
    return $stats;
}

// Fetch statistics for all relevant columns
$consentStats = fetchCounts($conn, 'consent');
$recycleFrequencyStats = fetchCounts($conn, 'recycle_frequency');
$awarenessScaleStats = fetchCounts($conn, 'awareness_scale');
$materialsRecycledStats = fetchCounts($conn, 'materials_recycled');
$disposeItemsStats = fetchCounts($conn, 'dispose_items');
$usedPlatformStats = fetchCounts($conn, 'used_platform');
$platformsUsedStats = fetchCounts($conn, 'platforms_used');
$motivationStats = fetchCounts($conn, 'motivation');
$barriersStats = fetchCounts($conn, 'barriers');
$co2AwarenessStats = fetchCounts($conn, 'co2_awareness');
$confirmationStats = fetchCounts($conn, 'confirmation');
$deviceUsageStats = fetchCounts($conn, 'device_used');
$navigationRatingStats = fetchCounts($conn, 'navigation_rating');
$visualAppealRatingStats = fetchCounts($conn, 'visual_appeal_rating');
$contentClarityRatingStats = fetchCounts($conn, 'content_clarity_rating');
$designPurposeRatingStats = fetchCounts($conn, 'design_purpose_rating');
$colorPaletteRatingStats = fetchCounts($conn, 'color_palette_rating');
$layoutOrganisationRatingStats = fetchCounts($conn, 'layout_organisation_rating');
$aestheticRatingStats = fetchCounts($conn, 'aesthetic_rating');
$recommendRatingStats = fetchCounts($conn, 'recommend_rating');
$searchUsefulnessStats = fetchCounts($conn, 'search_usefulness');
$listingCreationStats = fetchCounts($conn, 'listing_creation');
$editListingStats = fetchCounts($conn, 'edit_listing');
$mapUsefulnessStats = fetchCounts($conn, 'map_usefulness');
$messagingUsefulnessStats = fetchCounts($conn, 'messaging_usefulness');
$gpsButtonUsefulnessStats = fetchCounts($conn, 'gps_button_usefulness');
$pointsSystemStats = fetchCounts($conn, 'points_system');
$reuseRecycleStats = fetchCounts($conn, 'reuse_recycle');
$encourageOthersStats = fetchCounts($conn, 'encourage_others');
$effectiveFeaturesStats = fetchCounts($conn, 'effective_features');
$locationMapStats = fetchCounts($conn, 'location_map');
$pointsSystemMotivationStats = fetchCounts($conn, 'points_system_motivation');
$co2MotivationStats = fetchCounts($conn, 'co2_motivation');
$suggestionsStats = fetchCounts($conn, 'suggestions');

// Close the database connection
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://getbootstrap.com/docs/5.3/assets/css/docs.css" rel="stylesheet">
<link href="css/styles.css" rel="stylesheet">
    <style>
        .card {
            margin-bottom: 20px;
        }
        .chart-container {
            position: relative;
            height: 40vh;
            width: 80vw;
        }
    </style>
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<div class="container">
    <h1>Survey Results</h1>
    <h2>Total Responses: <?php echo $totalResponses; ?></h2>

    <!-- Consent Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Consent Statistics</h3>
        </div>
        <div class="card-body">
            <ul class="list-group">
                <li class="list-group-item">Yes: <?php echo isset($consentStats['Yes']) ? $consentStats['Yes'] : 0; ?></li>
                <li class="list-group-item">No: <?php echo isset($consentStats['No']) ? $consentStats['No'] : 0; ?></li>
            </ul>
        </div>
    </div>

    <!-- Recycling Frequency Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Recycling Frequency Statistics</h3>
        </div>
        <div class="card-body">
            <canvas id="recycleFrequencyChart" class="chart-container"></canvas>
            <script>
                const recycleFrequencyData = {
                    labels: <?php echo json_encode(array_keys($recycleFrequencyStats)); ?>,
                    datasets: [{
                        label: 'Recycling Frequency',
                        data: <?php echo json_encode(array_values($recycleFrequencyStats)); ?>,
                        backgroundColor: '#5cb85c',
                        borderColor: '#4cae4c',
                        borderWidth: 1
                    }]
                };
                const recycleFrequencyConfig = {
                    type: 'bar',
                    data: recycleFrequencyData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const recycleFrequencyChart = new Chart(
                    document.getElementById('recycleFrequencyChart'),
                    recycleFrequencyConfig
                );
            </script>
        </div>
    </div>

    <!-- Awareness Scale Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Awareness Scale Statistics (1-5)</h3>
        </div>
        <div class="card-body">
            <canvas id="awarenessScaleChart" class="chart-container"></canvas>
            <script>
                const awarenessScaleData = {
                    labels: ['1', '2', '3', '4', '5'],
                    datasets: [{
                        label: 'Awareness Level',
                        data: [
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo isset($awarenessScaleStats[$i]) ? $awarenessScaleStats[$i] : 0; ?>,
                            <?php endfor; ?>
                        ],
                        backgroundColor: '#0275d8',
                        borderColor: '#0056b3',
                        borderWidth: 1
                    }]
                };
                const awarenessScaleConfig = {
                    type: 'line',
                    data: awarenessScaleData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const awarenessScaleChart = new Chart(
                    document.getElementById('awarenessScaleChart'),
                    awarenessScaleConfig
                );
            </script>
        </div>
    </div>

    <!-- Materials Recycled Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Materials Recycled Statistics</h3>
        </div>
        <div class="card-body">
            <canvas id="materialsRecycledChart" class="chart-container"></canvas>
            <script>
                const materialsRecycledData = {
                    labels: <?php echo json_encode(array_keys($materialsRecycledStats)); ?>,
                    datasets: [{
                        label: 'Materials Recycled',
                        data: <?php echo json_encode(array_values($materialsRecycledStats)); ?>,
                        backgroundColor: '#5bc0de',
                        borderColor: '#31b0d5',
                        borderWidth: 1
                    }]
                };
                const materialsRecycledConfig = {
                    type: 'bar',
                    data: materialsRecycledData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const materialsRecycledChart = new Chart(
                    document.getElementById('materialsRecycledChart'),
                    materialsRecycledConfig
                );
            </script>
        </div>
    </div>

    <!-- Dispose Items Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Dispose Items Statistics</h3>
        </div>
        <div class="card-body">
            <canvas id="disposeItemsChart" class="chart-container"></canvas>
            <script>
                const disposeItemsData = {
                    labels: <?php echo json_encode(array_keys($disposeItemsStats)); ?>,
                    datasets: [{
                        label: 'Dispose Items',
                        data: <?php echo json_encode(array_values($disposeItemsStats)); ?>,
                        backgroundColor: '#d9534f',
                        borderColor: '#c9302c',
                        borderWidth: 1
                    }]
                };
                const disposeItemsConfig = {
                    type: 'bar',
                    data: disposeItemsData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const disposeItemsChart = new Chart(
                    document.getElementById('disposeItemsChart'),
                    disposeItemsConfig
                );
            </script>
        </div>
    </div>

    <!-- Used Platform Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Used Platform Statistics</h3>
        </div>
        <div class="card-body">
            <ul class="list-group">
                <li class="list-group-item">Yes: <?php echo isset($usedPlatformStats['Yes']) ? $usedPlatformStats['Yes'] : 0; ?></li>
                <li class="list-group-item">No: <?php echo isset($usedPlatformStats['No']) ? $usedPlatformStats['No'] : 0; ?></li>
            </ul>
        </div>
    </div>

    <!-- Motivations Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Motivations Statistics</h3>
        </div>
        <div class="card-body">
            <canvas id="motivationChart" class="chart-container"></canvas>
            <script>
                const motivationData = {
                    labels: <?php echo json_encode(array_keys($motivationStats)); ?>,
                    datasets: [{
                        label: 'Motivations',
                        data: <?php echo json_encode(array_values($motivationStats)); ?>,
                        backgroundColor: '#5bc0de',
                        borderColor: '#31b0d5',
                        borderWidth: 1
                    }]
                };
                const motivationConfig = {
                    type: 'bar',
                    data: motivationData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const motivationChart = new Chart(
                    document.getElementById('motivationChart'),
                    motivationConfig
                );
            </script>
        </div>
    </div>

    <!-- Barriers Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Barriers Statistics</h3>
        </div>
        <div class="card-body">
            <canvas id="barriersChart" class="chart-container"></canvas>
            <script>
                const barriersData = {
                    labels: <?php echo json_encode(array_keys($barriersStats)); ?>,
                    datasets: [{
                        label: 'Barriers',
                        data: <?php echo json_encode(array_values($barriersStats)); ?>,
                        backgroundColor: '#5bc0de',
                        borderColor: '#31b0d5',
                        borderWidth: 1
                    }]
                };
                const barriersConfig = {
                    type: 'bar',
                    data: barriersData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const barriersChart = new Chart(
                    document.getElementById('barriersChart'),
                    barriersConfig
                );
            </script>
        </div>
    </div>

    <!-- Device Usage Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Device Usage Statistics</h3>
        </div>
        <div class="card-body">
            <ul class="list-group">
                <?php foreach ($deviceUsageStats as $device => $count): ?>
                    <li class="list-group-item"><?php echo $device; ?>: <?php echo $count; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- CO2 Awareness Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>CO2 Awareness Statistics (1-5)</h3>
        </div>
        <div class="card-body">
            <canvas id="co2AwarenessChart" class="chart-container"></canvas>
            <script>
                const co2AwarenessData = {
                    labels: ['1', '2', '3', '4', '5'],
                    datasets: [{
                        label: 'CO2 Awareness Level',
                        data: [
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo isset($co2AwarenessStats[$i]) ? $co2AwarenessStats[$i] : 0; ?>,
                            <?php endfor; ?>
                        ],
                        backgroundColor: '#d9534f',
                        borderColor: '#c9302c',
                        borderWidth: 1
                    }]
                };
                const co2AwarenessConfig = {
                    type: 'bar',
                    data: co2AwarenessData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const co2AwarenessChart = new Chart(
                    document.getElementById('co2AwarenessChart'),
                    co2AwarenessConfig
                );
            </script>
        </div>
    </div>

    <!-- Confirmation Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Confirmation Statistics</h3>
        </div>
        <div class="card-body">
            <ul class="list-group">
                <li class="list-group-item">I confirm: <?php echo isset($confirmationStats['I confirm that I have visited the ShareNest website']) ? $confirmationStats['I confirm that I have visited the ShareNest website'] : 0; ?></li>
                <li class="list-group-item">Changed my mind: <?php echo isset($confirmationStats['NO, this sounds like a lot of things to do. I\'ve changed my mind']) ? $confirmationStats['NO, this sounds like a lot of things to do. I\'ve changed my mind'] : 0; ?></li>
            </ul>
        </div>
    </div>

    <!-- Navigation Rating Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Navigation Rating Statistics (1-5)</h3>
        </div>
        <div class="card-body">
            <canvas id="navigationRatingChart" class="chart-container"></canvas>
            <script>
                const navigationRatingData = {
                    labels: ['1', '2', '3', '4', '5'],
                    datasets: [{
                        label: 'Navigation Rating',
                        data: [
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo isset($navigationRatingStats[$i]) ? $navigationRatingStats[$i] : 0; ?>,
                            <?php endfor; ?>
                        ],
                        backgroundColor: '#f0ad4e',
                        borderColor: '#ec971f',
                        borderWidth: 1
                    }]
                };
                const navigationRatingConfig = {
                    type: 'bar',
                    data: navigationRatingData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const navigationRatingChart = new Chart(
                    document.getElementById('navigationRatingChart'),
                    navigationRatingConfig
                );
            </script>
        </div>
    </div>

    <!-- Visual Appeal Rating Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Visual Appeal Rating Statistics (1-5)</h3>
        </div>
        <div class="card-body">
            <canvas id="visualAppealRatingChart" class="chart-container"></canvas>
            <script>
                const visualAppealRatingData = {
                    labels: ['1', '2', '3', '4', '5'],
                    datasets: [{
                        label: 'Visual Appeal Rating',
                        data: [
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo isset($visualAppealRatingStats[$i]) ? $visualAppealRatingStats[$i] : 0; ?>,
                            <?php endfor; ?>
                        ],
                        backgroundColor: '#5bc0de',
                        borderColor: '#31b0d5',
                        borderWidth: 1
                    }]
                };
                const visualAppealRatingConfig = {
                    type: 'bar',
                    data: visualAppealRatingData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const visualAppealRatingChart = new Chart(
                    document.getElementById('visualAppealRatingChart'),
                    visualAppealRatingConfig
                );
            </script>
        </div>
    </div>

    <!-- Content Clarity Rating Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Content Clarity Rating Statistics (1-5)</h3>
        </div>
        <div class="card-body">
            <canvas id="contentClarityRatingChart" class="chart-container"></canvas>
            <script>
                const contentClarityRatingData = {
                    labels: ['1', '2', '3', '4', '5'],
                    datasets: [{
                        label: 'Content Clarity Rating',
                        data: [
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo isset($contentClarityRatingStats[$i]) ? $contentClarityRatingStats[$i] : 0; ?>,
                            <?php endfor; ?>
                        ],
                        backgroundColor: '#5bc0de',
                        borderColor: '#31b0d5',
                        borderWidth: 1
                    }]
                };
                const contentClarityRatingConfig = {
                    type: 'bar',
                    data: contentClarityRatingData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const contentClarityRatingChart = new Chart(
                    document.getElementById('contentClarityRatingChart'),
                    contentClarityRatingConfig
                );
            </script>
        </div>
    </div>

    <!-- Design Purpose Rating Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Design Purpose Rating Statistics (1-5)</h3>
        </div>
        <div class="card-body">
            <canvas id="designPurposeRatingChart" class="chart-container"></canvas>
            <script>
                const designPurposeRatingData = {
                    labels: ['1', '2', '3', '4', '5'],
                    datasets: [{
                        label: 'Design Purpose Rating',
                        data: [
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo isset($designPurposeRatingStats[$i]) ? $designPurposeRatingStats[$i] : 0; ?>,
                            <?php endfor; ?>
                        ],
                        backgroundColor: '#5bc0de',
                        borderColor: '#31b0d5',
                        borderWidth: 1
                    }]
                };
                const designPurposeRatingConfig = {
                    type: 'bar',
                    data: designPurposeRatingData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const designPurposeRatingChart = new Chart(
                    document.getElementById('designPurposeRatingChart'),
                    designPurposeRatingConfig
                );
            </script>
        </div>
    </div>

    <!-- Color Palette Rating Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Color Palette Rating Statistics (1-5)</h3>
        </div>
        <div class="card-body">
            <canvas id="colorPaletteRatingChart" class="chart-container"></canvas>
            <script>
                const colorPaletteRatingData = {
                    labels: ['1', '2', '3', '4', '5'],
                    datasets: [{
                        label: 'Color Palette Rating',
                        data: [
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo isset($colorPaletteRatingStats[$i]) ? $colorPaletteRatingStats[$i] : 0; ?>,
                            <?php endfor; ?>
                        ],
                        backgroundColor: '#5bc0de',
                        borderColor: '#31b0d5',
                        borderWidth: 1
                    }]
                };
                const colorPaletteRatingConfig = {
                    type: 'bar',
                    data: colorPaletteRatingData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const colorPaletteRatingChart = new Chart(
                    document.getElementById('colorPaletteRatingChart'),
                    colorPaletteRatingConfig
                );
            </script>
        </div>
    </div>

    <!-- Layout Organisation Rating Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Layout Organisation Rating Statistics (1-5)</h3>
        </div>
        <div class="card-body">
            <canvas id="layoutOrganisationRatingChart" class="chart-container"></canvas>
            <script>
                const layoutOrganisationRatingData = {
                    labels: ['1', '2', '3', '4', '5'],
                    datasets: [{
                        label: 'Layout Organisation Rating',
                        data: [
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo isset($layoutOrganisationRatingStats[$i]) ? $layoutOrganisationRatingStats[$i] : 0; ?>,
                            <?php endfor; ?>
                        ],
                        backgroundColor: '#5bc0de',
                        borderColor: '#31b0d5',
                        borderWidth: 1
                    }]
                };
                const layoutOrganisationRatingConfig = {
                    type: 'bar',
                    data: layoutOrganisationRatingData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const layoutOrganisationRatingChart = new Chart(
                    document.getElementById('layoutOrganisationRatingChart'),
                    layoutOrganisationRatingConfig
                );
            </script>
        </div>
    </div>

    <!-- Aesthetic Rating Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Aesthetic Rating Statistics (1-5)</h3>
        </div>
        <div class="card-body">
            <canvas id="aestheticRatingChart" class="chart-container"></canvas>
            <script>
                const aestheticRatingData = {
                    labels: ['1', '2', '3', '4', '5'],
                    datasets: [{
                        label: 'Aesthetic Rating',
                        data: [
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo isset($aestheticRatingStats[$i]) ? $aestheticRatingStats[$i] : 0; ?>,
                            <?php endfor; ?>
                        ],
                        backgroundColor: '#5bc0de',
                        borderColor: '#31b0d5',
                        borderWidth: 1
                    }]
                };
                const aestheticRatingConfig = {
                    type: 'bar',
                    data: aestheticRatingData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const aestheticRatingChart = new Chart(
                    document.getElementById('aestheticRatingChart'),
                    aestheticRatingConfig
                );
            </script>
        </div>
    </div>

    <!-- Recommend Rating Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Recommend Rating Statistics (1-5)</h3>
        </div>
        <div class="card-body">
            <canvas id="recommendRatingChart" class="chart-container"></canvas>
            <script>
                const recommendRatingData = {
                    labels: ['1', '2', '3', '4', '5'],
                    datasets: [{
                        label: 'Recommend Rating',
                        data: [
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo isset($recommendRatingStats[$i]) ? $recommendRatingStats[$i] : 0; ?>,
                            <?php endfor; ?>
                        ],
                        backgroundColor: '#5bc0de',
                        borderColor: '#31b0d5',
                        borderWidth: 1
                    }]
                };
                const recommendRatingConfig = {
                    type: 'bar',
                    data: recommendRatingData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const recommendRatingChart = new Chart(
                    document.getElementById('recommendRatingChart'),
                    recommendRatingConfig
                );
            </script>
        </div>
    </div>

    <!-- Search Usefulness Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Search Usefulness Statistics (1-5)</h3>
        </div>
        <div class="card-body">
            <canvas id="searchUsefulnessChart" class="chart-container"></canvas>
            <script>
                const searchUsefulnessData = {
                    labels: ['1', '2', '3', '4', '5'],
                    datasets: [{
                        label: 'Search Usefulness',
                        data: [
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo isset($searchUsefulnessStats[$i]) ? $searchUsefulnessStats[$i] : 0; ?>,
                            <?php endfor; ?>
                        ],
                        backgroundColor: '#5bc0de',
                        borderColor: '#31b0d5',
                        borderWidth: 1
                    }]
                };
                const searchUsefulnessConfig = {
                    type: 'bar',
                    data: searchUsefulnessData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const searchUsefulnessChart = new Chart(
                    document.getElementById('searchUsefulnessChart'),
                    searchUsefulnessConfig
                );
            </script>
        </div>
    </div>

    <!-- Listing Creation Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Listing Creation Statistics (1-5)</h3>
        </div>
        <div class="card-body">
            <canvas id="listingCreationChart" class="chart-container"></canvas>
            <script>
                const listingCreationData = {
                    labels: ['1', '2', '3', '4', '5'],
                    datasets: [{
                        label: 'Listing Creation',
                        data: [
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo isset($listingCreationStats[$i]) ? $listingCreationStats[$i] : 0; ?>,
                            <?php endfor; ?>
                        ],
                        backgroundColor: '#5bc0de',
                        borderColor: '#31b0d5',
                        borderWidth: 1
                    }]
                };
                const listingCreationConfig = {
                    type: 'bar',
                    data: listingCreationData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const listingCreationChart = new Chart(
                    document.getElementById('listingCreationChart'),
                    listingCreationConfig
                );
            </script>
        </div>
    </div>

    <!-- Edit Listing Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Edit Listing Statistics (1-5)</h3>
        </div>
        <div class="card-body">
            <canvas id="editListingChart" class="chart-container"></canvas>
            <script>
                const editListingData = {
                    labels: ['1', '2', '3', '4', '5'],
                    datasets: [{
                        label: 'Edit Listing',
                        data: [
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo isset($editListingStats[$i]) ? $editListingStats[$i] : 0; ?>,
                            <?php endfor; ?>
                        ],
                        backgroundColor: '#5bc0de',
                        borderColor: '#31b0d5',
                        borderWidth: 1
                    }]
                };
                const editListingConfig = {
                    type: 'bar',
                    data: editListingData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const editListingChart = new Chart(
                    document.getElementById('editListingChart'),
                    editListingConfig
                );
            </script>
        </div>
    </div>

    <!-- Map Usefulness Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Map Usefulness Statistics (1-5)</h3>
        </div>
        <div class="card-body">
            <canvas id="mapUsefulnessChart" class="chart-container"></canvas>
            <script>
                const mapUsefulnessData = {
                    labels: ['1', '2', '3', '4', '5'],
                    datasets: [{
                        label: 'Map Usefulness',
                        data: [
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo isset($mapUsefulnessStats[$i]) ? $mapUsefulnessStats[$i] : 0; ?>,
                            <?php endfor; ?>
                        ],
                        backgroundColor: '#5bc0de',
                        borderColor: '#31b0d5',
                        borderWidth: 1
                    }]
                };
                const mapUsefulnessConfig = {
                    type: 'bar',
                    data: mapUsefulnessData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const mapUsefulnessChart = new Chart(
                    document.getElementById('mapUsefulnessChart'),
                    mapUsefulnessConfig
                );
            </script>
        </div>
    </div>

    <!-- Messaging Usefulness Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Messaging Usefulness Statistics (1-5)</h3>
        </div>
        <div class="card-body">
            <canvas id="messagingUsefulnessChart" class="chart-container"></canvas>
            <script>
                const messagingUsefulnessData = {
                    labels: ['1', '2', '3', '4', '5'],
                    datasets: [{
                        label: 'Messaging Usefulness',
                        data: [
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo isset($messagingUsefulnessStats[$i]) ? $messagingUsefulnessStats[$i] : 0; ?>,
                            <?php endfor; ?>
                        ],
                        backgroundColor: '#5bc0de',
                        borderColor: '#31b0d5',
                        borderWidth: 1
                    }]
                };
                const messagingUsefulnessConfig = {
                    type: 'bar',
                    data: messagingUsefulnessData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const messagingUsefulnessChart = new Chart(
                    document.getElementById('messagingUsefulnessChart'),
                    messagingUsefulnessConfig
                );
            </script>
        </div>
    </div>

    <!-- GPS Button Usefulness Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>GPS Button Usefulness Statistics (1-5)</h3>
        </div>
        <div class="card-body">
            <canvas id="gpsButtonUsefulnessChart" class="chart-container"></canvas>
            <script>
                const gpsButtonUsefulnessData = {
                    labels: ['1', '2', '3', '4', '5'],
                    datasets: [{
                        label: 'GPS Button Usefulness',
                        data: [
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo isset($gpsButtonUsefulnessStats[$i]) ? $gpsButtonUsefulnessStats[$i] : 0; ?>,
                            <?php endfor; ?>
                        ],
                        backgroundColor: '#5bc0de',
                        borderColor: '#31b0d5',
                        borderWidth: 1
                    }]
                };
                const gpsButtonUsefulnessConfig = {
                    type: 'bar',
                    data: gpsButtonUsefulnessData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const gpsButtonUsefulnessChart = new Chart(
                    document.getElementById('gpsButtonUsefulnessChart'),
                    gpsButtonUsefulnessConfig
                );
            </script>
        </div>
    </div>

    <!-- Points System Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Points System Statistics (1-5)</h3>
        </div>
        <div class="card-body">
            <canvas id="pointsSystemChart" class="chart-container"></canvas>
            <script>
                const pointsSystemData = {
                    labels: ['1', '2', '3', '4', '5'],
                    datasets: [{
                        label: 'Points System',
                        data: [
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo isset($pointsSystemStats[$i]) ? $pointsSystemStats[$i] : 0; ?>,
                            <?php endfor; ?>
                        ],
                        backgroundColor: '#5bc0de',
                        borderColor: '#31b0d5',
                        borderWidth: 1
                    }]
                };
                const pointsSystemConfig = {
                    type: 'bar',
                    data: pointsSystemData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const pointsSystemChart = new Chart(
                    document.getElementById('pointsSystemChart'),
                    pointsSystemConfig
                );
            </script>
        </div>
    </div>

    <!-- Reuse/Recycling Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Reuse/Recycling Statistics</h3>
        </div>
        <div class="card-body">
            <ul class="list-group">
                <li class="list-group-item">Yes: <?php echo isset($reuseRecycleStats['Yes']) ? $reuseRecycleStats['Yes'] : 0; ?></li>
                <li class="list-group-item">No: <?php echo isset($reuseRecycleStats['No']) ? $reuseRecycleStats['No'] : 0; ?></li>
                <li class="list-group-item">Unsure: <?php echo isset($reuseRecycleStats['Unsure']) ? $reuseRecycleStats['Unsure'] : 0; ?></li>
            </ul>
        </div>
    </div>

    <!-- Encourage Others Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Encourage Others Statistics</h3>
        </div>
        <div class="card-body">
            <ul class="list-group">
                <li class="list-group-item">Yes: <?php echo isset($encourageOthersStats['Yes']) ? $encourageOthersStats['Yes'] : 0; ?></li>
                <li class="list-group-item">No: <?php echo isset($encourageOthersStats['No']) ? $encourageOthersStats['No'] : 0; ?></li>
                <li class="list-group-item">Unsure: <?php echo isset($encourageOthersStats['Unsure']) ? $encourageOthersStats['Unsure'] : 0; ?></li>
            </ul>
        </div>
    </div>

    <!-- Effective Features Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Effective Features Statistics</h3>
        </div>
        <div class="card-body">
            <p><?php echo nl2br(htmlspecialchars(implode(", ", array_filter(array_keys($effectiveFeaturesStats))))); ?></p>
        </div>
    </div>

    <!-- Location Map Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Location Map Statistics</h3>
        </div>
        <div class="card-body">
            <ul class="list-group">
                <li class="list-group-item">Yes: <?php echo isset($locationMapStats['Yes']) ? $locationMapStats['Yes'] : 0; ?></li>
                <li class="list-group-item">No: <?php echo isset($locationMapStats['No']) ? $locationMapStats['No'] : 0; ?></li>
                <li class="list-group-item">Maybe: <?php echo isset($locationMapStats['Maybe']) ? $locationMapStats['Maybe'] : 0; ?></li>
            </ul>
        </div>
    </div>

    <!-- Points System Motivation Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Points System Motivation Statistics</h3>
        </div>
        <div class="card-body">
            <ul class="list-group">
                <li class="list-group-item">Yes: <?php echo isset($pointsSystemMotivationStats['Yes']) ? $pointsSystemMotivationStats['Yes'] : 0; ?></li>
                <li class="list-group-item">No: <?php echo isset($pointsSystemMotivationStats['No']) ? $pointsSystemMotivationStats['No'] : 0; ?></li>
                <li class="list-group-item">Maybe: <?php echo isset($pointsSystemMotivationStats['Maybe']) ? $pointsSystemMotivationStats['Maybe'] : 0; ?></li>
            </ul>
        </div>
    </div>

    <!-- CO2 Motivation Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>CO2 Motivation Statistics (1-5)</h3>
        </div>
        <div class="card-body">
            <canvas id="co2MotivationChart" class="chart-container"></canvas>
            <script>
                const co2MotivationData = {
                    labels: ['1', '2', '3', '4', '5'],
                    datasets: [{
                        label: 'CO2 Motivation',
                        data: [
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo isset($co2MotivationStats[$i]) ? $co2MotivationStats[$i] : 0; ?>,
                            <?php endfor; ?>
                        ],
                        backgroundColor: '#5bc0de',
                        borderColor: '#31b0d5',
                        borderWidth: 1
                    }]
                };
                const co2MotivationConfig = {
                    type: 'bar',
                    data: co2MotivationData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                const co2MotivationChart = new Chart(
                    document.getElementById('co2MotivationChart'),
                    co2MotivationConfig
                );
            </script>
        </div>
    </div>

    <!-- Suggestions Statistics -->
    <div class="card">
        <div class="card-header">
            <h3>Suggestions</h3>
        </div>
        <div class="card-body">
            <p><?php echo nl2br(htmlspecialchars(implode("\n", array_filter($suggestionsStats)))); ?></p>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
