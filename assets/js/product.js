// Modern Product Page JavaScript - Nike Inspired

// Global variables for image gallery
let currentImageIndex = 0;
let productImages = [];

// Initialize product images array
function initializeProductImages() {
    // Get thumbnail images (which now include the main image as first thumbnail)
    const thumbnailItems = document.querySelectorAll('.thumbnail-item');
    productImages = Array.from(thumbnailItems).map(item => {
        const img = item.querySelector('img');
        return img ? img.src : '';
    }).filter(src => src !== '');

    console.log('Product images from thumbnails:', productImages);

    // Set initial image index to 0 (first thumbnail which is the main image)
    currentImageIndex = 0;

    // Preload all images for better performance
    preloadImages(productImages);
}

// Preload images for better performance
function preloadImages(imageUrls) {
    imageUrls.forEach(url => {
        if (url && !url.includes('placeholder')) {
            const img = new Image();
            img.src = url;
        }
    });
}

// Change main product image
function changeMainImage(imageSrc, index) {
    const mainImage = document.getElementById('mainProductImage');
    const loadingIndicator = document.getElementById('imageLoadingIndicator');
    
    // Clean the image source to remove any whitespace or special characters
    imageSrc = imageSrc.trim();
    
    console.log('Changing main image to:', imageSrc, 'Index:', index);
    
    if (mainImage) {
        // Show loading indicator
        if (loadingIndicator) {
            loadingIndicator.classList.add('show');
        }
        
        // Add loading state
        mainImage.style.opacity = '0';
        mainImage.style.transition = 'opacity 0.3s ease';
        
        // Create a new image to preload
        const newImage = new Image();
        newImage.onload = function() {
            console.log('Image loaded successfully:', imageSrc);
            // Image loaded successfully, update the main image
            mainImage.src = imageSrc;
            mainImage.style.opacity = '1';
            
            // Hide loading indicator
            if (loadingIndicator) {
                loadingIndicator.classList.remove('show');
            }
        };
        newImage.onerror = function() {
            // Image failed to load, show placeholder
            console.error('Failed to load image:', imageSrc);
            mainImage.src = 'assets/img/placeholder.jpg';
            mainImage.style.opacity = '1';
            
            // Hide loading indicator
            if (loadingIndicator) {
                loadingIndicator.classList.remove('show');
            }
        };
        newImage.src = imageSrc;
    }
    
    // Update active thumbnail
    document.querySelectorAll('.thumbnail-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Activate the clicked thumbnail
    const clickedThumbnail = document.querySelectorAll('.thumbnail-item')[index];
    if (clickedThumbnail) {
        clickedThumbnail.classList.add('active');
    }
    
    // Update current image index to match the thumbnail index
    currentImageIndex = index;
}

// Navigate to previous image
function previousImage() {
    if (productImages.length === 0) return;
    
    currentImageIndex = (currentImageIndex - 1 + productImages.length) % productImages.length;
    const imageSrc = productImages[currentImageIndex];
    
    // Update main image
    const mainImage = document.getElementById('mainProductImage');
    const loadingIndicator = document.getElementById('imageLoadingIndicator');
    
    if (mainImage) {
        // Show loading indicator
        if (loadingIndicator) {
            loadingIndicator.classList.add('show');
        }
        
        // Add loading state
        mainImage.style.opacity = '0';
        mainImage.style.transition = 'opacity 0.3s ease';
        
        // Create a new image to preload
        const newImage = new Image();
        newImage.onload = function() {
            mainImage.src = imageSrc;
            mainImage.style.opacity = '1';
            
            if (loadingIndicator) {
                loadingIndicator.classList.remove('show');
            }
        };
        newImage.onerror = function() {
            console.warn('Failed to load image:', imageSrc);
            mainImage.src = 'assets/img/placeholder.jpg';
            mainImage.style.opacity = '1';
            
            if (loadingIndicator) {
                loadingIndicator.classList.remove('show');
            }
        };
        newImage.src = imageSrc;
    }
    
    // Update active thumbnail
    updateActiveThumbnail();
}

// Navigate to next image
function nextImage() {
    if (productImages.length === 0) return;
    
    currentImageIndex = (currentImageIndex + 1) % productImages.length;
    const imageSrc = productImages[currentImageIndex];
    
    // Update main image
    const mainImage = document.getElementById('mainProductImage');
    const loadingIndicator = document.getElementById('imageLoadingIndicator');
    
    if (mainImage) {
        // Show loading indicator
        if (loadingIndicator) {
            loadingIndicator.classList.add('show');
        }
        
        // Add loading state
        mainImage.style.opacity = '0';
        mainImage.style.transition = 'opacity 0.3s ease';
        
        // Create a new image to preload
        const newImage = new Image();
        newImage.onload = function() {
            mainImage.src = imageSrc;
            mainImage.style.opacity = '1';
            
            if (loadingIndicator) {
                loadingIndicator.classList.remove('show');
            }
        };
        newImage.onerror = function() {
            console.warn('Failed to load image:', imageSrc);
            mainImage.src = 'assets/img/placeholder.jpg';
            mainImage.style.opacity = '1';
            
            if (loadingIndicator) {
                loadingIndicator.classList.remove('show');
            }
        };
        newImage.src = imageSrc;
    }
    
    // Update active thumbnail
    updateActiveThumbnail();
}

// Update active thumbnail based on current image index
function updateActiveThumbnail() {
    document.querySelectorAll('.thumbnail-item').forEach(item => {
        item.classList.remove('active');
    });

    // Activate the thumbnail corresponding to current image index
    const thumbnail = document.querySelectorAll('.thumbnail-item')[currentImageIndex];
    if (thumbnail) {
        thumbnail.classList.add('active');
    }
}

// Note: addToCart function is defined in global-cart.js
// This function handles size selection for product page
function addToCartWithSize(productId, quantity, button) {
    const sizeInput = document.querySelector('input[name="size"]:checked');

    if (!sizeInput) {
        showNotification('Please select a size', 'error');
        return;
    }

    const size = sizeInput.value;

    // Call the global addToCart function with the selected size
    if (typeof window.addToCart === 'function') {
        window.addToCart(productId, quantity, size, button);
    } else {
        console.error('Global addToCart function not found');
        showNotification('Error: Cart function not available', 'error');
    }
}

// Note: addToFavorites function is defined in global-favorites.js

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
        max-width: 400px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        border-radius: 12px;
        border: none;
        font-weight: 500;
    `;
    
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 4 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 4000);
}

// Image zoom functionality
function initializeImageZoom() {
    const mainImage = document.getElementById('mainProductImage');
    if (!mainImage) return;
    
    let isZoomed = false;
    
    mainImage.addEventListener('click', function() {
        if (isZoomed) {
            // Zoom out
            this.style.transform = 'scale(1)';
            this.style.cursor = 'zoom-in';
            isZoomed = false;
        } else {
            // Zoom in
            this.style.transform = 'scale(2)';
            this.style.cursor = 'zoom-out';
            isZoomed = true;
        }
    });
    
    // Reset zoom on image change
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'src') {
                mainImage.style.transform = 'scale(1)';
                mainImage.style.cursor = 'zoom-in';
                isZoomed = false;
            }
        });
    });
    
    observer.observe(mainImage, { attributes: true });
}

// Keyboard navigation
function initializeKeyboardNavigation() {
    document.addEventListener('keydown', function(e) {
        // Image navigation with arrow keys
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            previousImage();
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            nextImage();
        }
        
        // Size selection with number keys
        if (e.key >= '7' && e.key <= '9' || e.key === '0') {
            const sizeOptions = document.querySelectorAll('input[name="size"]');
            const keyValue = e.key === '0' ? '10' : e.key;
            const matchingOption = Array.from(sizeOptions).find(option => 
                option.value === keyValue || option.value === keyValue + '.5'
            );
            
            if (matchingOption) {
                matchingOption.checked = true;
                matchingOption.focus();
            }
        }
        
        // Add to cart with Enter key
        if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
            const addToCartBtn = document.querySelector('.btn-add-cart');
            if (addToCartBtn && !addToCartBtn.disabled) {
                const productId = addToCartBtn.getAttribute('onclick').match(/\d+/)[0];
                addToCartWithSize(productId, 1, addToCartBtn);
            }
        }
    });
}

// Smooth scrolling for better UX
function initializeSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Loading animation for images
function initializeImageLoading() {
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        // Set initial opacity for loading effect
        img.style.opacity = '0';
        img.style.transition = 'opacity 0.3s ease';
        
        // Handle image load
        img.addEventListener('load', function() {
            this.style.opacity = '1';
        });
        
        // Handle image error
        img.addEventListener('error', function() {
            console.warn('Image failed to load:', this.src);
            this.style.opacity = '1'; // Show even if failed to load
            // Optionally set a placeholder image
            if (!this.src.includes('placeholder')) {
                this.src = 'assets/img/placeholder.jpg';
            }
        });
        
        // If image is already loaded (cached), show it immediately
        if (img.complete && img.naturalHeight !== 0) {
            img.style.opacity = '1';
        }
    });
}

// Add hover effects for interactive elements
function initializeHoverEffects() {
    const interactiveElements = document.querySelectorAll('.btn-add-cart, .btn-favorite, .size-option, .thumbnail-item, .feature-item');
    interactiveElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            if (!this.classList.contains('btn-add-cart') || !this.disabled) {
                this.style.transform = 'translateY(-2px)';
            }
        });
        
        element.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

// Touch/swipe support for mobile
function initializeTouchSupport() {
    let startX = 0;
    let startY = 0;
    
    const mainImageContainer = document.querySelector('.main-image-container');
    if (!mainImageContainer) return;
    
    mainImageContainer.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    });
    
    mainImageContainer.addEventListener('touchend', function(e) {
        if (!startX || !startY) return;
        
        const endX = e.changedTouches[0].clientX;
        const endY = e.changedTouches[0].clientY;
        
        const diffX = startX - endX;
        const diffY = startY - endY;
        
        // Only trigger if horizontal swipe is more significant than vertical
        if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
            if (diffX > 0) {
                // Swipe left - next image
                nextImage();
            } else {
                // Swipe right - previous image
                previousImage();
            }
        }
        
        startX = 0;
        startY = 0;
    });
}

// Reviews functionality
function markHelpful(reviewId, button) {
    // Check if user is logged in (basic check)
    const helpfulSpan = button.querySelector('span');
    const currentCount = parseInt(helpfulSpan.textContent.match(/\d+/)[0]);

    // Send AJAX request to mark as helpful
    const formData = new FormData();
    formData.append('review_id', reviewId);
    formData.append('action', 'mark_helpful');

    fetch('review_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.action === 'added') {
                button.classList.add('helpful-active');
                helpfulSpan.textContent = `Helpful (${currentCount + 1})`;
            } else {
                button.classList.remove('helpful-active');
                helpfulSpan.textContent = `Helpful (${currentCount - 1})`;
            }
        } else {
            alert(data.message || 'Error updating helpful status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating helpful status');
    });
}

function loadMoreReviews() {
    const loadMoreBtn = document.querySelector('.btn-load-more');
    const originalText = loadMoreBtn.innerHTML;
    
    // Show loading state
    loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    loadMoreBtn.disabled = true;
    
    // Simulate loading more reviews
    setTimeout(() => {
        showNotification('More reviews loaded!', 'success');
        
        // Reset button
        loadMoreBtn.innerHTML = originalText;
        loadMoreBtn.disabled = false;
        
        // Here you would typically load more reviews from the server
        console.log('Loading more reviews...');
    }, 1500);
}

function openReviewModal() {
    const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
    modal.show();

    // Reset form
    const form = document.getElementById('reviewForm');
    if (form) {
        form.reset();
        document.querySelectorAll('.stars-input input').forEach(input => {
            input.checked = false;
        });
        updateRatingDisplay();
    }
}

// Update rating display
function updateRatingDisplay() {
    const ratingInputs = document.querySelectorAll('.stars-input input[name="rating"]');
    const ratingText = document.querySelector('.rating-text');

    if (ratingText) {
        ratingInputs.forEach(input => {
            input.addEventListener('change', function() {
                const rating = this.value;
                const labels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
                ratingText.textContent = labels[rating] || 'Click to rate';
            });
        });
    }
}

// Submit review
function submitReview() {
    const form = document.getElementById('reviewForm');
    if (!form) return;

    const formData = new FormData(form);

    // Validate rating
    const rating = formData.get('rating');
    if (!rating) {
        alert('Please select a rating');
        return;
    }

    // Validate title
    const title = formData.get('title').trim();
    if (!title) {
        alert('Please enter a review title');
        return;
    }

    // Validate review text
    const reviewText = formData.get('review_text').trim();
    if (!reviewText) {
        alert('Please enter your review');
        return;
    }

    // Add product ID
    formData.append('product_id', document.getElementById('productId').value);

    // Show loading state
    const submitBtn = document.querySelector('#reviewModal .btn-primary');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Submitting...';
    submitBtn.disabled = true;

    // Submit review
    fetch('submit_review.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
            modal.hide();
            // Reload page to show new review
            window.location.reload();
        } else {
            alert(data.message || 'Error submitting review');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error submitting review. Please try again.');
    })
    .finally(() => {
        // Reset button state
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

// Review filtering functionality
function initializeReviewFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const sortSelect = document.getElementById('reviewSort');
    const reviewsList = document.getElementById('reviewsList');
    
    // Filter by rating
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            const filter = this.getAttribute('data-filter');
            filterReviews(filter);
        });
    });
    
    // Sort reviews
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const sortBy = this.value;
            sortReviews(sortBy);
        });
    }
}

function filterReviews(rating) {
    const reviewItems = document.querySelectorAll('.review-item');
    
    reviewItems.forEach(item => {
        const itemRating = item.getAttribute('data-rating');
        
        if (rating === 'all' || itemRating === rating) {
            item.style.display = 'block';
            item.style.animation = 'fadeIn 0.3s ease';
        } else {
            item.style.display = 'none';
        }
    });
}

function sortReviews(sortBy) {
    const reviewsList = document.getElementById('reviewsList');
    const reviewItems = Array.from(document.querySelectorAll('.review-item'));
    
    reviewItems.sort((a, b) => {
        switch (sortBy) {
            case 'newest':
                return new Date(b.getAttribute('data-date')) - new Date(a.getAttribute('data-date'));
            case 'oldest':
                return new Date(a.getAttribute('data-date')) - new Date(b.getAttribute('data-date'));
            case 'highest':
                return parseInt(b.getAttribute('data-rating')) - parseInt(a.getAttribute('data-rating'));
            case 'lowest':
                return parseInt(a.getAttribute('data-rating')) - parseInt(b.getAttribute('data-rating'));
            default:
                return 0;
        }
    });
    
    // Clear and re-append sorted items
    reviewsList.innerHTML = '';
    reviewItems.forEach(item => {
        reviewsList.appendChild(item);
    });
}

// Handle window resize for better responsiveness
function handleWindowResize() {
    // Recalculate image container heights on resize
    const imageContainer = document.querySelector('.image-zoom-container');
    if (imageContainer) {
        // Force reflow to ensure proper sizing
        imageContainer.style.height = 'auto';
        setTimeout(() => {
            // Reset to ensure proper responsive behavior
            imageContainer.style.height = '';
        }, 100);
    }
}

// Utility function to format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount);
}

// Utility function to validate form inputs
function validateProductForm() {
    const sizeSelected = document.querySelector('input[name="size"]:checked');
    
    if (!sizeSelected) {
        showNotification('Please select a size', 'error');
        return false;
    }
    
    return true;
}

// Initialize thumbnail click events
function initializeThumbnailClicks() {
    const thumbnailItems = document.querySelectorAll('.thumbnail-item');
    console.log('Found thumbnail items:', thumbnailItems.length);
    
    thumbnailItems.forEach((item, index) => {
        const img = item.querySelector('img');
        if (img) {
            console.log(`Thumbnail ${index}:`, img.src);
            
            // Remove any existing click handlers
            item.onclick = null;
            
            // Add new click handler
            item.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Thumbnail clicked:', index, img.src);
                changeMainImage(img.src, index);
            });
        }
    });
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all functionality
    initializeProductImages();
    initializeImageZoom();
    initializeKeyboardNavigation();
    initializeSmoothScrolling();
    initializeImageLoading();
    initializeHoverEffects();
    initializeTouchSupport();
    initializeReviewFilters();
    
    // Ensure thumbnail click events are properly bound
    initializeThumbnailClicks();
    
    // Add window resize listener
    window.addEventListener('resize', handleWindowResize);
    
    // Set up size selection validation
    const sizeOptions = document.querySelectorAll('input[name="size"]');
    sizeOptions.forEach(option => {
        option.addEventListener('change', function() {
            // Remove any previous validation messages
            const existingMessage = document.querySelector('.size-validation-message');
            if (existingMessage) {
                existingMessage.remove();
            }
        });
    });
    
    // Add loading states to buttons
    const buttons = document.querySelectorAll('.btn-add-cart, .btn-favorite');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            if (this.classList.contains('btn-add-cart') || this.classList.contains('btn-favorite')) {
                // Loading state is handled in the respective functions
                return;
            }
        });
    });
    
    // Add intersection observer for animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    const animatedElements = document.querySelectorAll('.feature-item, .product-details-content');
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
    
    console.log('Product page initialized successfully');
});

// Export functions for global access
window.changeMainImage = changeMainImage;
window.previousImage = previousImage;
window.nextImage = nextImage;
window.addToCartWithSize = addToCartWithSize;
// Note: addToFavorites is exported by global-favorites.js
window.markHelpful = markHelpful;
window.loadMoreReviews = loadMoreReviews;
window.openReviewModal = openReviewModal;
window.submitReview = submitReview;

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeImageGallery();
    initializeReviewFilters();
    initializeTouchGestures();
    updateRatingDisplay();

    // Check if we should open review modal (from notification redirect)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('review') === '1') {
        // Small delay to ensure page is fully loaded
        setTimeout(() => {
            const writeReviewBtn = document.querySelector('.btn-write-review:not([disabled])');
            if (writeReviewBtn) {
                openReviewModal();
            }
        }, 500);
    }
});