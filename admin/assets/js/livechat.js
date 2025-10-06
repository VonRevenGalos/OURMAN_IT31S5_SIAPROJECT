// Admin Live Chat JavaScript

// Global variables
let chatUpdateInterval = null;
let lastMessageId = 0;
let sessionListUpdateInterval = null;

// Initialize live chat
document.addEventListener('DOMContentLoaded', function() {
    initializeLiveChat();
});

function initializeLiveChat() {
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
    if (window.activeSessionId) {
        initializeActiveChat();
    }
    
    // Start session list updates
    startSessionListUpdates();
}

// Initialize active chat
function initializeActiveChat() {
    loadChatMessages();
    
    // Start polling for updates
    startChatUpdates();
    
    // Scroll to bottom
    setTimeout(() => {
        scrollToBottom();
    }, 100);
}

// Load chat messages
function loadChatMessages() {
    if (!window.activeSessionId) return;
    
    const formData = new FormData();
    formData.append('action', 'get_messages');
    formData.append('session_id', window.activeSessionId);
    formData.append('last_message_id', lastMessageId);
    
    fetch('admin_chat_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.messages.length > 0) {
                displayMessages(data.messages);
                
                // Update last message ID
                lastMessageId = Math.max(...data.messages.map(m => m.id));
            }
            
            updateSessionStatus(data.session_status);
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
    
    messages.forEach(message => {
        const messageElement = createMessageElement(message);
        chatMessages.appendChild(messageElement);
    });
    
    // Scroll to bottom
    scrollToBottom();
}

// Create message element
function createMessageElement(message) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${message.sender_type}`;
    
    const messageContent = document.createElement('div');
    messageContent.className = 'message-content';
    messageContent.textContent = message.message;
    messageDiv.appendChild(messageContent);
    
    const messageMeta = document.createElement('div');
    messageMeta.className = 'message-meta';
    
    const senderSpan = document.createElement('span');
    senderSpan.className = 'message-sender';
    if (message.sender_type === 'admin') {
        senderSpan.textContent = (message.first_name || 'Admin') + ' ' + (message.last_name || '');
    } else if (message.sender_type === 'user') {
        senderSpan.textContent = 'Customer';
    } else {
        senderSpan.textContent = 'System';
    }
    
    const timeSpan = document.createElement('span');
    timeSpan.className = 'message-time';
    timeSpan.textContent = formatMessageTime(message.created_at);
    
    messageMeta.appendChild(senderSpan);
    messageMeta.appendChild(timeSpan);
    messageDiv.appendChild(messageMeta);
    
    return messageDiv;
}

// Format message time
function formatMessageTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Update session status
function updateSessionStatus(status) {
    if (status !== window.sessionStatus) {
        window.sessionStatus = status;
        
        // Reload page to update UI
        if (status === 'closed') {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    }
}

// Send message
function sendMessage() {
    const messageInput = document.getElementById('messageInput');
    if (!messageInput || !window.activeSessionId) return;
    
    const message = messageInput.value.trim();
    if (!message) return;
    
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('session_id', window.activeSessionId);
    formData.append('message', message);
    
    // Clear input immediately
    messageInput.value = '';
    
    fetch('admin_chat_actions.php', {
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

// Accept chat
function acceptChat(sessionId) {
    const formData = new FormData();
    formData.append('action', 'accept_chat');
    formData.append('session_id', sessionId);
    
    fetch('admin_chat_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to update UI
            window.location.reload();
        } else {
            alert(data.message || 'Failed to accept chat');
        }
    })
    .catch(error => {
        console.error('Error accepting chat:', error);
        alert('Error accepting chat. Please try again.');
    });
}

// Decline chat
function declineChat(sessionId) {
    if (!confirm('Are you sure you want to decline this chat?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'decline_chat');
    formData.append('session_id', sessionId);
    
    fetch('admin_chat_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to update UI
            window.location.reload();
        } else {
            alert(data.message || 'Failed to decline chat');
        }
    })
    .catch(error => {
        console.error('Error declining chat:', error);
        alert('Error declining chat. Please try again.');
    });
}

// End chat
function endChat(sessionId) {
    if (!confirm('Are you sure you want to end this chat?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'end_chat');
    formData.append('session_id', sessionId);
    
    fetch('admin_chat_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to update UI
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

// Open chat session
function openChatSession(sessionId) {
    window.location.href = `livechat.php?session=${sessionId}`;
}

// Refresh chat list
function refreshChatList() {
    window.location.reload();
}

// Scroll to bottom of chat
function scrollToBottom() {
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
}

// Start chat updates
function startChatUpdates() {
    if (chatUpdateInterval) {
        clearInterval(chatUpdateInterval);
    }
    
    // Update every 3 seconds
    chatUpdateInterval = setInterval(() => {
        if (window.sessionStatus !== 'closed') {
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

// Start session list updates
function startSessionListUpdates() {
    if (sessionListUpdateInterval) {
        clearInterval(sessionListUpdateInterval);
    }
    
    // Update session list every 10 seconds
    sessionListUpdateInterval = setInterval(() => {
        updateSessionList();
    }, 10000);
}

// Update session list
function updateSessionList() {
    const formData = new FormData();
    formData.append('action', 'get_session_list');
    
    fetch('admin_chat_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateSessionListUI(data.sessions);
        }
    })
    .catch(error => {
        console.error('Error updating session list:', error);
    });
}

// Update session list UI
function updateSessionListUI(sessions) {
    // Update badge counts
    const pendingCount = sessions.filter(s => s.status === 'pending').length;
    const activeCount = sessions.filter(s => s.status === 'active').length;
    
    const pendingBadge = document.querySelector('.badge.bg-warning');
    const activeBadge = document.querySelector('.badge.bg-success');
    
    if (pendingBadge) {
        pendingBadge.textContent = `${pendingCount} Pending`;
    }
    
    if (activeBadge) {
        activeBadge.textContent = `${activeCount} Active`;
    }
    
    // Update unread counts
    sessions.forEach(session => {
        const sessionItem = document.querySelector(`[onclick="openChatSession(${session.id})"]`);
        if (sessionItem) {
            const unreadBadge = sessionItem.querySelector('.unread-badge');
            if (session.unread_count > 0) {
                if (!unreadBadge) {
                    const badge = document.createElement('div');
                    badge.className = 'unread-badge';
                    badge.textContent = session.unread_count;
                    sessionItem.querySelector('.session-meta').appendChild(badge);
                } else {
                    unreadBadge.textContent = session.unread_count;
                }
            } else if (unreadBadge) {
                unreadBadge.remove();
            }
        }
    });
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    stopChatUpdates();
    if (sessionListUpdateInterval) {
        clearInterval(sessionListUpdateInterval);
    }
});

// Export functions for global access
window.sendMessage = sendMessage;
window.acceptChat = acceptChat;
window.declineChat = declineChat;
window.endChat = endChat;
window.openChatSession = openChatSession;
window.refreshChatList = refreshChatList;
