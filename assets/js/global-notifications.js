// Global Notifications Functions
document.addEventListener('DOMContentLoaded', function() {
    // Initialize notification count on page load
    if (isUserLoggedIn()) {
        updateNotificationCountInNavbar();
    }
});

// Update notification count in navbar
function updateNotificationCountInNavbar(count = null) {
    if (count !== null) {
        // Use provided count
        updateNotificationBadgeDisplay(count);
    } else {
        // Fetch current count from server
        fetch('get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadgeDisplay(data.count);
            }
        })
        .catch(error => {
            console.error('Error fetching notification count:', error);
        });
    }
}

// Update the notification badge display
function updateNotificationBadgeDisplay(count) {
    const notificationBadge = document.querySelector('a[href="notification.php"] .notification-badge');
    if (notificationBadge) {
        if (count > 0) {
            notificationBadge.textContent = count;
            notificationBadge.style.display = 'flex';
        } else {
            notificationBadge.style.display = 'none';
        }
    }

    // Update user stats page if present
    const notificationsStats = document.getElementById('notifications-stats');
    if (notificationsStats) {
        notificationsStats.textContent = count;
        // Trigger animation if the count changed
        if (typeof animateNumber === 'function') {
            animateNumber(notificationsStats, count);
        }
    }

    // Trigger custom event for other scripts
    document.dispatchEvent(new CustomEvent('notificationsUpdated', { detail: { count: count } }));

    // Update localStorage to sync across tabs
    localStorage.setItem('notifications_updated', Date.now());
}

// Create notification (for admin status updates)
function createNotification(userId, type, message) {
    const formData = new FormData();
    formData.append('action', 'create_notification');
    formData.append('user_id', userId);
    formData.append('type', type);
    formData.append('message', message);
    
    return fetch('create_notification.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update notification count if on same user's session
            updateNotificationCountInNavbar();
        }
        return data;
    })
    .catch(error => {
        console.error('Error creating notification:', error);
        return { success: false, message: 'Failed to create notification' };
    });
}

// Check if user is logged in (reuse from other global files)
function isUserLoggedIn() {
    // Check if user info exists in the page
    const userIcon = document.querySelector('a[href="user.php"]');
    return userIcon !== null;
}

// Notification helper functions
window.NotificationHelper = {
    // Update count
    updateCount: updateNotificationCountInNavbar,
    
    // Create notification
    create: createNotification,
    
    // Mark as read
    markAsRead: function(notificationId) {
        const formData = new FormData();
        formData.append('action', 'mark_as_read');
        formData.append('notification_id', notificationId);
        
        return fetch('notification.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationCountInNavbar(data.unread_count);
            }
            return data;
        });
    },
    
    // Get count
    getCount: function() {
        return fetch('get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                return data.count;
            }
            return 0;
        })
        .catch(() => 0);
    }
};

// Make functions globally available
window.updateNotificationCountInNavbar = updateNotificationCountInNavbar;
window.createNotification = createNotification;
