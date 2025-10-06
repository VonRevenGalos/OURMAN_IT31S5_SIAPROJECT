// Navbar JavaScript Functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchIcon = document.querySelector('.search-icon');
    
    // Search functionality - only handle Enter key and icon click
    // Let search.js handle the real-time dropdown functionality
    function performSearch() {
        const searchTerm = searchInput.value.trim();
        if (searchTerm) {
            window.location.href = `search_results.php?q=${encodeURIComponent(searchTerm)}`;
        }
    }
    
    // Search on Enter key press (only if search.js hasn't handled it)
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                // Let search.js handle this if it's available
                if (window.searchManager && window.searchManager.submitSearch) {
                    window.searchManager.submitSearch();
                } else {
                    performSearch();
                }
            }
        });
    }
    
    // Search on icon click
    if (searchIcon) {
        searchIcon.addEventListener('click', function() {
            performSearch();
        });
    }
    
    // Add smooth scrolling for anchor links
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
    
    // Navbar scroll effect
    const navbar = document.querySelector('.navbar-custom');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.15)';
            } else {
                navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
            }
        });
    }
    
    // Mobile menu close on link click
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (navbarCollapse.classList.contains('show')) {
                const bsCollapse = new bootstrap.Collapse(navbarCollapse, {
                    toggle: false
                });
                bsCollapse.hide();
            }
        });
    });
    
    // Notification badge animation
    const notificationBadges = document.querySelectorAll('.notification-badge');
    notificationBadges.forEach(badge => {
        badge.addEventListener('click', function() {
            this.style.transform = 'scale(1.2)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 200);
        });
    });
    
    // Visual feedback for search input (let search.js handle the main functionality)
    if (searchInput) {
        searchInput.addEventListener('focus', function() {
            this.style.borderColor = '#3498db';
        });
        
        searchInput.addEventListener('blur', function() {
            this.style.borderColor = '#e9ecef';
        });
    }
});
