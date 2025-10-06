// Favorites Page JavaScript
let selectedItems = [];
let isLoading = false;

// Show loading state
function showLoadingState(element, originalText) {
    if (element) {
        element.disabled = true;
        element.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i>Loading...`;
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

// Remove from favorites function
function removeFromFavorites(productId) {
    if (isLoading) return;

    if (confirm('Are you sure you want to remove this item from your favorites?')) {
        isLoading = true;
        const card = document.querySelector(`[data-product-id="${productId}"]`);

        // Add loading state to card
        if (card) {
            card.style.opacity = '0.6';
            card.style.pointerEvents = 'none';
        }

        fetch('favorites.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=remove_favorite&product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the card from DOM with animation
                if (card) {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.8)';

                    setTimeout(() => {
                        card.remove();
                        updateFavoritesCount(data.remaining_count);
                        updateNavbarFavoritesCount(data.remaining_count);
                        checkEmptyState();
                        updateSelectedCount();
                    }, 300);
                }
                showNotification('Removed from favorites!', 'success');
            } else {
                // Restore card state on error
                if (card) {
                    card.style.opacity = '1';
                    card.style.pointerEvents = 'auto';
                }
                showNotification(data.message || 'Failed to remove from favorites', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Restore card state on error
            if (card) {
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
            }
            showNotification('An error occurred', 'error');
        })
        .finally(() => {
            isLoading = false;
        });
    }
}

// Clear all favorites function
function clearAllFavorites() {
    if (isLoading) return;

    if (confirm('Are you sure you want to remove all items from your favorites? This action cannot be undone.')) {
        isLoading = true;
        const clearBtn = document.querySelector('button[onclick="clearAllFavorites()"]');
        const originalText = clearBtn ? clearBtn.innerHTML : '';

        showLoadingState(clearBtn, originalText);

        // Add loading state to all cards
        const cards = document.querySelectorAll('.favorite-card');
        cards.forEach(card => {
            card.style.opacity = '0.6';
            card.style.pointerEvents = 'none';
        });

        fetch('favorites.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clear_all_favorites'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Animate all cards out
                cards.forEach((card, index) => {
                    setTimeout(() => {
                        card.style.transition = 'all 0.3s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.8)';
                    }, index * 50);
                });

                setTimeout(() => {
                    // Remove all cards from DOM
                    cards.forEach(card => card.remove());

                    updateFavoritesCount(0);
                    updateNavbarFavoritesCount(0);
                    checkEmptyState();
                    selectedItems = [];
                    updateSelectedCount();
                }, cards.length * 50 + 300);

                showNotification('All favorites cleared!', 'success');
            } else {
                // Restore cards state on error
                cards.forEach(card => {
                    card.style.opacity = '1';
                    card.style.pointerEvents = 'auto';
                });
                showNotification(data.message || 'Failed to clear favorites', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Restore cards state on error
            cards.forEach(card => {
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
            });
            showNotification('An error occurred', 'error');
        })
        .finally(() => {
            isLoading = false;
            hideLoadingState(clearBtn);
        });
    }
}

// Toggle select all
function toggleSelectAll() {
    const checkboxes = document.querySelectorAll('.favorite-select');
    const selectAllBtn = document.getElementById('select-all-btn');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
    });
    
    if (allChecked) {
        selectAllBtn.innerHTML = '<i class="fas fa-check-square me-1"></i>Select All';
    } else {
        selectAllBtn.innerHTML = '<i class="fas fa-square me-1"></i>Deselect All';
    }
    
    updateSelectedCount();
}

// Update selected count
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.favorite-select:checked');
    const removeBtn = document.getElementById('remove-selected-btn');
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    const selectAllBtn = document.getElementById('select-all-btn');
    const allCheckboxes = document.querySelectorAll('.favorite-select');

    selectedItems = Array.from(checkboxes).map(cb => cb.value);

    // Update card visual states
    allCheckboxes.forEach(checkbox => {
        const card = checkbox.closest('.favorite-card');
        if (card) {
            if (checkbox.checked) {
                card.classList.add('selected');
                card.classList.add('pulse');
                setTimeout(() => card.classList.remove('pulse'), 600);
            } else {
                card.classList.remove('selected');
            }
        }
    });

    // Update remove button
    if (selectedItems.length > 0) {
        removeBtn.disabled = false;
        removeBtn.innerHTML = `<i class="fas fa-trash me-1"></i>Remove Selected (${selectedItems.length})`;
        removeBtn.classList.add('btn-danger');
        removeBtn.classList.remove('btn-warning');
    } else {
        removeBtn.disabled = true;
        removeBtn.innerHTML = '<i class="fas fa-trash me-1"></i>Remove Selected';
        removeBtn.classList.remove('btn-danger');
        removeBtn.classList.add('btn-warning');
    }

    // Update add to cart button
    if (addToCartBtn) {
        if (selectedItems.length > 0) {
            addToCartBtn.disabled = false;
            addToCartBtn.innerHTML = `<i class="fas fa-shopping-cart me-1"></i>Add Selected to Cart (${selectedItems.length})`;
        } else {
            addToCartBtn.disabled = true;
            addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart me-1"></i>Add Selected to Cart';
        }
    }

    // Update select all button
    const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
    if (allChecked && allCheckboxes.length > 0) {
        selectAllBtn.innerHTML = '<i class="fas fa-square me-1"></i>Deselect All';
    } else {
        selectAllBtn.innerHTML = '<i class="fas fa-check-square me-1"></i>Select All';
    }
}

// Remove selected items
function removeSelected() {
    if (selectedItems.length === 0 || isLoading) return;

    if (confirm(`Are you sure you want to remove ${selectedItems.length} item(s) from your favorites?`)) {
        isLoading = true;
        const removeBtn = document.getElementById('remove-selected-btn');
        const originalText = removeBtn ? removeBtn.innerHTML : '';

        showLoadingState(removeBtn, originalText);

        // Add loading state to selected cards
        selectedItems.forEach(productId => {
            const card = document.querySelector(`[data-product-id="${productId}"]`);
            if (card) {
                card.style.opacity = '0.6';
                card.style.pointerEvents = 'none';
            }
        });

        // Fix the body format for array data
        const formData = new FormData();
        formData.append('action', 'remove_selected');
        selectedItems.forEach(id => {
            formData.append('product_ids[]', id);
        });

        fetch('favorites.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove selected cards from DOM with staggered animation
                selectedItems.forEach((productId, index) => {
                    const card = document.querySelector(`[data-product-id="${productId}"]`);
                    if (card) {
                        setTimeout(() => {
                            card.style.transition = 'all 0.3s ease';
                            card.style.opacity = '0';
                            card.style.transform = 'scale(0.8)';

                            setTimeout(() => {
                                card.remove();
                            }, 300);
                        }, index * 50);
                    }
                });

                setTimeout(() => {
                    updateFavoritesCount(data.remaining_count);
                    updateNavbarFavoritesCount(data.remaining_count);
                    checkEmptyState();
                    selectedItems = [];
                    updateSelectedCount();
                }, selectedItems.length * 50 + 300);

                showNotification(`Removed ${data.removed_count || selectedItems.length} item(s) from favorites!`, 'success');
            } else {
                // Restore cards state on error
                selectedItems.forEach(productId => {
                    const card = document.querySelector(`[data-product-id="${productId}"]`);
                    if (card) {
                        card.style.opacity = '1';
                        card.style.pointerEvents = 'auto';
                    }
                });
                showNotification(data.message || 'Failed to remove selected items', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Restore cards state on error
            selectedItems.forEach(productId => {
                const card = document.querySelector(`[data-product-id="${productId}"]`);
                if (card) {
                    card.style.opacity = '1';
                    card.style.pointerEvents = 'auto';
                }
            });
            showNotification('An error occurred', 'error');
        })
        .finally(() => {
            isLoading = false;
            hideLoadingState(removeBtn);
        });
    }
}

// Add selected items to cart
function addSelectedToCart() {
    if (selectedItems.length === 0 || isLoading) return;

    if (confirm(`Are you sure you want to add ${selectedItems.length} item(s) to cart? They will be removed from your favorites.`)) {
        isLoading = true;
        const addToCartBtn = document.getElementById('add-to-cart-btn');
        const originalText = addToCartBtn ? addToCartBtn.innerHTML : '';

        showLoadingState(addToCartBtn, originalText);

        // Add loading state to selected cards
        selectedItems.forEach(productId => {
            const card = document.querySelector(`[data-product-id="${productId}"]`);
            if (card) {
                card.style.opacity = '0.6';
                card.style.pointerEvents = 'none';
                card.classList.add('loading');
            }
        });

        const formData = new FormData();
        formData.append('action', 'add_selected_to_cart');
        selectedItems.forEach(id => {
            formData.append('product_ids[]', id);
        });

        fetch('favorites.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove successfully added cards from DOM with animation
                let delay = 0;
                selectedItems.forEach(productId => {
                    const card = document.querySelector(`[data-product-id="${productId}"]`);
                    if (card) {
                        setTimeout(() => {
                            card.style.transform = 'translateX(100%)';
                            card.style.opacity = '0';
                            setTimeout(() => {
                                card.remove();
                            }, 300);
                        }, delay);
                        delay += 50;
                    }
                });

                // Update counts and UI after animation
                setTimeout(() => {
                    updateFavoritesCount(data.remaining_favorites);
                    updateNavbarFavoritesCount(data.remaining_favorites);
                    updateNavbarCartCount(data.cart_count);
                    checkEmptyState();
                    selectedItems = [];
                    updateSelectedCount();
                }, selectedItems.length * 50 + 300);

                // Show success message with details
                let message = data.message;
                if (data.out_of_stock_count > 0) {
                    showNotification(message, 'warning');
                } else {
                    showNotification(message, 'success');
                }
            } else {
                // Restore cards state on error
                selectedItems.forEach(productId => {
                    const card = document.querySelector(`[data-product-id="${productId}"]`);
                    if (card) {
                        card.style.opacity = '1';
                        card.style.pointerEvents = 'auto';
                        card.classList.remove('loading');
                    }
                });
                showNotification(data.message || 'Failed to add items to cart', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Restore cards state on error
            selectedItems.forEach(productId => {
                const card = document.querySelector(`[data-product-id="${productId}"]`);
                if (card) {
                    card.style.opacity = '1';
                    card.style.pointerEvents = 'auto';
                    card.classList.remove('loading');
                }
            });
            showNotification('An error occurred', 'error');
        })
        .finally(() => {
            isLoading = false;
            hideLoadingState(addToCartBtn);
        });
    }
}

// Add to cart function is now handled by global-cart.js
// The global function automatically detects when called from favorites and handles the special logic

// Update favorites count in header
function updateFavoritesCount(count = null) {
    const remainingItems = count !== null ? count : document.querySelectorAll('.favorite-card').length;
    const countElement = document.getElementById('favorites-count');
    const subtitle = document.querySelector('.page-subtitle');

    if (countElement) {
        countElement.textContent = remainingItems;
    }

    if (subtitle) {
        subtitle.innerHTML = `<span id="favorites-count">${remainingItems}</span> item${remainingItems !== 1 ? 's' : ''} in your favorites`;
    }
}

// Update navbar favorites count
function updateNavbarFavoritesCount(count = null) {
    if (count !== null) {
        updateFavoritesCountInNavbar(count);
    } else {
        fetch('get_favorites_count.php')
            .then(response => response.json())
            .then(data => {
                updateFavoritesCountInNavbar(data.count);
            })
            .catch(error => {
                console.error('Error updating navbar count:', error);
            });
    }
}

// Update favorites count in navbar (from global-favorites.js)
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

// Update navbar cart count
function updateNavbarCartCount(count) {
    const cartIcon = document.querySelector('a[href="cart.php"] .cart-count');
    if (cartIcon) {
        if (count > 0) {
            cartIcon.textContent = count;
            cartIcon.style.display = 'flex';
        } else {
            cartIcon.style.display = 'none';
        }
    }
}

// Check if favorites is empty and show empty state
function checkEmptyState() {
    const remainingItems = document.querySelectorAll('.favorite-card').length;
    
    if (remainingItems === 0) {
        const favoritesGrid = document.querySelector('.favorites-grid');
        const headerActions = document.querySelector('.header-actions');
        
        if (favoritesGrid) {
            favoritesGrid.innerHTML = `
                <div class="empty-favorites">
                    <div class="empty-icon">
                        <i class="fas fa-heart-broken"></i>
                    </div>
                    <h3>No favorites yet</h3>
                    <p>Start adding products to your favorites to see them here.</p>
                    <a href="men.php" class="btn btn-dark btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                    </a>
                </div>
            `;
        }
        
        if (headerActions) {
            headerActions.style.display = 'none';
        }
    }
}

// Show notification
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

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Favorites page loaded');
    updateSelectedCount();

    // Add event listeners for checkboxes
    const checkboxes = document.querySelectorAll('.favorite-select');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedCount();
        });
    });

    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+A or Cmd+A to select all
        if ((e.ctrlKey || e.metaKey) && e.key === 'a' && !e.target.matches('input, textarea')) {
            e.preventDefault();
            toggleSelectAll();
        }

        // Delete key to remove selected
        if (e.key === 'Delete' && selectedItems.length > 0) {
            e.preventDefault();
            removeSelected();
        }
    });

    // Initialize navbar favorites count
    updateNavbarFavoritesCount();
});

