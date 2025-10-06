// Global variables for dynamic filtering
let currentFilters = {
    color: [],
    height: [],
    width: [],
    brand: [],
    collection: [],
    category: [],
    price_min: '',
    price_max: '',
    sort: 'newness'
};

// Clear all filters (sidebar only)
window.clearAllFilters = function() {
    // Clear all checkboxes
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Reset price sliders
    const priceMinSlider = document.getElementById('priceRangeMin');
    const priceMaxSlider = document.getElementById('priceRangeMax');
    const mobilePriceMinSlider = document.getElementById('mobilePriceRangeMin');
    const mobilePriceMaxSlider = document.getElementById('mobilePriceRangeMax');
    
    if (priceMinSlider && priceMaxSlider) {
        priceMinSlider.value = priceMinSlider.min;
        priceMaxSlider.value = priceMaxSlider.max;
    }
    
    if (mobilePriceMinSlider && mobilePriceMaxSlider) {
        mobilePriceMinSlider.value = mobilePriceMinSlider.min;
        mobilePriceMaxSlider.value = mobilePriceMaxSlider.max;
    }
    
    // Reset current filters
    currentFilters = {
        color: [], height: [], width: [], brand: [],
        collection: [], category: [], price_min: '',
        price_max: '', sort: 'newness'
    };
    
    // Update displays
    updatePriceDisplays();
    
    // Apply filters
    updateURL();
    applyFilters();
};

// Update price displays
function updatePriceDisplays() {
    const priceMinSlider = document.getElementById('priceRangeMin');
    const priceMaxSlider = document.getElementById('priceRangeMax');
    const priceMinDisplay = document.getElementById('priceMinDisplay');
    const priceMaxDisplay = document.getElementById('priceMaxDisplay');
    const mobilePriceMinSlider = document.getElementById('mobilePriceRangeMin');
    const mobilePriceMaxSlider = document.getElementById('mobilePriceRangeMax');
    const mobilePriceMinDisplay = document.getElementById('mobilePriceMinDisplay');
    const mobilePriceMaxDisplay = document.getElementById('mobilePriceMaxDisplay');
    
    if (priceMinDisplay && priceMinSlider) {
        priceMinDisplay.textContent = '₱' + parseInt(priceMinSlider.value).toLocaleString();
    }
    if (priceMaxDisplay && priceMaxSlider) {
        priceMaxDisplay.textContent = '₱' + parseInt(priceMaxSlider.value).toLocaleString();
    }
    if (mobilePriceMinDisplay && mobilePriceMinSlider) {
        mobilePriceMinDisplay.textContent = '₱' + parseInt(mobilePriceMinSlider.value).toLocaleString();
    }
    if (mobilePriceMaxDisplay && mobilePriceMaxSlider) {
        mobilePriceMaxDisplay.textContent = '₱' + parseInt(mobilePriceMaxSlider.value).toLocaleString();
    }
}

// Toggle filters sidebar (Desktop)
window.toggleFilters = function() {
    const sidebar = document.getElementById('filtersSidebar');
    const showBtn = document.getElementById('showFiltersBtn');
    const hideBtn = document.getElementById('hideFiltersBtn');
    const productsContainer = document.getElementById('productsContainer');
    const sidebarContainer = document.getElementById('sidebarContainer');
    const productsGrid = document.querySelector('.products-grid');
    
    // Add transitioning class for smooth animation
    if (productsGrid) {
        productsGrid.classList.add('transitioning');
    }
    
    if (sidebar.classList.contains('hidden')) {
        // Show filters - return to original layout
        sidebar.classList.remove('hidden');
        sidebarContainer.style.display = 'block';
        
        // Smooth slide animation: slide right to original position
        productsContainer.classList.remove('slide-left');
        productsContainer.classList.add('slide-right');
        
        // Change width classes after slide animation starts
        setTimeout(() => {
            productsContainer.classList.remove('col-lg-12');
            productsContainer.classList.add('col-lg-9');
        }, 100);
        
        showBtn.classList.add('d-none');
        hideBtn.classList.remove('d-none');
        
        // Update button text
        showBtn.innerHTML = '<i class="fas fa-eye-slash me-1"></i>Hide Filter';
        hideBtn.innerHTML = '<i class="fas fa-eye-slash me-1"></i>Hide Filter';
        
    } else {
        // Hide filters - expand to full width
        sidebar.classList.add('hidden');
        sidebarContainer.style.display = 'none';
        
        // Change width classes first
        productsContainer.classList.remove('col-lg-9');
        productsContainer.classList.add('col-lg-12');
        
        // Smooth slide animation: slide left to fill space
        setTimeout(() => {
            productsContainer.classList.remove('slide-right');
            productsContainer.classList.add('slide-left');
        }, 50);
        
        showBtn.classList.remove('d-none');
        hideBtn.classList.add('d-none');
        
        // Update button text
        showBtn.innerHTML = '<i class="fas fa-eye me-1"></i>Show Filter';
        hideBtn.innerHTML = '<i class="fas fa-eye me-1"></i>Show Filter';
    }
    
    // Remove transitioning class after animation completes
    setTimeout(() => {
        if (productsGrid) {
            productsGrid.classList.remove('transitioning');
        }
    }, 500);
};

