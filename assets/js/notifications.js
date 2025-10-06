// Notifications Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize notifications page
    initializeNotifications();
    
    // Update notification count in navbar
    updateNotificationCount();
});

function initializeNotifications() {
    console.log('Notifications page initialized');
    
    // Add click handlers for notification items
    const notificationItems = document.querySelectorAll('.notification-item.unread');
    notificationItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Don't trigger if clicking on buttons
            if (e.target.closest('.notification-actions')) {
                return;
            }
            
            const notificationId = this.dataset.notificationId;
            if (notificationId) {
                markAsRead(notificationId);
            }
        });
    });
}

// Mark single notification as read
function markAsRead(notificationId) {
    const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
    if (!notificationItem) return;
    
    // Add loading state
    notificationItem.classList.add('loading');
    
    const formData = new FormData();
    formData.append('action', 'mark_as_read');
    formData.append('notification_id', notificationId);
    
    fetch('notification.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            notificationItem.classList.remove('unread', 'loading');
            notificationItem.classList.add('read');
            
            // Remove unread indicator
            const unreadIndicator = notificationItem.querySelector('.unread-indicator');
            if (unreadIndicator) {
                unreadIndicator.remove();
            }
            
            // Update mark as read button
            const markReadBtn = notificationItem.querySelector('[onclick*="markAsRead"]');
            if (markReadBtn) {
                markReadBtn.remove();
            }
            
            // Update notification count
            updateNotificationCount(data.unread_count);
            updateNotificationBadge(data.unread_count);
            
            // Update page header badge
            updatePageHeaderBadge(data.unread_count);
            
            showNotification('Notification marked as read', 'success');
        } else {
            showNotification(data.message || 'Failed to mark notification as read', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    })
    .finally(() => {
        notificationItem.classList.remove('loading');
    });
}

// Mark all notifications as read
function markAllAsRead() {
    const unreadItems = document.querySelectorAll('.notification-item.unread');
    if (unreadItems.length === 0) {
        showNotification('No unread notifications', 'info');
        return;
    }
    
    if (!confirm(`Mark all ${unreadItems.length} notifications as read?`)) {
        return;
    }
    
    // Add loading state to all unread items
    unreadItems.forEach(item => item.classList.add('loading'));
    
    const formData = new FormData();
    formData.append('action', 'mark_all_read');
    
    fetch('notification.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update all unread items
            unreadItems.forEach(item => {
                item.classList.remove('unread', 'loading');
                item.classList.add('read');
                
                // Remove unread indicator
                const unreadIndicator = item.querySelector('.unread-indicator');
                if (unreadIndicator) {
                    unreadIndicator.remove();
                }
                
                // Remove mark as read button
                const markReadBtn = item.querySelector('[onclick*="markAsRead"]');
                if (markReadBtn) {
                    markReadBtn.remove();
                }
            });
            
            // Update notification count
            updateNotificationCount(0);
            updateNotificationBadge(0);
            
            // Update page header
            updatePageHeaderBadge(0);
            
            // Hide mark all read button
            const markAllBtn = document.querySelector('[onclick="markAllAsRead()"]');
            if (markAllBtn) {
                markAllBtn.style.display = 'none';
            }
            
            showNotification('All notifications marked as read', 'success');
        } else {
            showNotification(data.message || 'Failed to mark notifications as read', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    })
    .finally(() => {
        unreadItems.forEach(item => item.classList.remove('loading'));
    });
}

// Delete notification
function deleteNotification(notificationId) {
    if (!confirm('Are you sure you want to delete this notification?')) {
        return;
    }
    
    const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
    if (!notificationItem) return;
    
    // Add loading state
    notificationItem.classList.add('loading');
    
    const formData = new FormData();
    formData.append('action', 'delete_notification');
    formData.append('notification_id', notificationId);
    
    fetch('notification.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove notification item with animation
            notificationItem.style.transform = 'translateX(-100%)';
            notificationItem.style.opacity = '0';
            
            setTimeout(() => {
                notificationItem.remove();
                
                // Check if no notifications left
                const remainingNotifications = document.querySelectorAll('.notification-item');
                if (remainingNotifications.length === 0) {
                    location.reload(); // Reload to show empty state
                }
            }, 300);
            
            // Update notification count
            updateNotificationCount(data.unread_count);
            updateNotificationBadge(data.unread_count);
            
            showNotification('Notification deleted', 'success');
        } else {
            showNotification(data.message || 'Failed to delete notification', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    })
    .finally(() => {
        notificationItem.classList.remove('loading');
    });
}

// View order details - redirects to myorders.php
function viewOrderDetails(orderReference) {
    console.log('viewOrderDetails called with:', orderReference);

    // Extract order ID from reference
    let orderId = orderReference;
    if (orderReference.includes('Order #')) {
        orderId = orderReference.replace('Order #', '').trim();
    }

    console.log('Extracted order ID:', orderId);

    // Redirect to myorders.php page
    if (orderId && orderId !== 'General') {
        showNotification(`Redirecting to My Orders page...`, 'info');

        // Redirect to myorders.php
        window.location.href = 'myorders.php';
    } else {
        showNotification('Order reference not found', 'error');
    }
}

// Review product - redirects to product page with review focus
function reviewProduct(productId) {
    console.log('reviewProduct called with product ID:', productId);

    if (productId) {
        showNotification('Redirecting to product page for review...', 'info');

        // Redirect to product page with review parameter
        window.location.href = `product.php?id=${productId}&review=1`;
    } else {
        showNotification('Product ID not found', 'error');
    }
}

// Update page header badge
function updatePageHeaderBadge(count) {
    const headerBadge = document.querySelector('.page-title .badge');
    if (count > 0) {
        if (headerBadge) {
            headerBadge.textContent = `${count} unread`;
        } else {
            const pageTitle = document.querySelector('.page-title');
            if (pageTitle) {
                const badge = document.createElement('span');
                badge.className = 'badge bg-danger ms-2';
                badge.textContent = `${count} unread`;
                pageTitle.appendChild(badge);
            }
        }
    } else {
        if (headerBadge) {
            headerBadge.remove();
        }
    }
}

// Update notification count in navbar (uses global function)
function updateNotificationCount(count = null) {
    if (count === null) {
        // Fetch current count
        fetch('get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(data.count);
            }
        })
        .catch(error => {
            console.error('Error fetching notification count:', error);
        });
    } else {
        updateNotificationBadge(count);
    }
}

// Update notification badge in navbar
function updateNotificationBadge(count) {
    const notificationBadge = document.querySelector('.notification-badge');
    if (notificationBadge) {
        if (count > 0) {
            notificationBadge.textContent = count;
            notificationBadge.style.display = 'flex';
        } else {
            notificationBadge.style.display = 'none';
        }
    }
}

// Show notification message (local function for notifications page)
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}
