// Global Cart Functions
window.addToCart = function(productId, quantity = 1, size = null, buttonElement = null, fromFavorites = false) {
    console.log('addToCart called with:', {productId, quantity, size, fromFavorites});

    // Check if user is logged in
    if (!isUserLoggedIn()) {
        console.log('User not logged in');
        showNotification('Please log in to add items to cart', 'error');
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 1500);
        return;
    }

    console.log('User is logged in, proceeding with add to cart');

    // Find the button if not provided
    const button = buttonElement || document.querySelector(`button[onclick*="addToCart(${productId})"]`);
    const originalText = button ? button.innerHTML : '';

    if (button) {
        showLoadingState(button, originalText);
    }

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    if (size) {
        formData.append('size', size);
    }
    if (fromFavorites) {
        formData.append('from_favorites', 'true');
    }
    
    fetch('add_to_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            showNotification(data.message, 'success');
            console.log('Updating cart count to:', data.cart_count);
            updateCartCountInNavbar(data.cart_count);

            // Handle favorites-specific logic
            if (fromFavorites && data.from_favorites) {
                console.log('Handling favorites-specific logic');

                // Update favorites count in navbar
                if (typeof updateFavoritesCountInNavbar === 'function') {
                    updateFavoritesCountInNavbar(data.favorites_count);
                }

                // If on favorites page, remove the product card
                const productCard = document.querySelector(`[data-product-id="${productId}"]`);
                if (productCard) {
                    productCard.style.transition = 'all 0.3s ease';
                    productCard.style.opacity = '0';
                    productCard.style.transform = 'scale(0.8)';

                    setTimeout(() => {
                        productCard.remove();

                        // Update favorites count display if function exists
                        if (typeof updateFavoritesCount === 'function') {
                            updateFavoritesCount(data.favorites_count);
                        }

                        // Check empty state if function exists
                        if (typeof checkEmptyState === 'function') {
                            checkEmptyState();
                        }

                        // Update selected count if function exists
                        if (typeof updateSelectedCount === 'function') {
                            updateSelectedCount();
                        }
                    }, 300);
                }

                // Update button state for favorites
                if (button) {
                    button.innerHTML = '<i class="fas fa-check"></i> Moved to Cart';
                    button.classList.add('added');
                    button.disabled = true;
                }
            } else {
                // Regular add to cart behavior
                if (button) {
                    button.innerHTML = '<i class="fas fa-check"></i> Added to Cart';
                    button.classList.add('added');
                    setTimeout(() => {
                        if (button && originalText) {
                            button.innerHTML = originalText;
                            button.classList.remove('added');
                        }
                    }, 2000);
                }
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    })
    .finally(() => {
        if (button) {
            hideLoadingState(button);
        }
    });
};

// Show loading state
function showLoadingState(element, originalText) {
    if (element) {
        element.disabled = true;
        element.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i>Adding...`;
        element.setAttribute('data-original-text', originalText);
    }
}

// Hide loading state
function hideLoadingState(element) {
    if (element) {
        element.disabled = false;
        const originalText = element.getAttribute('data-original-text');
        if (originalText) {
            element.innerHTML = originalText;
            element.removeAttribute('data-original-text');
        }
    }
}

// Update cart count in navbar
function updateCartCountInNavbar(count) {
    const cartIcon = document.querySelector('a[href="cart.php"] .cart-count');
    if (cartIcon) {
        if (count > 0) {
            cartIcon.textContent = count;
            cartIcon.style.display = 'flex';
        } else {
            cartIcon.style.display = 'none';
        }
    } else {
        console.error('Cart count element not found. Looking for: a[href="cart.php"] .cart-count');
    }
}

// Check if user is logged in
function isUserLoggedIn() {
    // Check if the user is logged in by looking for the cart link in navbar
    // This is more reliable than checking for existence of cart.php link
    const userDropdown = document.querySelector('.dropdown-menu');
    const logoutLink = document.querySelector('a[href="logout.php"]');
    return logoutLink !== null || userDropdown !== null;
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

// Global function to update cart count
window.updateCartCount = function() {
    if (isUserLoggedIn()) {
        fetch('get_cart_count.php')
            .then(response => response.json())
            .then(data => {
                updateCartCountInNavbar(data.count);
            })
            .catch(error => {
                console.error('Error loading cart count:', error);
            });
    }
};

// Initialize cart count on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
});
