<?php
require_once 'includes/session.php';
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to use chat']);
    exit();
}

$user_id = getUserId();
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'start_chat':
            handleStartChat($pdo, $user_id);
            break;
            
        case 'get_messages':
            handleGetMessages($pdo, $user_id);
            break;
            
        case 'send_message':
            handleSendMessage($pdo, $user_id);
            break;
            
        case 'end_chat':
            handleEndChat($pdo, $user_id);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Chat action error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}

function handleStartChat($pdo, $user_id) {
    $subject = $_POST['subject'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    
    // Validate inputs
    if (empty($subject)) {
        echo json_encode(['success' => false, 'message' => 'Please select a topic']);
        return;
    }
    
    if (!in_array($priority, ['low', 'medium', 'high'])) {
        $priority = 'medium';
    }
    
    // Check if user already has an active chat
    $stmt = $pdo->prepare("SELECT id FROM chat_sessions WHERE user_id = ? AND status IN ('pending', 'active')");
    $stmt->execute([$user_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You already have an active chat session']);
        return;
    }
    
    // Create new chat session
    $stmt = $pdo->prepare("
        INSERT INTO chat_sessions (user_id, subject, priority, status, created_at) 
        VALUES (?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$user_id, $subject, $priority]);
    
    $session_id = $pdo->lastInsertId();
    
    // Add system message
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (session_id, sender_id, sender_type, message, message_type, created_at) 
        VALUES (?, ?, 'system', ?, 'system', NOW())
    ");
    $welcome_message = "Chat session started. Please wait while we connect you with a support agent.";
    $stmt->execute([$session_id, $user_id, $welcome_message]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Chat session started successfully',
        'session_id' => $session_id
    ]);
}

function handleGetMessages($pdo, $user_id) {
    $session_id = (int)($_POST['session_id'] ?? 0);
    $last_message_id = (int)($_POST['last_message_id'] ?? 0);
    
    if ($session_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
        return;
    }
    
    // Verify session belongs to user
    $stmt = $pdo->prepare("SELECT status FROM chat_sessions WHERE id = ? AND user_id = ?");
    $stmt->execute([$session_id, $user_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        return;
    }
    
    // Get messages newer than last_message_id
    $stmt = $pdo->prepare("
        SELECT cm.*, u.first_name, u.last_name 
        FROM chat_messages cm
        LEFT JOIN users u ON cm.sender_id = u.id
        WHERE cm.session_id = ? AND cm.id > ?
        ORDER BY cm.created_at ASC
    ");
    $stmt->execute([$session_id, $last_message_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format messages
    foreach ($messages as &$message) {
        if ($message['sender_type'] === 'admin') {
            $message['sender_name'] = ($message['first_name'] ?? 'Support') . ' ' . ($message['last_name'] ?? 'Agent');
        } else {
            $message['sender_name'] = 'You';
        }
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'session_status' => $session['status']
    ]);
}

function handleSendMessage($pdo, $user_id) {
    $session_id = (int)($_POST['session_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    
    if ($session_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
        return;
    }
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        return;
    }
    
    if (strlen($message) > 500) {
        echo json_encode(['success' => false, 'message' => 'Message too long (max 500 characters)']);
        return;
    }
    
    // Verify session belongs to user and is active
    $stmt = $pdo->prepare("SELECT status FROM chat_sessions WHERE id = ? AND user_id = ? AND status IN ('pending', 'active')");
    $stmt->execute([$session_id, $user_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found or not active']);
        return;
    }
    
    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (session_id, sender_id, sender_type, message, created_at) 
        VALUES (?, ?, 'user', ?, NOW())
    ");
    $stmt->execute([$session_id, $user_id, $message]);
    
    // Update session last activity
    $stmt = $pdo->prepare("UPDATE chat_sessions SET last_activity = NOW() WHERE id = ?");
    $stmt->execute([$session_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully'
    ]);
}

function handleEndChat($pdo, $user_id) {
    $session_id = (int)($_POST['session_id'] ?? 0);
    
    if ($session_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
        return;
    }
    
    // Verify session belongs to user
    $stmt = $pdo->prepare("SELECT status FROM chat_sessions WHERE id = ? AND user_id = ?");
    $stmt->execute([$session_id, $user_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        return;
    }
    
    if ($session['status'] === 'closed') {
        echo json_encode(['success' => false, 'message' => 'Chat is already closed']);
        return;
    }
    
    // Close the session
    $stmt = $pdo->prepare("UPDATE chat_sessions SET status = 'closed', closed_at = NOW() WHERE id = ?");
    $stmt->execute([$session_id]);
    
    // Add system message
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (session_id, sender_id, sender_type, message, message_type, created_at) 
        VALUES (?, ?, 'system', 'Chat session ended by user.', 'system', NOW())
    ");
    $stmt->execute([$session_id, $user_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Chat session ended successfully'
    ]);
}
?>
