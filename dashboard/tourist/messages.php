<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get selected guide if provided
$guide_id = isset($_GET['guide_id']) ? filter_var($_GET['guide_id'], FILTER_SANITIZE_NUMBER_INT) : null;

// Get all conversations
$stmt = $conn->prepare("
    SELECT DISTINCT 
        u.user_id,
        u.name,
        u.role,
        (SELECT content 
         FROM messages 
         WHERE (sender_id = u.user_id AND receiver_id = ?) 
            OR (sender_id = ? AND receiver_id = u.user_id)
         ORDER BY timestamp DESC 
         LIMIT 1) as last_message,
        (SELECT timestamp 
         FROM messages 
         WHERE (sender_id = u.user_id AND receiver_id = ?) 
            OR (sender_id = ? AND receiver_id = u.user_id)
         ORDER BY timestamp DESC 
         LIMIT 1) as last_message_time,
        (SELECT COUNT(*) 
         FROM messages 
         WHERE sender_id = u.user_id 
         AND receiver_id = ? 
         AND read_status = 'unread') as unread_count
    FROM users u
    JOIN messages m ON u.user_id = m.sender_id OR u.user_id = m.receiver_id
    WHERE (m.sender_id = ? OR m.receiver_id = ?)
    AND u.user_id != ?
    GROUP BY u.user_id
    ORDER BY last_message_time DESC
");
$stmt->execute([
    $_SESSION['user_id'],
    $_SESSION['user_id'],
    $_SESSION['user_id'],
    $_SESSION['user_id'],
    $_SESSION['user_id'],
    $_SESSION['user_id'],
    $_SESSION['user_id'],
    $_SESSION['user_id']
]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get messages for selected conversation
if ($guide_id) {
    $stmt = $conn->prepare("
        SELECT m.*, u.name as sender_name 
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE (sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?)
        ORDER BY timestamp ASC
    ");
    $stmt->execute([$_SESSION['user_id'], $guide_id, $guide_id, $_SESSION['user_id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark messages as read
    $stmt = $conn->prepare("
        UPDATE messages 
        SET read_status = 'read' 
        WHERE sender_id = ? AND receiver_id = ? AND read_status = 'unread'
    ");
    $stmt->execute([$guide_id, $_SESSION['user_id']]);
}

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="flex h-[calc(100vh-200px)]">
                <!-- Conversations List -->
                <div class="w-1/3 border-r">
                    <div class="p-4 border-b">
                        <h2 class="text-lg font-semibold">Messages</h2>
                    </div>
                    <div class="overflow-y-auto h-full">
                        <?php foreach ($conversations as $conv): ?>
                            <a href="?guide_id=<?= $conv['user_id'] ?>"
                                class="block p-4 hover:bg-gray-50 <?= $guide_id == $conv['user_id'] ? 'bg-blue-50' : '' ?>">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-medium"><?= htmlspecialchars($conv['name']) ?></h3>
                                        <p class="text-sm text-gray-500">
                                            <?= htmlspecialchars(substr($conv['last_message'], 0, 50)) ?>...
                                        </p>
                                    </div>
                                    <?php if ($conv['unread_count'] > 0): ?>
                                        <span class="bg-blue-500 text-white text-xs px-2 py-1 rounded-full">
                                            <?= $conv['unread_count'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Messages Area -->
                <div class="flex-1 flex flex-col">
                    <?php if ($guide_id): ?>
                        <!-- Messages Header -->
                        <div class="p-4 border-b">
                            <h2 class="font-semibold">
                                <?= htmlspecialchars($conversations[array_search($guide_id, array_column($conversations, 'user_id'))]['name']) ?>
                            </h2>
                        </div>

                        <!-- Messages List -->
                        <div class="flex-1 overflow-y-auto p-4 space-y-4">
                            <?php foreach ($messages as $message): ?>
                                <div class="flex <?= $message['sender_id'] == $_SESSION['user_id'] ? 'justify-end' : 'justify-start' ?>">
                                    <div class="max-w-sm <?= $message['sender_id'] == $_SESSION['user_id'] ? 'bg-blue-500 text-white' : 'bg-gray-200' ?> rounded-lg px-4 py-2">
                                        <p><?= htmlspecialchars($message['content']) ?></p>
                                        <p class="text-xs <?= $message['sender_id'] == $_SESSION['user_id'] ? 'text-blue-100' : 'text-gray-500' ?> mt-1">
                                            <?= date('H:i', strtotime($message['timestamp'])) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Message Input -->
                        <div class="p-4 border-t">
                            <form id="messageForm" class="flex space-x-4">
                                <input type="hidden" name="receiver_id" value="<?= $guide_id ?>">
                                <input type="text"
                                    name="message"
                                    class="flex-1 border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Type your message...">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600">
                                    Send
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="flex-1 flex items-center justify-center text-gray-500">
                            Select a conversation to start messaging
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('messageForm')?.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const form = e.target;
                    const formData = new FormData(form);

                    try {
                        const response = await fetch('send-message.php', {
                            method: 'POST',
                            body: formData
                        });

                        if (response.ok) {
                            form.reset();
                            // Reload messages
                            window.location.reload();
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }

                    // Add this to your messages.php file, just before the closing 
</script> tag

let lastMessageId = 0;
let typingTimeout;

// Function to poll for new messages
function pollMessages() {
if (!selectedRecipientId) return;

fetch(`check-messages.php?recipient_id=${selectedRecipientId}&last_message_id=${lastMessageId}`)
.then(response => response.json())
.then(data => {
if (data.messages && data.messages.length > 0) {
data.messages.forEach(message => {
appendMessage(message);
lastMessageId = Math.max(lastMessageId, message.message_id);
});
scrollToBottom();
}

// Update typing indicator
updateTypingIndicator(data.is_typing);
});
}

// Start polling when page loads
setInterval(pollMessages, 3000);

// Handle typing status
const messageInput = document.querySelector('input[name="message"]');
messageInput?.addEventListener('input', () => {
clearTimeout(typingTimeout);
updateTypingStatus(true);

typingTimeout = setTimeout(() => {
updateTypingStatus(false);
}, 2000);
});

function updateTypingStatus(isTyping) {
if (!selectedRecipientId) return;

const formData = new FormData();
formData.append('recipient_id', selectedRecipientId);
formData.append('is_typing', isTyping);

fetch('update-typing.php', {
method: 'POST',
body: formData
});
}

function updateTypingIndicator(isTyping) {
const typingIndicator = document.getElementById('typingIndicator');
if (isTyping) {
typingIndicator.classList.remove('hidden');
} else {
typingIndicator.classList.add('hidden');
}
}

// File attachment handling
const fileInput = document.getElementById('fileAttachment');
fileInput?.addEventListener('change', () => {
const fileLabel = document.getElementById('fileLabel');
fileLabel.textContent = fileInput.files[0]?.name || 'Attach File';
});

// Modify the message form submission to handle files
document.getElementById('messageForm')?.addEventListener('submit', async function(e) {
e.preventDefault();
const formData = new FormData(this);

try {
const response = await fetch('send-message.php', {
method: 'POST',
body: formData
});

if (response.ok) {
this.reset();
document.getElementById('fileLabel').textContent = 'Attach File';
await pollMessages(); // Immediately check for new messages
}
} catch (error) {
console.error('Error:', error);
}
});

// Function to handle starting a new conversation
function startNewConversation(userId) {
selectedRecipientId = userId;
document.getElementById('messagesList').innerHTML = '';
document.getElementById('messagingArea').classList.remove('hidden');
document.querySelector('input[name="receiver_id"]').value = userId;
}
});
</script>

<?php include '../../includes/footer.php'; ?>