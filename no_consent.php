<?php
// no_consent.php
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>No Consent - ShareNest Survey</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .message-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .message-title {
            color: #dc3545; /* Bootstrap danger color */
            font-weight: bold;
            margin-bottom: 20px;
        }
        .message-text {
            font-size: 18px;
            color: #333333;
            margin-bottom: 20px;
        }
        .back-to-home-btn {
            color: #ffffff;
            background-color: #5cb85c;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            transition: background-color 0.3s ease;
            text-decoration: none;
        }
        .back-to-home-btn:hover {
            background-color: #4cae4c;
        }
    </style>
</head>
<body>

<div class="message-container">
    <h1 class="message-title">Consent Required</h1>
    <p class="message-text">
        You have chosen not to participate in the survey. Consent is required to continue.
    </p>
    <p class="message-text">
        If you have any questions or concerns, feel free to reach out to us.
    </p>
    <a href="https://sharenest.org" class="back-to-home-btn">Back to Home</a>
</div>

</body>
</html>
