<?php
require_once 'includes/session.php';
require_once 'db.php';

// Get current user
$currentUser = getCurrentUser();
$user_id = getUserId();

// Check if user has an active chat session
$active_session = null;
if (isLoggedIn()) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM chat_sessions WHERE user_id = ? AND status IN ('pending', 'active') ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $active_session = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error checking active chat session: " . $e->getMessage());
    }
}

// FAQ Data
$faqs = [
    [
        'question' => 'How do I track my order?',
        'answer' => 'You can track your order by logging into your account and visiting the "My Orders" section. You will also receive email notifications with tracking information once your order ships.'
    ],
    [
        'question' => 'What is your return policy?',
        'answer' => 'We offer a 30-day return policy for unworn items in original packaging. Items must be returned in the same condition as received. Please contact our support team to initiate a return.'
    ],
    [
        'question' => 'Do you offer free shipping?',
        'answer' => 'Yes! We offer free shipping on orders over ₱2,000 within the Philippines. For orders under ₱2,000, standard shipping rates apply.'
    ],
    [
        'question' => 'How do I know what size to order?',
        'answer' => 'Each product page includes a detailed size chart. We recommend measuring your foot and comparing it to our size guide. If you\'re between sizes, we generally recommend sizing up.'
    ],
    [
        'question' => 'What payment methods do you accept?',
        'answer' => 'We accept Cash on Delivery (COD), Bank Transfer, Credit/Debit Cards, and GCash. All payments are processed securely through our payment partners.'
    ],
    [
        'question' => 'How long does delivery take?',
        'answer' => 'Standard delivery takes 3-7 business days within Metro Manila and 5-10 business days for provincial areas. Express delivery options are available for faster shipping.'
    ],
    [
        'question' => 'Can I cancel my order?',
        'answer' => 'You can cancel your order within 24 hours of placing it, provided it hasn\'t been shipped yet. Please contact our support team or use the cancel option in your order details.'
    ],
    [
        'question' => 'Do you have physical stores?',
        'answer' => 'Currently, we operate as an online-only store. However, we\'re planning to open physical locations in the future. Follow our social media for updates!'
    ],
    [
        'question' => 'How do I contact customer support?',
        'answer' => 'You can reach our customer support team through the live chat feature below, email us at support@shoearizz.store, or call our hotline during business hours.'
    ],
    [
        'question' => 'Are your products authentic?',
        'answer' => 'Yes, all our products are 100% authentic. We work directly with authorized distributors and brands to ensure the quality and authenticity of every item we sell.'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact & Support - ShoeARizz</title>

    <!-- Include Navbar CSS and dependencies -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Custom Contact CSS -->
    <link rel="stylesheet" href="assets/css/contact.css">
</head>
<body>
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="header-content">
                        <h1 class="page-title">Contact & Support</h1>
                        <p class="page-subtitle">Get help with your orders, find answers to common questions, or chat with our support team</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="faq-section">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="section-header">
                        <h2 class="section-title">Frequently Asked Questions</h2>
                        <p class="section-subtitle">Find quick answers to the most common questions</p>
                    </div>

                    <div class="faq-container">
                        <div class="accordion" id="faqAccordion">
                            <?php foreach ($faqs as $index => $faq): ?>
                                <div class="accordion-item">
                                    <h3 class="accordion-header" id="heading<?php echo $index; ?>">
                                        <button class="accordion-button <?php echo $index === 0 ? '' : 'collapsed'; ?>"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#collapse<?php echo $index; ?>"
                                                aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                                                aria-controls="collapse<?php echo $index; ?>">
                                            <i class="fas fa-question-circle me-3"></i>
                                            <?php echo htmlspecialchars($faq['question']); ?>
                                        </button>
                                    </h3>
                                    <div id="collapse<?php echo $index; ?>"
                                         class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>"
                                         aria-labelledby="heading<?php echo $index; ?>"
                                         data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Chat Section -->
    <div class="live-chat-section">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="section-header">
                        <h2 class="section-title">Live Chat Support</h2>
                        <p class="section-subtitle">Get instant help from our support team</p>
                    </div>

                    <div class="chat-container">
                        <?php if (!isLoggedIn()): ?>
                            <!-- Not Logged In -->
                            <div class="chat-login-prompt">
                                <div class="chat-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <h4>Login Required</h4>
                                <p>Please log in to start a chat with our support team</p>
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login to Chat
                                </a>
                            </div>
                        <?php elseif ($active_session): ?>
                            <!-- Active Chat Session -->
                            <div class="active-chat-container" id="activeChatContainer">
                                <div class="chat-header">
                                    <div class="chat-status">
                                        <div class="status-indicator status-<?php echo $active_session['status']; ?>"></div>
                                        <span class="status-text">
                                            <?php
                                            switch($active_session['status']) {
                                                case 'pending': echo 'Waiting for support...'; break;
                                                case 'active': echo 'Connected with support'; break;
                                                default: echo ucfirst($active_session['status']);
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="chat-actions">
                                        <button class="btn btn-sm btn-outline-danger" onclick="endChat()">
                                            <i class="fas fa-times"></i> End Chat
                                        </button>
                                    </div>
                                </div>

                                <div class="chat-messages" id="chatMessages">
                                    <!-- Messages will be loaded here -->
                                </div>

                                <div class="chat-input-container" id="chatInputContainer" style="display: none;">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="messageInput" placeholder="Type your message..." maxlength="500">
                                        <button class="btn btn-primary" type="button" onclick="sendMessage()">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Start New Chat -->
                            <div class="start-chat-container">
                                <div class="chat-icon">
                                    <i class="fas fa-headset"></i>
                                </div>
                                <h4>Need Help?</h4>
                                <p>Start a conversation with our support team. We're here to help!</p>

                                <form id="startChatForm" class="start-chat-form">
                                    <div class="mb-3">
                                        <label for="chatSubject" class="form-label">What can we help you with?</label>
                                        <select class="form-select" id="chatSubject" name="subject" required>
                                            <option value="">Select a topic...</option>
                                            <option value="Order Inquiry">Order Inquiry</option>
                                            <option value="Product Question">Product Question</option>
                                            <option value="Shipping Issue">Shipping Issue</option>
                                            <option value="Return/Exchange">Return/Exchange</option>
                                            <option value="Payment Issue">Payment Issue</option>
                                            <option value="Technical Support">Technical Support</option>
                                            <option value="General Question">General Question</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="chatPriority" class="form-label">Priority</label>
                                        <select class="form-select" id="chatPriority" name="priority">
                                            <option value="low">Low - General inquiry</option>
                                            <option value="medium" selected>Medium - Need assistance</option>
                                            <option value="high">High - Urgent issue</option>
                                        </select>
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-comments me-2"></i>Start Chat
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="contact-info-section">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h5>Email Support</h5>
                        <p>support@shoearizz.store</p>
                        <small>Response within 24 hours</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h5>Phone Support</h5>
                        <p>+63 (02) 8123-4567</p>
                        <small>Mon-Fri, 9AM-6PM</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h5>Business Hours</h5>
                        <p>Monday - Friday</p>
                        <small>9:00 AM - 6:00 PM (PHT)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="assets/js/navbar.js"></script>
    <?php if (isLoggedIn()): ?>
    <script src="assets/js/global-cart.js"></script>
    <script src="assets/js/global-favorites.js"></script>
    <script src="assets/js/global-notifications.js"></script>
    <?php endif; ?>
    <script src="assets/js/contact.js"></script>

    <?php if ($active_session): ?>
    <script>
        // Initialize chat with session ID
        window.chatSessionId = <?php echo $active_session['id']; ?>;
        window.chatStatus = '<?php echo $active_session['status']; ?>';
    </script>
    <?php endif; ?>
</body>
</html>