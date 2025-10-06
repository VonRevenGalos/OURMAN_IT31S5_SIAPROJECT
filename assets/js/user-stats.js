// User Stats JavaScript
function updateUserStats() {
    // Update cart count
    fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            const cartStats = document.getElementById('cart-stats');
            if (cartStats) {
                cartStats.textContent = data.count;
                animateNumber(cartStats, data.count);
            }

            // Update navbar cart badge
            if (typeof updateCartCountInNavbar === 'function') {
                updateCartCountInNavbar(data.count);
            }
        })
        .catch(error => {
            console.error('Error loading cart count:', error);
        });

    // Update favorites count
    fetch('get_favorites_count.php')
        .then(response => response.json())
        .then(data => {
            const favoritesStats = document.getElementById('favorites-stats');
            if (favoritesStats) {
                favoritesStats.textContent = data.count;
                animateNumber(favoritesStats, data.count);
            }

            // Update navbar favorites badge
            if (typeof updateFavoritesCountInNavbar === 'function') {
                updateFavoritesCountInNavbar(data.count);
            }
        })
        .catch(error => {
            console.error('Error loading favorites count:', error);
        });

    // Update notifications count
    fetch('get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notificationsStats = document.getElementById('notifications-stats');
                if (notificationsStats) {
                    notificationsStats.textContent = data.count;
                    animateNumber(notificationsStats, data.count);
                }

                // Update navbar notification badge
                if (typeof updateNotificationCountInNavbar === 'function') {
                    updateNotificationCountInNavbar(data.count);
                }
            }
        })
        .catch(error => {
            console.error('Error loading notifications count:', error);
        });
}

// Animate number change
function animateNumber(element, newValue) {
    const currentValue = parseInt(element.textContent) || 0;
    
    if (currentValue !== newValue) {
        element.style.transform = 'scale(1.2)';
        element.style.color = '#007bff';
        
        setTimeout(() => {
            element.style.transform = 'scale(1)';
            element.style.color = '';
        }, 300);
    }
}

// Initialize stats on page load
document.addEventListener('DOMContentLoaded', function() {
    // Update stats immediately
    updateUserStats();
    
    // Update stats every 30 seconds
    setInterval(updateUserStats, 30000);
    
    // Listen for storage events (when user adds items in another tab)
    window.addEventListener('storage', function(e) {
        if (e.key === 'cart_updated' || e.key === 'favorites_updated' || e.key === 'notifications_updated') {
            updateUserStats();
        }
    });

    // Listen for custom events from other scripts
    document.addEventListener('cartUpdated', updateUserStats);
    document.addEventListener('favoritesUpdated', updateUserStats);
    document.addEventListener('notificationsUpdated', updateUserStats);
});
