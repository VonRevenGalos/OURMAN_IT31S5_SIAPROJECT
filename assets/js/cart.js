// Cart Page JavaScript
let selectedItems = [];
let isLoading = false;
let currentEditingCartId = null;

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

// Update quantity
function updateQuantity(cartId, newQuantity) {
    if (isLoading || newQuantity < 1) return;

    isLoading = true;
    const cartItem = document.querySelector(`.cart-item[data-cart-id="${cartId}"]`);

    if (cartItem) {
        cartItem.classList.add('loading');
    }
    
    fetch('cart_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_quantity&cart_id=${cartId}&quantity=${newQuantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update quantity input
            const qtyInput = cartItem.querySelector('.qty-input');
            if (qtyInput) {
                qtyInput.value = newQuantity;
            }

            // Update item total price
            const priceTotal = cartItem.querySelector('.price-total');
            if (priceTotal) {
                priceTotal.textContent = `₱${data.item_total.toFixed(2)}`;
            }
            
            // Update cart summary
            updateCartSummary(data.cart_count, data.cart_total);
            updateNavbarCartCount(data.cart_count);
            
            // Update quantity buttons
            updateQuantityButtons(cartItem, newQuantity);
            
            showNotification('Quantity updated!', 'success');
        } else {
            showNotification(data.message || 'Failed to update quantity', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    })
    .finally(() => {
        isLoading = false;
        if (cartItem) {
            cartItem.classList.remove('loading');
        }
    });
}

// Update quantity buttons state
function updateQuantityButtons(cartItem, quantity) {
    const minusBtn = cartItem.querySelector('.qty-minus');
    const plusBtn = cartItem.querySelector('.qty-plus');
    const qtyInput = cartItem.querySelector('.qty-input');

    if (minusBtn) {
        minusBtn.disabled = quantity <= 1;
    }

    if (plusBtn && qtyInput) {
        const maxQty = parseInt(qtyInput.getAttribute('max'));
        plusBtn.disabled = quantity >= maxQty;
    }
}

// Edit size
function editSize(cartId, currentSize) {
    currentEditingCartId = cartId;
    
    // Clear previous selections
    document.querySelectorAll('.size-option').forEach(btn => {
        btn.classList.remove('selected');
    });
    
    // Select current size
    const currentSizeBtn = document.querySelector(`[data-size="${currentSize}"]`);
    if (currentSizeBtn) {
        currentSizeBtn.classList.add('selected');
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('sizeModal'));
    modal.show();
}