// Apply mobile filters
window.applyMobileFilters = function() {
    const form = document.getElementById('mobileFilterForm');
    form.submit();
};

// Price range slider functionality
function initializePriceSliders() {
    // Desktop sliders
    const priceMinSlider = document.getElementById('priceRangeMin');
    const priceMaxSlider = document.getElementById('priceRangeMax');
    const priceMinDisplay = document.getElementById('priceMinDisplay');
    const priceMaxDisplay = document.getElementById('priceMaxDisplay');
    
    // Mobile sliders
    const mobilePriceMinSlider = document.getElementById('mobilePriceRangeMin');
    const mobilePriceMaxSlider = document.getElementById('mobilePriceRangeMax');
    const mobilePriceMinDisplay = document.getElementById('mobilePriceMinDisplay');
    const mobilePriceMaxDisplay = document.getElementById('mobilePriceMaxDisplay');
    
    // Desktop slider handlers
    if (priceMinSlider && priceMaxSlider) {
        priceMinSlider.addEventListener('input', function() {
            const minVal = parseInt(this.value);
            const maxVal = parseInt(priceMaxSlider.value);
            
            if (minVal >= maxVal) {
                priceMaxSlider.value = minVal;
            }
            
            currentFilters.price_min = minVal;
            currentFilters.price_max = parseInt(priceMaxSlider.value);
            
            if (priceMinDisplay) priceMinDisplay.textContent = '₱' + minVal.toLocaleString();
            if (priceMaxDisplay) priceMaxDisplay.textContent = '₱' + priceMaxSlider.value.toLocaleString();
            
            updateURL();
            applyFilters();
        });
        
        priceMaxSlider.addEventListener('input', function() {
            const maxVal = parseInt(this.value);
            const minVal = parseInt(priceMinSlider.value);
            
            if (maxVal <= minVal) {
                priceMinSlider.value = maxVal;
            }
            
            currentFilters.price_min = parseInt(priceMinSlider.value);
            currentFilters.price_max = maxVal;
            
            if (priceMinDisplay) priceMinDisplay.textContent = '₱' + priceMinSlider.value.toLocaleString();
            if (priceMaxDisplay) priceMaxDisplay.textContent = '₱' + maxVal.toLocaleString();
            
            updateURL();
            applyFilters();
        });
    }
    
    // Mobile slider handlers
    if (mobilePriceMinSlider && mobilePriceMaxSlider) {
        mobilePriceMinSlider.addEventListener('input', function() {
            const minVal = parseInt(this.value);
            const maxVal = parseInt(mobilePriceMaxSlider.value);
            
            if (minVal >= maxVal) {
                mobilePriceMaxSlider.value = minVal;
            }
            
            currentFilters.price_min = minVal;
            currentFilters.price_max = parseInt(mobilePriceMaxSlider.value);
            
            if (mobilePriceMinDisplay) mobilePriceMinDisplay.textContent = '₱' + minVal.toLocaleString();
            if (mobilePriceMaxDisplay) mobilePriceMaxDisplay.textContent = '₱' + mobilePriceMaxSlider.value.toLocaleString();
            
            updateURL();
            applyFilters();
        });
        
        mobilePriceMaxSlider.addEventListener('input', function() {
            const maxVal = parseInt(this.value);
            const minVal = parseInt(mobilePriceMinSlider.value);
            
            if (maxVal <= minVal) {
                mobilePriceMinSlider.value = maxVal;
            }
            
            currentFilters.price_min = parseInt(mobilePriceMinSlider.value);
            currentFilters.price_max = maxVal;
            
            if (mobilePriceMinDisplay) mobilePriceMinDisplay.textContent = '₱' + mobilePriceMinSlider.value.toLocaleString();
            if (mobilePriceMaxDisplay) mobilePriceMaxDisplay.textContent = '₱' + maxVal.toLocaleString();
            
            updateURL();
            applyFilters();
        });
    }
}

