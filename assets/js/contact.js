// Contact Page JavaScript

// Global variables
let chatUpdateInterval = null;
let lastMessageId = 0;

// Initialize contact page
document.addEventListener('DOMContentLoaded', function() {
    initializeContactPage();
});

function initializeContactPage() {
    // Initialize start chat form
    const startChatForm = document.getElementById('startChatForm');
    if (startChatForm) {
        startChatForm.addEventListener('submit', handleStartChat);
    }
    
    // Initialize message input
    const messageInput = document.getElementById('messageInput');
    if (messageInput) {
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }
    
    // Initialize active chat if exists
    if (window.chatSessionId) {
        initializeActiveChat();
    }
}

// Handle start chat form submission
function handleStartChat(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    // Add the required action parameter
    formData.append('action', 'start_chat');

    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    // Show loading state
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Starting Chat...';
    submitBtn.disabled = true;

    fetch('chat_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to show active chat
            window.location.reload();
        } else {
            alert(data.message || 'Failed to start chat. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error starting chat:', error);
        alert('Error starting chat. Please try again.');
    })
    .finally(() => {
        // Reset button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Initialize active chat
function initializeActiveChat() {
    loadChatMessages();
    
    // Show input if chat is active
    if (window.chatStatus === 'active') {
        const inputContainer = document.getElementById('chatInputContainer');
        if (inputContainer) {
            inputContainer.style.display = 'block';
        }
    }
    
    // Start polling for updates
    startChatUpdates();
}

// Load chat messages
function loadChatMessages() {
    if (!window.chatSessionId) return;
    
    const formData = new FormData();
    formData.append('action', 'get_messages');
    formData.append('session_id', window.chatSessionId);
    formData.append('last_message_id', lastMessageId);
    
    fetch('chat_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayMessages(data.messages);
            updateChatStatus(data.session_status);
            
            // Update last message ID
            if (data.messages.length > 0) {
                lastMessageId = Math.max(...data.messages.map(m => m.id));
            }
        }
    })
    .catch(error => {
        console.error('Error loading messages:', error);
    });
}

// Display messages in chat
function displayMessages(messages) {
    const chatMessages = document.getElementById('chatMessages');
    if (!chatMessages) return;
    
    // If this is the first load, clear existing messages
    if (lastMessageId === 0) {
        chatMessages.innerHTML = '';
    }
    
    messages.forEach(message => {
        const messageElement = createMessageElement(message);
        chatMessages.appendChild(messageElement);
    });
    
    // Scroll to bottom
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Create message element
function createMessageElement(message) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${message.sender_type}`;
    
    const messageContent = document.createElement('div');
    messageContent.textContent = message.message;
    messageDiv.appendChild(messageContent);
    
    const messageTime = document.createElement('div');
    messageTime.className = 'message-time';
    messageTime.textContent = formatMessageTime(message.created_at);
    messageDiv.appendChild(messageTime);
    
    return messageDiv;
}

// Format message time
function formatMessageTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 60000) { // Less than 1 minute
        return 'Just now';
    } else if (diff < 3600000) { // Less than 1 hour
        const minutes = Math.floor(diff / 60000);
        return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    } else if (date.toDateString() === now.toDateString()) { // Same day
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } else {
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
}

// Update chat status
function updateChatStatus(status) {
    if (status !== window.chatStatus) {
        window.chatStatus = status;
        
        // Update status indicator
        const statusIndicator = document.querySelector('.status-indicator');
        const statusText = document.querySelector('.status-text');
        
        if (statusIndicator && statusText) {
            statusIndicator.className = `status-indicator status-${status}`;
            
            switch(status) {
                case 'pending':
                    statusText.textContent = 'Waiting for support...';
                    break;
                case 'active':
                    statusText.textContent = 'Connected with support';
                    // Show input container
                    const inputContainer = document.getElementById('chatInputContainer');
                    if (inputContainer) {
                        inputContainer.style.display = 'block';
                    }
                    break;
                case 'closed':
                    statusText.textContent = 'Chat ended';
                    // Hide input container
                    const inputContainer2 = document.getElementById('chatInputContainer');
                    if (inputContainer2) {
                        inputContainer2.style.display = 'none';
                    }
                    stopChatUpdates();
                    break;
            }
        }
    }
}

// Send message
function sendMessage() {
    const messageInput = document.getElementById('messageInput');
    if (!messageInput || !window.chatSessionId) return;
    
    const message = messageInput.value.trim();
    if (!message) return;
    
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('session_id', window.chatSessionId);
    formData.append('message', message);
    
    // Clear input immediately
    messageInput.value = '';
    
    fetch('chat_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Message will be loaded in next update
            loadChatMessages();
        } else {
            alert(data.message || 'Failed to send message');
            // Restore message to input
            messageInput.value = message;
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
        alert('Error sending message. Please try again.');
        // Restore message to input
        messageInput.value = message;
    });
}

// End chat
function endChat() {
    if (!window.chatSessionId) return;
    
    if (!confirm('Are you sure you want to end this chat?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'end_chat');
    formData.append('session_id', window.chatSessionId);
    
    fetch('chat_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to show start chat form
            window.location.reload();
        } else {
            alert(data.message || 'Failed to end chat');
        }
    })
    .catch(error => {
        console.error('Error ending chat:', error);
        alert('Error ending chat. Please try again.');
    });
}

// Start chat updates
function startChatUpdates() {
    if (chatUpdateInterval) {
        clearInterval(chatUpdateInterval);
    }
    
    // Update every 3 seconds
    chatUpdateInterval = setInterval(() => {
        if (window.chatStatus !== 'closed') {
            loadChatMessages();
        }
    }, 3000);
}

// Stop chat updates
function stopChatUpdates() {
    if (chatUpdateInterval) {
        clearInterval(chatUpdateInterval);
        chatUpdateInterval = null;
    }
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    stopChatUpdates();
});

// Export functions for global access
window.sendMessage = sendMessage;
window.endChat = endChat;
