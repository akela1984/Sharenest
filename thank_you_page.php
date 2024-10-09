<?php
// thank_you_page.php
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thank You - ShareNest Survey</title>
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
        .thank-you-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .thank-you-title {
            color: #4CAF50;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .thank-you-message {
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

<div class="thank-you-container">
    <h1 class="thank-you-title">Thank You for Your Participation!</h1>
    <p class="thank-you-message">
        We appreciate your time and feedback in helping us evaluate the ShareNest platform. Your insights are invaluable in our effort to promote sustainable waste management practices. 
    </p>
    <p class="thank-you-message">
        Feel free to continue exploring ShareNest and discover ways you can contribute to reducing waste in your community.
    </p>
    <a href="https://sharenest.org" class="back-to-home-btn">Back to Home</a>
</div>

</body>
</html>
