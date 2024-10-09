<?php
if (session_status() === PHP_SESSION_NONE) {
    include 'session_timeout.php';
}

// Redirect non-logged-in users to the sign-in page
if (!isset($_SESSION['loggedin'])) {
    header('Location: signin.php');
    exit;
}

include 'connection.php';

// Fetch user's information
$username = $_SESSION['username'];
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "<p>User not found. Please <a href='signin.php'>sign in</a> again.</p>";
    exit;
}

$user_id = $user['id'];

// Fetch all conversations for the logged-in user
$sql = "
    SELECT c.id AS conversation_id, l.title AS listing_title, u.username AS other_user, m.sent_at, l.id AS listing_id, l.user_id AS listing_owner_id,
    (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND recipient_id = ? AND `read` = FALSE) AS unread_count
    FROM conversations c
    JOIN conversation_members cm1 ON c.id = cm1.conversation_id
    JOIN conversation_members cm2 ON c.id = cm2.conversation_id
    JOIN listings l ON c.listing_id = l.id
    JOIN users u ON cm2.user_id = u.id
    JOIN (
        SELECT conversation_id, sent_at
        FROM messages
        WHERE id IN (
            SELECT MAX(id)
            FROM messages
            GROUP BY conversation_id
        )
    ) m ON c.id = m.conversation_id
    WHERE cm1.user_id = ? AND cm2.user_id != ?
    ORDER BY m.sent_at DESC
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$conversations = [];
while ($row = $result->fetch_assoc()) {
    $conversations[] = $row;
}

// Fetch unread messages count for the navbar
$sql_unread_count = "SELECT COUNT(*) AS unread_count FROM messages WHERE recipient_id = ? AND `read` = FALSE";
$stmt_unread_count = $conn->prepare($sql_unread_count);
$stmt_unread_count->bind_param("i", $user_id);
$stmt_unread_count->execute();
$result_unread_count = $stmt_unread_count->get_result();
$unread_count = $result_unread_count->fetch_assoc()['unread_count'];

// Fetch user address
$sql_address = "SELECT * FROM users_address WHERE user_id = ?";
$stmt_address = $conn->prepare($sql_address);
$stmt_address->bind_param("i", $user_id);
$stmt_address->execute();
$result_address = $stmt_address->get_result();