// Save size
function saveSize() {
    const selectedSize = document.querySelector('.size-option.selected');
    if (!selectedSize || !currentEditingCartId) {
        showNotification('Please select a size', 'error');
        return;
    }
    
    const newSize = selectedSize.getAttribute('data-size');
    
    fetch('cart_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_size&cart_id=${currentEditingCartId}&size=${newSize}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update size display
            const cartItem = document.querySelector(`.cart-item[data-cart-id="${currentEditingCartId}"]`);
            const sizeBtn = cartItem.querySelector('.size-btn');
            if (sizeBtn) {
                sizeBtn.innerHTML = `${newSize} <i class="fas fa-edit ms-1"></i>`;
                sizeBtn.setAttribute('onclick', `editSize(${currentEditingCartId}, '${newSize}')`);
                // Remove size-required class since size is now selected
                sizeBtn.classList.remove('size-required');
            }
            
            // Hide modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('sizeModal'));
            modal.hide();
            
            showNotification('Size updated!', 'success');
        } else {
            showNotification(data.message || 'Failed to update size', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// Remove from cart
function removeFromCart(cartId) {
    if (isLoading) return;
    
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        isLoading = true;
        const cartItem = document.querySelector(`.cart-item[data-cart-id="${cartId}"]`);
        
        if (cartItem) {
            cartItem.style.opacity = '0.6';
            cartItem.style.pointerEvents = 'none';
        }
        
        fetch('cart_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=remove_item&cart_id=${cartId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove item with animation
                if (cartItem) {
                    cartItem.style.transition = 'all 0.3s ease';
                    cartItem.style.opacity = '0';
                    cartItem.style.transform = 'scale(0.8)';
                    
                    setTimeout(() => {
                        cartItem.remove();
                        updateCartSummary(data.cart_count, data.cart_total);
                        updateNavbarCartCount(data.cart_count);
                        checkEmptyState();
                        updateSelectedCount();
                    }, 300);
                }
                
                showNotification('Item removed from cart!', 'success');
            } else {
                // Restore item state on error
                if (cartItem) {
                    cartItem.style.opacity = '1';
                    cartItem.style.pointerEvents = 'auto';
                }
                showNotification(data.message || 'Failed to remove item', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Restore item state on error
            if (cartItem) {
                cartItem.style.opacity = '1';
                cartItem.style.pointerEvents = 'auto';
            }
            showNotification('An error occurred', 'error');
        })
        .finally(() => {
            isLoading = false;
        });
    }
}

// Toggle select all
function toggleSelectAll() {
    const checkboxes = document.querySelectorAll('.cart-select');
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
    const checkboxes = document.querySelectorAll('.cart-select:checked');
    const removeBtn = document.getElementById('remove-selected-btn');
    const selectAllBtn = document.getElementById('select-all-btn');
    const allCheckboxes = document.querySelectorAll('.cart-select');
    
    selectedItems = Array.from(checkboxes).map(cb => cb.value);
    
    // Update cart item visual states
    allCheckboxes.forEach(checkbox => {
        const cartItem = checkbox.closest('.cart-item');
        if (cartItem) {
            if (checkbox.checked) {
                cartItem.classList.add('selected');
            } else {
                cartItem.classList.remove('selected');
            }
        }
    });
    
    // Update remove button
    if (selectedItems.length > 0) {
        removeBtn.disabled = false;
        removeBtn.innerHTML = `<i class="fas fa-trash me-1"></i>Remove Selected (${selectedItems.length})`;
        removeBtn.classList.add('btn-danger');
        removeBtn.classList.remove('btn-dark');
    } else {
        removeBtn.disabled = true;
        removeBtn.innerHTML = '<i class="fas fa-trash me-1"></i>Remove Selected';
        removeBtn.classList.remove('btn-danger');
        removeBtn.classList.add('btn-dark');
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
    
    if (confirm(`Are you sure you want to remove ${selectedItems.length} item(s) from your cart?`)) {
        isLoading = true;
        const removeBtn = document.getElementById('remove-selected-btn');
        const originalText = removeBtn ? removeBtn.innerHTML : '';
        
        showLoadingState(removeBtn, originalText);
        
        // Add loading state to selected items
        selectedItems.forEach(cartId => {
            const cartItem = document.querySelector(`.cart-item[data-cart-id="${cartId}"]`);
            if (cartItem) {
                cartItem.style.opacity = '0.6';
                cartItem.style.pointerEvents = 'none';
            }
        });
        
        const formData = new FormData();
        formData.append('action', 'remove_selected');
        selectedItems.forEach(id => {
            formData.append('cart_ids[]', id);
        });
        
        fetch('cart_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove selected items with staggered animation
                selectedItems.forEach((cartId, index) => {
                    const cartItem = document.querySelector(`.cart-item[data-cart-id="${cartId}"]`);
                    if (cartItem) {
                        setTimeout(() => {
                            cartItem.style.transition = 'all 0.3s ease';
                            cartItem.style.opacity = '0';
                            cartItem.style.transform = 'scale(0.8)';
                            
                            setTimeout(() => {
                                cartItem.remove();
                            }, 300);
                        }, index * 50);
                    }
                });
                
                setTimeout(() => {
                    updateCartSummary(data.cart_count, data.cart_total);
                    updateNavbarCartCount(data.cart_count);
                    checkEmptyState();
                    selectedItems = [];
                    updateSelectedCount();
                }, selectedItems.length * 50 + 300);
                
                showNotification(`Removed ${data.removed_count || selectedItems.length} item(s) from cart!`, 'success');
            } else {
                // Restore items state on error
                selectedItems.forEach(cartId => {
                    const cartItem = document.querySelector(`.cart-item[data-cart-id="${cartId}"]`);
                    if (cartItem) {
                        cartItem.style.opacity = '1';
                        cartItem.style.pointerEvents = 'auto';
                    }
                });
                showNotification(data.message || 'Failed to remove selected items', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Restore items state on error
            selectedItems.forEach(cartId => {
                const cartItem = document.querySelector(`.cart-item[data-cart-id="${cartId}"]`);
                if (cartItem) {
                    cartItem.style.opacity = '1';
                    cartItem.style.pointerEvents = 'auto';
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

// Clear cart
function clearCart() {
    if (isLoading) return;

    if (confirm('Are you sure you want to clear your entire cart? This action cannot be undone.')) {
        isLoading = true;
        const clearBtn = document.querySelector('button[onclick="clearCart()"]');
        const originalText = clearBtn ? clearBtn.innerHTML : '';

        showLoadingState(clearBtn, originalText);

        // Add loading state to all items
        const cartItems = document.querySelectorAll('.cart-item');
        cartItems.forEach(item => {
            item.style.opacity = '0.6';
            item.style.pointerEvents = 'none';
        });

        fetch('cart_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clear_cart'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Animate all items out
                cartItems.forEach((item, index) => {
                    setTimeout(() => {
                        item.style.transition = 'all 0.3s ease';
                        item.style.opacity = '0';
                        item.style.transform = 'scale(0.8)';
                    }, index * 50);
                });

                setTimeout(() => {
                    // Remove all items from DOM
                    cartItems.forEach(item => item.remove());

                    updateCartSummary(0, 0);
                    updateNavbarCartCount(0);
                    checkEmptyState();
                    selectedItems = [];
                    updateSelectedCount();
                }, cartItems.length * 50 + 300);

                showNotification('Cart cleared!', 'success');
            } else {
                // Restore items state on error
                cartItems.forEach(item => {
                    item.style.opacity = '1';
                    item.style.pointerEvents = 'auto';
                });
                showNotification(data.message || 'Failed to clear cart', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Restore items state on error
            cartItems.forEach(item => {
                item.style.opacity = '1';
                item.style.pointerEvents = 'auto';
            });
            showNotification('An error occurred', 'error');
        })
        .finally(() => {
            isLoading = false;
            hideLoadingState(clearBtn);
        });
    }
}

// Update cart summary
function updateCartSummary(itemCount, cartTotal) {
    const summaryItems = document.getElementById('summary-items');
    const summarySubtotal = document.getElementById('summary-subtotal');
    const summaryShipping = document.getElementById('summary-shipping');
    const summaryTax = document.getElementById('summary-tax');
    const summaryTotal = document.getElementById('summary-total');
    const cartCount = document.getElementById('cart-count');
    const pageSubtitle = document.querySelector('.page-subtitle');

    // Calculate values
    const shipping = cartTotal > 100 ? 0 : 10;
    const tax = cartTotal * 0.08;
    const total = cartTotal + shipping + tax;

    // Update elements
    if (summaryItems) summaryItems.textContent = itemCount;
    if (summarySubtotal) summarySubtotal.textContent = `₱${cartTotal.toFixed(2)}`;
    if (summaryShipping) summaryShipping.textContent = shipping > 0 ? `₱${shipping.toFixed(2)}` : 'FREE';
    if (summaryTax) summaryTax.textContent = `₱${tax.toFixed(2)}`;
    if (summaryTotal) summaryTotal.textContent = `₱${total.toFixed(2)}`;
    if (cartCount) cartCount.textContent = itemCount;

    if (pageSubtitle) {
        pageSubtitle.innerHTML = `<span id="cart-count">${itemCount}</span> item${itemCount !== 1 ? 's' : ''} in your cart`;
    }
}

// Update navbar cart count
function updateNavbarCartCount(count) {
    const cartIcon = document.querySelector('a[href="cart.php"] .notification-badge');
    if (cartIcon) {
        if (count > 0) {
            cartIcon.textContent = count;
            cartIcon.style.display = 'flex';
        } else {
            cartIcon.style.display = 'none';
        }
    }
}

// Check if cart is empty and show empty state
function checkEmptyState() {
    const remainingItems = document.querySelectorAll('.cart-item').length;

    if (remainingItems === 0) {
        const cartContent = document.querySelector('.cart-content .container');
        const headerActions = document.querySelector('.header-actions');

        if (cartContent) {
            cartContent.innerHTML = `
                <div class="empty-cart">
                    <div class="empty-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Your cart is empty</h3>
                    <p>Start adding products to your cart to see them here.</p>
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

// Proceed to checkout
function proceedToCheckout() {
    // Check if cart has items
    const cartItems = document.querySelectorAll('.cart-item');
    if (cartItems.length === 0) {
        showNotification('Your cart is empty!', 'error');
        return;
    }

    // Get selected items (if any are selected, only checkout those)
    const selectedCheckboxes = document.querySelectorAll('.cart-select:checked');
    const itemsToCheck = selectedCheckboxes.length > 0 ?
        Array.from(selectedCheckboxes).map(cb => cb.closest('.cart-item')) :
        Array.from(cartItems);

    // Check if selected/all items have sizes selected
    const itemsWithoutSize = [];
    itemsToCheck.forEach(item => {
        const sizeBtn = item.querySelector('.size-btn');
        if (sizeBtn) {
            // Check if button has the 'size-required' class or contains "Select Size" text
            const hasRequiredClass = sizeBtn.classList.contains('size-required');
            const containsSelectSize = sizeBtn.textContent.includes('Select Size');

            if (hasRequiredClass || containsSelectSize) {
                const titleElement = item.querySelector('.item-title a');
                if (titleElement) {
                    itemsWithoutSize.push(titleElement.textContent.trim());
                }
            }
        }
    });

    if (itemsWithoutSize.length > 0) {
        const itemList = itemsWithoutSize.length > 3
            ? itemsWithoutSize.slice(0, 3).join(', ') + ` and ${itemsWithoutSize.length - 3} more`
            : itemsWithoutSize.join(', ');
        showNotification(`Please select sizes for: ${itemList}`, 'warning');

        // Highlight items without sizes
        itemsWithoutSize.forEach(itemTitle => {
            const cartItems = document.querySelectorAll('.cart-item');
            cartItems.forEach(item => {
                const titleElement = item.querySelector('.item-title a');
                if (titleElement && titleElement.textContent.trim() === itemTitle) {
                    const sizeBtn = item.querySelector('.size-btn');
                    if (sizeBtn) {
                        sizeBtn.style.animation = 'pulse-warning 1s ease-in-out 3';
                    }
                }
            });
        });

        return;
    }

    // If items are selected, store them for checkout
    if (selectedCheckboxes.length > 0) {
        const selectedCartIds = Array.from(selectedCheckboxes).map(cb => cb.value);
        // Store selected items in sessionStorage for checkout
        sessionStorage.setItem('selectedCartItems', JSON.stringify(selectedCartIds));
    } else {
        // Clear any previously selected items if checking out all
        sessionStorage.removeItem('selectedCartItems');
    }

    // Redirect to checkout page
    window.location.href = 'checkout.php';
}



// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} notification-toast`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
        ${message}
    `;

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

    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 100);

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
    console.log('Cart page loaded');
    updateSelectedCount();

    // Add event listeners for checkboxes
    const checkboxes = document.querySelectorAll('.cart-select');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedCount();
        });
    });

    // Add event listeners for quantity buttons
    document.querySelectorAll('.qty-minus').forEach(btn => {
        btn.addEventListener('click', function() {
            const cartId = this.getAttribute('data-cart-id');
            const cartItem = this.closest('.cart-item');
            const qtyInput = cartItem.querySelector('.qty-input');
            const currentQty = parseInt(qtyInput.value);
            if (currentQty > 1) {
                updateQuantity(cartId, currentQty - 1);
            }
        });
    });

    document.querySelectorAll('.qty-plus').forEach(btn => {
        btn.addEventListener('click', function() {
            const cartId = this.getAttribute('data-cart-id');
            const cartItem = this.closest('.cart-item');
            const qtyInput = cartItem.querySelector('.qty-input');
            const currentQty = parseInt(qtyInput.value);
            const maxQty = parseInt(qtyInput.getAttribute('max'));
            if (currentQty < maxQty) {
                updateQuantity(cartId, currentQty + 1);
            }
        });
    });

    // Add event listeners for quantity input changes
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', function() {
            const cartId = this.getAttribute('data-cart-id');
            const newQty = parseInt(this.value);
            if (newQty >= 1) {
                updateQuantity(cartId, newQty);
            }
        });
    });

    // Add event listeners for size options
    document.querySelectorAll('.size-option').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.size-option').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
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

    // Initialize navbar cart count
    fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            updateNavbarCartCount(data.count);
        })
        .catch(error => {
            console.error('Error loading cart count:', error);
        });
});
