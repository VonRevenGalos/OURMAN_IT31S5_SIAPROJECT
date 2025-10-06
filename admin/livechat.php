<?php
require_once 'includes/admin_auth.php';
require_once '../db.php';

// Check admin authentication
if (!isAdminLoggedIn()) {
    header("Location: adminlogin.php");
    exit();
}

$admin_id = getAdminId();
$currentUser = getCurrentAdminUser();

// Get chat sessions
$chat_sessions = [];
try {
    $stmt = $pdo->prepare("
        SELECT cs.*, u.first_name, u.last_name, u.email,
               (SELECT COUNT(*) FROM chat_messages WHERE session_id = cs.id AND sender_type = 'user' AND is_read = 0) as unread_count,
               (SELECT message FROM chat_messages WHERE session_id = cs.id ORDER BY created_at DESC LIMIT 1) as last_message,
               (SELECT created_at FROM chat_messages WHERE session_id = cs.id ORDER BY created_at DESC LIMIT 1) as last_message_time
        FROM chat_sessions cs
        JOIN users u ON cs.user_id = u.id
        WHERE cs.status IN ('pending', 'active', 'closed')
        ORDER BY 
            CASE cs.status 
                WHEN 'pending' THEN 1 
                WHEN 'active' THEN 2 
                WHEN 'closed' THEN 3 
            END,
            cs.last_activity DESC
    ");
    $stmt->execute();
    $chat_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching chat sessions: " . $e->getMessage());
}

// Get active session if specified
$active_session_id = $_GET['session'] ?? null;
$active_session = null;
$chat_messages = [];

if ($active_session_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT cs.*, u.first_name, u.last_name, u.email
            FROM chat_sessions cs
            JOIN users u ON cs.user_id = u.id
            WHERE cs.id = ?
        ");
        $stmt->execute([$active_session_id]);
        $active_session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($active_session) {
            // Get messages for this session
            $stmt = $pdo->prepare("
                SELECT cm.*, u.first_name, u.last_name 
                FROM chat_messages cm
                LEFT JOIN users u ON cm.sender_id = u.id
                WHERE cm.session_id = ?
                ORDER BY cm.created_at ASC
            ");
            $stmt->execute([$active_session_id]);
            $chat_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Mark admin messages as read
            $stmt = $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender_type = 'user'");
            $stmt->execute([$active_session_id]);
        }
    } catch (PDOException $e) {
        error_log("Error fetching active session: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Chat - Admin Panel</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/livechat.css">
</head>
<body>
    <!-- Admin Layout Wrapper -->
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content Area -->
        <div class="admin-content">
            <!-- Header -->
            <div class="admin-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" onclick="toggleMobileSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h4 class="mb-0">Live Chat Support</h4>
                        <small class="text-muted d-none d-md-block">Manage customer support conversations</small>
                    </div>
                </div>
                <div class="header-right">
                    <div class="header-stats d-none d-xl-flex">
                        <div class="stat-item">
                            <small class="text-muted">Pending</small>
                            <strong id="pendingCount"><?php echo count(array_filter($chat_sessions, fn($s) => $s['status'] === 'pending')); ?></strong>
                        </div>
                        <div class="stat-item">
                            <small class="text-muted">Active</small>
                            <strong id="activeCount"><?php echo count(array_filter($chat_sessions, fn($s) => $s['status'] === 'active')); ?></strong>
                        </div>
                    </div>
                    <button class="btn btn-admin-secondary btn-sm" onclick="refreshChatList()">
                        <i class="fas fa-sync-alt"></i>
                        <span class="d-none d-sm-inline">Refresh</span>
                    </button>
                    <div class="user-info">
                        <span class="text-muted d-none d-sm-inline">Support Agent:</span>
                        <strong><?php echo htmlspecialchars($currentUser['first_name']); ?>!</strong>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="admin-content-wrapper">
                <div class="content-container">

                    <!-- Chat Interface -->
                    <div class="admin-card chat-interface-card">
                        <div class="row g-0 h-100">
                            <!-- Chat Sessions List -->
                            <div class="col-lg-4 col-md-5 chat-sidebar">
                                <div class="chat-sidebar-header">
                                    <h6 class="mb-0">Chat Sessions</h6>
                                    <div class="chat-stats d-none d-md-flex">
                                        <span class="badge bg-warning me-2">
                                            <?php echo count(array_filter($chat_sessions, fn($s) => $s['status'] === 'pending')); ?> Pending
                                        </span>
                                        <span class="badge bg-success">
                                            <?php echo count(array_filter($chat_sessions, fn($s) => $s['status'] === 'active')); ?> Active
                                        </span>
                                    </div>
                                </div>
                        
                        <div class="chat-sessions-list" id="chatSessionsList">
                            <?php if (empty($chat_sessions)): ?>
                                <div class="no-sessions">
                                    <div class="no-sessions-icon">
                                        <i class="fas fa-comments"></i>
                                    </div>
                                    <h6>No Chat Sessions</h6>
                                    <p>No active chat sessions at the moment</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($chat_sessions as $session): ?>
                                    <div class="chat-session-item <?php echo $active_session_id == $session['id'] ? 'active' : ''; ?>" 
                                         onclick="openChatSession(<?php echo $session['id']; ?>)">
                                        <div class="session-header">
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div class="user-details">
                                                    <div class="user-name">
                                                        <?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?>
                                                    </div>
                                                    <div class="session-subject">
                                                        <?php echo htmlspecialchars($session['subject'] ?? 'General Inquiry'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="session-meta">
                                                <div class="session-status status-<?php echo $session['status']; ?>">
                                                    <?php echo ucfirst($session['status']); ?>
                                                </div>
                                                <div class="session-priority priority-<?php echo $session['priority']; ?>">
                                                    <?php echo ucfirst($session['priority']); ?>
                                                </div>
                                                <?php if ($session['unread_count'] > 0): ?>
                                                    <div class="unread-badge">
                                                        <?php echo $session['unread_count']; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($session['last_message']): ?>
                                            <div class="last-message">
                                                <?php echo htmlspecialchars(substr($session['last_message'], 0, 50)) . (strlen($session['last_message']) > 50 ? '...' : ''); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="session-time">
                                            <?php 
                                            if ($session['last_message_time']) {
                                                $time = new DateTime($session['last_message_time']);
                                                echo $time->format('M j, g:i A');
                                            } else {
                                                $time = new DateTime($session['created_at']);
                                                echo $time->format('M j, g:i A');
                                            }
                                            ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                            <!-- Chat Messages Area -->
                            <div class="col-lg-8 col-md-7 chat-main">
                        <?php if ($active_session): ?>
                            <!-- Active Chat -->
                            <div class="chat-header">
                                <div class="chat-user-info">
                                    <div class="user-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="user-details">
                                        <div class="user-name">
                                            <?php echo htmlspecialchars($active_session['first_name'] . ' ' . $active_session['last_name']); ?>
                                        </div>
                                        <div class="user-email">
                                            <?php echo htmlspecialchars($active_session['email']); ?>
                                        </div>
                                        <div class="session-info">
                                            <span class="session-subject"><?php echo htmlspecialchars($active_session['subject']); ?></span>
                                            <span class="session-priority priority-<?php echo $active_session['priority']; ?>">
                                                <?php echo ucfirst($active_session['priority']); ?> Priority
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="chat-actions">
                                    <?php if ($active_session['status'] === 'pending'): ?>
                                        <button class="btn btn-success btn-sm" onclick="acceptChat(<?php echo $active_session['id']; ?>)">
                                            <i class="fas fa-check"></i> Accept Chat
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm" onclick="declineChat(<?php echo $active_session['id']; ?>)">
                                            <i class="fas fa-times"></i> Decline
                                        </button>
                                    <?php elseif ($active_session['status'] === 'active'): ?>
                                        <button class="btn btn-outline-danger btn-sm" onclick="endChat(<?php echo $active_session['id']; ?>)">
                                            <i class="fas fa-times"></i> End Chat
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="chat-messages" id="chatMessages">
                                <?php foreach ($chat_messages as $message): ?>
                                    <div class="message <?php echo $message['sender_type']; ?>">
                                        <div class="message-content">
                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                        </div>
                                        <div class="message-meta">
                                            <span class="message-sender">
                                                <?php 
                                                if ($message['sender_type'] === 'admin') {
                                                    echo ($message['first_name'] ?? 'Admin') . ' ' . ($message['last_name'] ?? '');
                                                } elseif ($message['sender_type'] === 'user') {
                                                    echo $active_session['first_name'] . ' ' . $active_session['last_name'];
                                                } else {
                                                    echo 'System';
                                                }
                                                ?>
                                            </span>
                                            <span class="message-time">
                                                <?php 
                                                $time = new DateTime($message['created_at']);
                                                echo $time->format('g:i A');
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if ($active_session['status'] === 'active'): ?>
                                <div class="chat-input">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="messageInput" 
                                               placeholder="Type your message..." maxlength="500">
                                        <button class="btn btn-primary" type="button" onclick="sendMessage()">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- No Chat Selected -->
                            <div class="no-chat-selected">
                                <div class="no-chat-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <h4>Select a Chat Session</h4>
                                <p>Choose a chat session from the sidebar to start or continue a conversation</p>
                            </div>
                        <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="assets/js/livechat.js"></script>

    <?php if ($active_session): ?>
    <script>
        // Initialize chat with session ID
        window.activeSessionId = <?php echo $active_session['id']; ?>;
        window.sessionStatus = '<?php echo $active_session['status']; ?>';
    </script>
    <?php endif; ?>

    <script>
        // Mobile sidebar functions (same as dashboard)
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('sidebarOverlay');

            if (sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            } else {
                sidebar.classList.add('show');
                overlay.classList.add('show');
            }
        }

        // Close sidebar when clicking overlay
        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            toggleMobileSidebar();
        });

        // Auto-refresh chat stats in header
        function updateHeaderStats() {
            const pendingCount = document.querySelectorAll('.chat-session-item .session-status.status-pending').length;
            const activeCount = document.querySelectorAll('.chat-session-item .session-status.status-active').length;

            const pendingElement = document.getElementById('pendingCount');
            const activeElement = document.getElementById('activeCount');

            if (pendingElement) pendingElement.textContent = pendingCount;
            if (activeElement) activeElement.textContent = activeCount;
        }

        // Update stats every time chat list is refreshed
        setInterval(updateHeaderStats, 5000);

        // Mobile responsive chat interface
        function handleChatMobileView() {
            const chatSidebar = document.querySelector('.chat-sidebar');
            const chatMain = document.querySelector('.chat-main');
            const sessionItems = document.querySelectorAll('.chat-session-item');

            if (window.innerWidth <= 768) {
                // On mobile, hide sidebar when a chat is selected
                sessionItems.forEach(item => {
                    item.addEventListener('click', function() {
                        if (chatSidebar && chatMain) {
                            chatSidebar.style.display = 'none';
                            chatMain.style.display = 'block';

                            // Add back button to chat header
                            const chatHeader = document.querySelector('.chat-header');
                            if (chatHeader && !chatHeader.querySelector('.mobile-back-btn')) {
                                const backBtn = document.createElement('button');
                                backBtn.className = 'btn btn-sm btn-outline-secondary mobile-back-btn me-2';
                                backBtn.innerHTML = '<i class="fas fa-arrow-left"></i>';
                                backBtn.onclick = function() {
                                    chatSidebar.style.display = 'block';
                                    chatMain.style.display = 'none';
                                    this.remove();
                                };
                                chatHeader.insertBefore(backBtn, chatHeader.firstChild);
                            }
                        }
                    });
                });
            }
        }

        // Initialize mobile view handling
        document.addEventListener('DOMContentLoaded', function() {
            handleChatMobileView();

            // Re-initialize on window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    // Reset mobile view changes on desktop
                    const chatSidebar = document.querySelector('.chat-sidebar');
                    const chatMain = document.querySelector('.chat-main');
                    const backBtn = document.querySelector('.mobile-back-btn');

                    if (chatSidebar) chatSidebar.style.display = '';
                    if (chatMain) chatMain.style.display = '';
                    if (backBtn) backBtn.remove();
                } else {
                    handleChatMobileView();
                }
            });
        });
    </script>
</body>
</html>