$address = null;
if ($result_address->num_rows > 0) {
    $address = $result_address->fetch_assoc();
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Messages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://getbootstrap.com/docs/5.3/assets/css/docs.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        .conversation-box {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
            position: relative;
        }
        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .conversation-content {
            display: flex;
            flex-direction: column;
        }
        .conversation-footer {
            margin-top: auto;
            font-size: 0.9rem;
            color: #888;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }
        .modal-dialog-scrollable {
            max-height: 90vh;
        }
        .message-bubble {
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 10px;
            max-width: 75%;
        }
        .message-bubble-left {
            background-color: #e9ecef;
            align-self: flex-start;
        }
        .message-bubble-right {
            background-color: #5cb85c;
            color: white;
            align-self: flex-end;
        }
        .form-container {
            width: 100%;
        }
        .suggestion-buttons {
            margin-bottom: 10px;
            display: flex;
            justify-content: center;
        }
        .suggestion-button {
            font-size: 0.8rem;
            margin: 0 5px;
        }
        .badge-container {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }
        .badge-container .badge {
            margin-bottom: 5px;
        }
        .seen-status {
            font-size: 0.8rem;
            color: yellow;
        }
        .conversation-header .badge.unread-badge {
            margin-right: 5px;
        }
        .map-links {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .map-link {
            text-decoration: none;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .map-link svg {
            width: 20px;
            height: 20px;
        }
    </style>
</head>
<body class="p-3 m-0 border-0 bd-example m-0 border-0">

<!-- Navbar STARTS here -->
<?php include 'navbar.php'; ?>
<!-- Navbar ENDS here -->

<div class="container mt-5">
    <h2>My Messages</h2>
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-info"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>

    <?php if (empty($conversations)): ?>
        <div class="alert alert-info" role="alert">
            You have no conversations yet.
        </div>
    <?php else: ?>
        <?php foreach ($conversations as $conversation): ?>
            <div class="conversation-box">
                <div class="conversation-header">
                    <div>
                        <h5><?php echo htmlspecialchars($conversation['listing_title']); ?></h5>
                        <p class="mb-0">With: <?php echo htmlspecialchars($conversation['other_user']); ?></p>
                    </div>
                    <div class="badge-container">
                        <?php if ($conversation['unread_count'] > 0): ?>
                            <span class="badge bg-danger unread-badge"><?php echo $conversation['unread_count']; ?> unread</span>
                        <?php endif; ?>
                        <span><?php echo date('d M Y, H:i', strtotime($conversation['sent_at'])); ?></span>
                    </div>
                </div>
                <div class="conversation-footer">
                    <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#conversationModal" data-conversation-id="<?php echo $conversation['conversation_id']; ?>" data-listing-id="<?php echo $conversation['listing_id']; ?>" data-listing-title="<?php echo htmlspecialchars($conversation['listing_title']); ?>" data-other-user="<?php echo htmlspecialchars($conversation['other_user']); ?>" data-listing-owner-id="<?php echo $conversation['listing_owner_id']; ?>">View Conversation</button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal -->
<div class="modal fade" id="conversationModal" tabindex="-1" aria-labelledby="conversationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="conversationModalLabel">Conversation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="messagesContainer" class="d-flex flex-column">
                    <!-- Messages will be loaded here dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <form id="sendMessageForm" class="form-container">
                    <div class="suggestion-buttons">
                        <button type="button" class="btn btn-sm btn-outline-secondary suggestion-button" id="shareAddressButton" style="display: none;">Share my address</button>
                    </div>
                    <textarea name="message" class="form-control mt-3" id="messageText" placeholder="Type your message here..." required></textarea>
                    <input type="hidden" id="conversationId" name="conversation_id">
                    <input type="hidden" id="listingId" name="listing_id">
                </form>
                <button type="button" class="btn btn-outline-success" id="sendMessageButton">Send</button>
                <button type="button" class="btn btn-outline-success d-none" id="sendingAddressButton">Sending my address...</button>
                <div id="sendingIndicator" class="text-success" style="display: none; margin-left: 10px;">Sending...</div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const conversationModal = document.getElementById('conversationModal');

    conversationModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const conversationId = button.getAttribute('data-conversation-id');
        const listingId = button.getAttribute('data-listing-id');
        const listingTitle = button.getAttribute('data-listing-title');
        const otherUser = button.getAttribute('data-other-user');
        const listingOwnerId = button.getAttribute('data-listing-owner-id');

        const modalTitle = conversationModal.querySelector('.modal-title');
        modalTitle.textContent = `Conversation with ${otherUser} about ${listingTitle}`;

        const conversationIdInput = document.getElementById('conversationId');
        conversationIdInput.value = conversationId;

        const listingIdInput = document.getElementById('listingId');
        listingIdInput.value = listingId;

        const messagesContainer = document.getElementById('messagesContainer');
        messagesContainer.innerHTML = '';

        const shareAddressButton = document.getElementById('shareAddressButton');
        if (parseInt(listingOwnerId) === <?php echo $user_id; ?>) {
            shareAddressButton.style.display = 'inline-block';
        } else {
            shareAddressButton.style.display = 'none';
        }

        // Fetch and display the conversation messages
        fetch(`fetch_messages.php?conversation_id=${conversationId}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(message => {
                    const messageBubble = document.createElement('div');
                    messageBubble.classList.add('message-bubble');
                    messageBubble.innerHTML = message.message; // Use innerHTML to render HTML content

                    if (message.sender_id === <?php echo $user_id; ?>) {
                        messageBubble.classList.add('message-bubble-right');
                        // Check if this is the last message sent by the user
                        if (data[data.length - 1].id === message.id) {
                            const seenStatus = document.createElement('div');
                            seenStatus.classList.add('seen-status');
                            seenStatus.textContent = message.read ? 'Seen' : 'Not seen yet';
                            messageBubble.appendChild(seenStatus);
                        }
                    } else {
                        messageBubble.classList.add('message-bubble-left');
                    }

                    messagesContainer.appendChild(messageBubble);
                });

                // Hide unread badge
                button.closest('.conversation-box').querySelector('.unread-badge').style.display = 'none';
            });
    });

    conversationModal.addEventListener('hidden.bs.modal', function() {
        location.reload();
    });

    const sendMessageButton = document.getElementById('sendMessageButton');
    const sendingIndicator = document.getElementById('sendingIndicator');
    const messageText = document.getElementById('messageText');

    sendMessageButton.addEventListener('click', function() {
        if (messageText.value.trim().length < 2) {
            alert('Message must be at least 2 characters long.');
            return;
        }

        const messageTextValue = messageText.value;
        const conversationId = document.getElementById('conversationId').value;

        // Disable and hide the button and show sending indicator
        sendMessageButton.style.display = 'none';
        sendingIndicator.style.display = 'inline';

        fetch('send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                conversation_id: conversationId,
                message: messageTextValue
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const messagesContainer = document.getElementById('messagesContainer');
                const messageBubble = document.createElement('div');
                messageBubble.classList.add('message-bubble', 'message-bubble-right');
                messageBubble.innerHTML = messageTextValue; // Use innerHTML to render HTML content

                const seenStatus = document.createElement('div');
                seenStatus.classList.add('seen-status');
                seenStatus.textContent = 'Not seen yet';
                messageBubble.appendChild(seenStatus);

                messagesContainer.appendChild(messageBubble);
                messageText.value = '';
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            } else {
                alert('Error sending message: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending message.');
        })
        .finally(() => {
            // Re-enable and show the button and hide sending indicator
            sendMessageButton.style.display = 'inline';
            sendingIndicator.style.display = 'none';
        });
    });

    const shareAddressButton = document.getElementById('shareAddressButton');
    const sendingAddressButton = document.getElementById('sendingAddressButton');

    shareAddressButton.addEventListener('click', function() {
        const hasAddress = <?php echo $address ? 'true' : 'false'; ?>;
        if (hasAddress) {
            if (confirm('Are you sure you want to send your address?')) {
                const address = `<?php echo addslashes($address['address_line1']); ?>, <?php echo addslashes($address['address_line2']); ?>, <?php echo addslashes($address['town_city']); ?>, <?php echo addslashes($address['postcode']); ?>, <?php echo addslashes($address['country']); ?>`;
                const addressMessage = `Address for pickup: 
                ${address}
                <div class="map-links">
                    <a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}" class="map-link" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 488 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                        <path d="M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z"/></svg> Google Maps
                    </a>
                    <a href="https://maps.apple.com/?q=${encodeURIComponent(address)}" class="map-link" target="_blank">
                         <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" fill="#fff"><!--! Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
                        <path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/></svg> Apple Maps
                     </a>
                    <a href="https://waze.com/ul?q=${encodeURIComponent(address)}" class="map-link" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="#fff"><!--! Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc. -->
                        <path d="M502.2 201.7C516.7 287.5 471.2 369.6 389 409.8c13 34.1-12.4 70.2-48.3 70.2a51.7 51.7 0 0 1 -51.6-49c-6.4 .2-64.2 0-76.3-.6A51.7 51.7 0 0 1 159 479.9c-33.9-1.4-58-34.8-47-67.9-37.2-13.1-72.5-34.9-99.6-70.8-13-17.3-.5-41.8 20.8-41.8 46.3 0 32.2-54.2 43.2-110.3C94.8 95.2 193.1 32 288.1 32c102.5 0 197.2 70.7 214.1 169.7zM373.5 388.3c42-19.2 81.3-56.7 96.3-102.1 40.5-123.1-64.2-228-181.7-228-83.5 0-170.3 55.4-186.1 136-9.5 48.9 5 131.4-68.8 131.4C58.2 358.6 91.6 378.1 127 389.5c24.7-21.8 63.9-15.5 79.8 14.3 14.2 1 79.2 1.2 87.9 .8a51.7 51.7 0 0 1 78.8-16.4zM205.1 187.1c0-34.7 50.8-34.8 50.8 0s-50.8 34.7-50.8 0zm116.6 0c0-34.7 50.9-34.8 50.9 0s-50.9 34.8-50.9 0zm-122.6 70.7c-3.4-16.9 22.2-22.2 25.6-5.2l.1 .3c4.1 21.4 29.9 44 64.1 43.1 35.7-.9 59.3-22.2 64.1-42.8 4.5-16.1 28.6-10.4 25.5 6-5.2 22.2-31.2 62-91.5 62.9-42.6 0-80.9-27.8-87.9-64.3z"/></svg> Waze
                    </a>
                </div>`;
                sendAddressMessage(addressMessage);
            }
        } else {
            alert('You do not have an address set. You can add your address here: profile.php');
        }
    });

    function sendAddressMessage(messageText) {
        const conversationId = document.getElementById('conversationId').value;

        // Disable and hide the button and show sending indicator
        shareAddressButton.style.display = 'none';
        sendingAddressButton.style.display = 'inline';

        fetch('send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                conversation_id: conversationId,
                message: messageText
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const messagesContainer = document.getElementById('messagesContainer');
                const messageBubble = document.createElement('div');
                messageBubble.classList.add('message-bubble', 'message-bubble-right');
                messageBubble.innerHTML = messageText; // Use innerHTML to render HTML content

                const seenStatus = document.createElement('div');
                seenStatus.classList.add('seen-status');
                seenStatus.textContent = 'Not seen yet';
                messageBubble.appendChild(seenStatus);

                messagesContainer.appendChild(messageBubble);
                document.getElementById('messageText').value = '';
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            } else {
                alert('Error sending message: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending message.');
        })
        .finally(() => {
            // Re-enable and show the button and hide sending indicator
            shareAddressButton.style.display = 'inline';
            sendingAddressButton.style.display = 'none';
        });
    }
});
</script>

</body>
</html>
