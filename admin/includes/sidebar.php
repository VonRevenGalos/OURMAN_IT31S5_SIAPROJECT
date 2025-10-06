<?php
/**
 * Professional Admin Sidebar Component
 * Fixed sidebar for desktop, collapsible for mobile
 */

// Include admin authentication if not already included
if (!function_exists('getAdminSessionInfo')) {
    require_once __DIR__ . '/admin_auth.php';
}

// Get current page for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

// Get admin info
$adminInfo = getAdminSessionInfo();
?>

<!-- Admin Sidebar -->
<div class="admin-sidebar" id="adminSidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <i class="fas fa-store"></i>
            <span class="brand-text">ShoeStore Admin</span>
        </div>
        <!-- Mobile toggle button (hidden on desktop) -->
        <button class="mobile-toggle d-lg-none" id="sidebarToggle" aria-label="Toggle Sidebar">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Admin Profile Section -->
    <div class="admin-profile">
        <div class="profile-avatar">
            <i class="fas fa-user-shield"></i>
        </div>
        <div class="profile-info">
            <h6 class="profile-name"><?php echo htmlspecialchars($adminInfo['name']); ?></h6>
            <small class="profile-role">Administrator</small>
        </div>
        <div class="profile-status">
            <span class="status-indicator"></span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <!-- Dashboard -->
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <!-- Products -->
            <li class="nav-item">
                <a href="all_products.php" class="nav-link <?php echo $currentPage === 'all_products.php' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i>
                    <span class="nav-text">Products</span>
                </a>
            </li>

            <!-- Inventory -->
            <li class="nav-item">
                <a href="inventory.php" class="nav-link <?php echo $currentPage === 'inventory.php' ? 'active' : ''; ?>">
                    <i class="fas fa-warehouse"></i>
                    <span class="nav-text">Inventory</span>
                </a>
            </li>

            <!-- Orders -->
            <li class="nav-item">
                <a href="orders.php" class="nav-link <?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="nav-text">Orders</span>
                </a>
            </li>

            <!-- Users -->
            <li class="nav-item">
                <a href="users.php" class="nav-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span class="nav-text">Users</span>
                </a>
            </li>

            <!-- Analytics -->
            <li class="nav-item">
                <a href="analytics.php" class="nav-link <?php echo $currentPage === 'analytics.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span class="nav-text">Analytics</span>
                </a>
            </li>

            <!-- Live Chat -->
            <li class="nav-item">
                <a href="livechat.php" class="nav-link <?php echo $currentPage === 'livechat.php' ? 'active' : ''; ?>">
                    <i class="fas fa-ticket-alt"></i>
                    <span class="nav-text">Live Chat</span>
                </a>
            </li>

            <!-- Audit Log -->
            <li class="nav-item">
                <a href="audit.php" class="nav-link <?php echo $currentPage === 'audit.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="nav-text">Audit Log</span>
                </a>
            </li>

            <!-- Settings -->
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span class="nav-text">Settings</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="footer-stats">
            <div class="stat-item">
                <small>Session Time</small>
                <span id="sessionTime"><?php echo gmdate('H:i:s', $adminInfo['session_duration']); ?></span>
            </div>
        </div>
        
        <div class="footer-actions">
            <a href="../index.php" class="footer-link" title="View Store" target="_blank">
                <i class="fas fa-external-link-alt"></i>
            </a>
            <a href="#" class="footer-link" title="Admin Profile" onclick="showAdminProfile()">
                <i class="fas fa-user-cog"></i>
            </a>
            <a href="admin_logout.php" class="footer-link logout-link" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</div>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
/**
 * Professional Admin Sidebar JavaScript
 * Clean, reliable, and fully integrated
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize sidebar functionality
    initializeSidebar();

    // Start session timer
    startSessionTimer();

    // Setup responsive handlers
    setupResponsiveHandlers();
});

function initializeSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (!sidebar || !sidebarToggle || !sidebarOverlay) return;

    // Toggle sidebar functionality (mobile only)
    sidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();

        // Only toggle on mobile (desktop sidebar is always visible)
        if (window.innerWidth <= 991.98) {
            toggleMobileSidebar();
        }
    });

    // Close sidebar on overlay click (mobile only)
    sidebarOverlay.addEventListener('click', function() {
        closeMobileSidebar();
    });
}

function toggleMobileSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (sidebar.classList.contains('show')) {
        closeMobileSidebar();
    } else {
        openMobileSidebar();
    }
}

function openMobileSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    sidebar.classList.add('show');
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeMobileSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    sidebar.classList.remove('show');
    overlay.classList.remove('show');
    document.body.style.overflow = '';
}

function toggleSubmenu(submenuItem) {
    const isOpen = submenuItem.classList.contains('open');

    // Close all other submenus
    document.querySelectorAll('.has-submenu.open').forEach(function(item) {
        if (item !== submenuItem) {
            item.classList.remove('open');
        }
    });

    // Toggle current submenu
    if (isOpen) {
        submenuItem.classList.remove('open');
    } else {
        submenuItem.classList.add('open');
    }
}

function openActiveSubmenu() {
    const activeSubmenu = document.querySelector('.has-submenu.open');
    if (activeSubmenu) {
        activeSubmenu.classList.add('open');
    }
}

function startSessionTimer() {
    setInterval(updateSessionTime, 1000);
}

function setupResponsiveHandlers() {
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 991.98) {
            // Mobile: ensure sidebar is hidden
            closeMobileSidebar();
        } else {
            // Desktop: ensure sidebar is visible and overlay is hidden
            closeMobileSidebar();
        }
    });

    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileSidebar();
        }
    });

    // Handle touch gestures for mobile
    setupTouchGestures();
}

function updateSessionTime() {
    const sessionTimeElement = document.getElementById('sessionTime');
    if (!sessionTimeElement) return;

    const currentTime = sessionTimeElement.textContent;
    const parts = currentTime.split(':');
    let totalSeconds = parseInt(parts[0]) * 3600 + parseInt(parts[1]) * 60 + parseInt(parts[2]);
    totalSeconds++;

    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    sessionTimeElement.textContent =
        String(hours).padStart(2, '0') + ':' +
        String(minutes).padStart(2, '0') + ':' +
        String(seconds).padStart(2, '0');
}

function showAdminProfile() {
    // TODO: Implement admin profile modal
    alert('Admin profile feature coming soon!');
}

function setupTouchGestures() {
    let touchStartX = 0;
    let touchEndX = 0;

    document.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    document.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    }, { passive: true });

    function handleSwipe() {
        if (window.innerWidth > 991.98) return;

        const sidebar = document.getElementById('adminSidebar');
        const swipeDistance = touchEndX - touchStartX;
        const swipeThreshold = 100;

        // Swipe right from left edge to open
        if (swipeDistance > swipeThreshold && touchStartX < 50) {
            openMobileSidebar();
        }

        // Swipe left to close when sidebar is open
        if (swipeDistance < -swipeThreshold && sidebar.classList.contains('show')) {
            closeMobileSidebar();
        }
    }
}

// Global functions for external access
window.toggleMobileSidebar = toggleMobileSidebar;
window.showAdminProfile = showAdminProfile;
</script>
