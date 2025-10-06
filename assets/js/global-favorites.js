// Global Favorites Functions
window.addToFavorites = function(productId) {
    // Check if user is logged in
    if (!isUserLoggedIn()) {
        showNotification('Please log in to add favorites', 'error');
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 1500);
        return;
    }
    
    fetch('add_to_favorites.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            updateFavoritesCountInNavbar(data.favorites_count);
            
            // Update heart icon if on product page
            const heartBtn = document.querySelector(`[onclick="addToFavorites(${productId})"]`);
            if (heartBtn) {
                heartBtn.innerHTML = '<i class="fas fa-heart"></i> Favorited';
                heartBtn.classList.add('favorited');
                heartBtn.disabled = true;
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
};

// Update favorites count in navbar
function updateFavoritesCountInNavbar(count) {
    const favoritesIcon = document.querySelector('a[href="favorites.php"] .favorites-count');
    if (favoritesIcon) {
        if (count > 0) {
            favoritesIcon.textContent = count;
            favoritesIcon.style.display = 'flex';
        } else {
            favoritesIcon.style.display = 'none';
        }
    }
}

// Check if user is logged in (you'll need to implement this based on your session system)
function isUserLoggedIn() {
    // This should check your session or make an AJAX call to verify login status
    // For now, we'll check if the favorites link exists in navbar
    return document.querySelector('a[href="favorites.php"]') !== null;
}

// Show notification function
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} notification-toast`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
        ${message}
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Hide notification
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Global function to update favorites count
window.updateFavoritesCount = function() {
    if (isUserLoggedIn()) {
        fetch('get_favorites_count.php')
            .then(response => response.json())
            .then(data => {
                updateFavoritesCountInNavbar(data.count);
            })
            .catch(error => {
                console.error('Error loading favorites count:', error);
            });
    }
};

// Initialize favorites count on page load
document.addEventListener('DOMContentLoaded', function() {
    updateFavoritesCount();
});