// Initialize filters from URL parameters
function initializeFiltersFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    
    currentFilters.color = urlParams.getAll('color[]') || [];
    currentFilters.height = urlParams.getAll('height[]') || [];
    currentFilters.width = urlParams.getAll('width[]') || [];
    currentFilters.brand = urlParams.getAll('brand[]') || [];
    currentFilters.collection = urlParams.getAll('collection[]') || [];
    currentFilters.category = urlParams.getAll('category[]') || [];
    currentFilters.price_min = urlParams.get('price_min') || '';
    currentFilters.price_max = urlParams.get('price_max') || '';
    currentFilters.sort = urlParams.get('sort') || 'newness';
}

// Update URL with current filters
function updateURL() {
    const params = new URLSearchParams();
    
    // Add all filter values
    currentFilters.color.forEach(color => params.append('color[]', color));
    currentFilters.height.forEach(height => params.append('height[]', height));
    currentFilters.width.forEach(width => params.append('width[]', width));
    currentFilters.brand.forEach(brand => params.append('brand[]', brand));
    currentFilters.collection.forEach(collection => params.append('collection[]', collection));
    currentFilters.category.forEach(category => params.append('category[]', category));
    
    if (currentFilters.price_min) params.append('price_min', currentFilters.price_min);
    if (currentFilters.price_max) params.append('price_max', currentFilters.price_max);
    params.append('sort', currentFilters.sort);
    
    // Update URL without page reload
    const newURL = window.location.pathname + '?' + params.toString();
    window.history.pushState({}, '', newURL);
}

// Apply filters dynamically
function applyFilters() {
    const formData = new FormData();
    
    // Add all filter values
    currentFilters.color.forEach(color => formData.append('color[]', color));
    currentFilters.height.forEach(height => formData.append('height[]', height));
    currentFilters.width.forEach(width => formData.append('width[]', width));
    currentFilters.brand.forEach(brand => formData.append('brand[]', brand));
    currentFilters.collection.forEach(collection => formData.append('collection[]', collection));
    currentFilters.category.forEach(category => formData.append('category[]', category));
    
    if (currentFilters.price_min) formData.append('price_min', currentFilters.price_min);
    if (currentFilters.price_max) formData.append('price_max', currentFilters.price_max);
    formData.append('sort', currentFilters.sort);
    
    // Show loading state
    showLoadingState();
    
    // Fetch filtered results
    fetch('kids.php?' + new URLSearchParams(formData).toString())
        .then(response => response.text())
        .then(html => {
            // Extract products from response
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const productsContainer = doc.querySelector('.products-grid .row');
            
            if (productsContainer) {
                // Update products
                document.querySelector('.products-grid .row').innerHTML = productsContainer.innerHTML;
                
                // Update results count
                const resultsCount = doc.querySelector('.results-count');
                if (resultsCount) {
                    document.querySelector('.results-count').textContent = resultsCount.textContent;
                }
            }
            
            hideLoadingState();
        })
        .catch(error => {
            console.error('Error applying filters:', error);
            hideLoadingState();
        });
}

// Show loading state
function showLoadingState() {
    const productsContainer = document.querySelector('.products-grid .row');
    if (productsContainer) {
        productsContainer.style.opacity = '0.5';
        productsContainer.style.pointerEvents = 'none';
    }
}

// Hide loading state
function hideLoadingState() {
    const productsContainer = document.querySelector('.products-grid .row');
    if (productsContainer) {
        productsContainer.style.opacity = '1';
        productsContainer.style.pointerEvents = 'auto';
    }
}

// Initialize filter handlers for dynamic updates
function initializeFilterHandlers() {
    // Handle checkbox changes
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const filterType = this.name.replace('[]', '');
            const value = this.value;
            
            if (this.checked) {
                if (!currentFilters[filterType].includes(value)) {
                    currentFilters[filterType].push(value);
                }
            } else {
                currentFilters[filterType] = currentFilters[filterType].filter(item => item !== value);
            }
            
            updateURL();
            applyFilters();
        });
    });
    
    // Handle sort changes - prevent form submission
    document.querySelectorAll('select[name="sort"]').forEach(select => {
        select.addEventListener('change', function(e) {
            e.preventDefault(); // Prevent form submission
            currentFilters.sort = this.value;
            updateURL();
            applyFilters();
        });
    });
    
    // Handle mobile sort changes
    const mobileSortSelect = document.getElementById('mobileSortSelect');
    if (mobileSortSelect) {
        mobileSortSelect.addEventListener('change', function(e) {
            e.preventDefault(); // Prevent form submission
            currentFilters.sort = this.value;
            updateURL();
            applyFilters();
        });
    }
}

// Note: addToCart function is defined in global-cart.js

// Add to favorites function is defined in global-favorites.js

// Quick view function - redirect to product page
window.quickView = function(productId) {
    window.location.href = 'product.php?id=' + productId;
};

// Show notification
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

// Initialize all components when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializePriceSliders();
    initializeFiltersFromURL();
    initializeFilterHandlers();
});
