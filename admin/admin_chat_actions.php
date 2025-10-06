<?php
require_once 'includes/admin_auth.php';
require_once '../db.php';

header('Content-Type: application/json');

// Check admin authentication
if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
    exit();
}

$admin_id = getAdminId();
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_session_list':
            handleGetSessionList($pdo);
            break;
            
        case 'get_messages':
            handleGetMessages($pdo, $admin_id);
            break;
            
        case 'send_message':
            handleSendMessage($pdo, $admin_id);
            break;
            
        case 'accept_chat':
            handleAcceptChat($pdo, $admin_id);
            break;
            
        case 'decline_chat':
            handleDeclineChat($pdo, $admin_id);
            break;
            
        case 'end_chat':
            handleEndChat($pdo, $admin_id);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Admin chat action error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}

function handleGetSessionList($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT cs.id, cs.status,
                   (SELECT COUNT(*) FROM chat_messages WHERE session_id = cs.id AND sender_type = 'user' AND is_read = 0) as unread_count
            FROM chat_sessions cs
            WHERE cs.status IN ('pending', 'active', 'closed')
            ORDER BY cs.last_activity DESC
        ");
        $stmt->execute();
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'sessions' => $sessions
        ]);
    } catch (PDOException $e) {
        error_log("Error getting session list: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to get session list']);
    }
}

function handleGetMessages($pdo, $admin_id) {
    $session_id = (int)($_POST['session_id'] ?? 0);
    $last_message_id = (int)($_POST['last_message_id'] ?? 0);
    
    if ($session_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
        return;
    }
    
    // Get session status
    $stmt = $pdo->prepare("SELECT status FROM chat_sessions WHERE id = ?");
    $stmt->execute([$session_id]);
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
    
    // Mark user messages as read
    if (!empty($messages)) {
        $stmt = $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender_type = 'user' AND is_read = 0");
        $stmt->execute([$session_id]);
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'session_status' => $session['status']
    ]);
}

function handleSendMessage($pdo, $admin_id) {
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
    
    // Verify session exists and is active
    $stmt = $pdo->prepare("SELECT status FROM chat_sessions WHERE id = ? AND status = 'active'");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found or not active']);
        return;
    }
    
    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (session_id, sender_id, sender_type, message, created_at) 
        VALUES (?, ?, 'admin', ?, NOW())
    ");
    $stmt->execute([$session_id, $admin_id, $message]);
    
    // Update session last activity
    $stmt = $pdo->prepare("UPDATE chat_sessions SET last_activity = NOW() WHERE id = ?");
    $stmt->execute([$session_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully'
    ]);
}

function handleAcceptChat($pdo, $admin_id) {
    $session_id = (int)($_POST['session_id'] ?? 0);
    
    if ($session_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
        return;
    }
    
    // Verify session is pending
    $stmt = $pdo->prepare("SELECT status, user_id FROM chat_sessions WHERE id = ? AND status = 'pending'");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found or not pending']);
        return;
    }
    
    // Accept the session
    $stmt = $pdo->prepare("
        UPDATE chat_sessions 
        SET status = 'active', admin_id = ?, accepted_at = NOW(), last_activity = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$admin_id, $session_id]);
    
    // Add system message
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (session_id, sender_id, sender_type, message, message_type, created_at) 
        VALUES (?, ?, 'system', 'Support agent has joined the chat.', 'system', NOW())
    ");
    $stmt->execute([$session_id, $admin_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Chat accepted successfully'
    ]);
}

function handleDeclineChat($pdo, $admin_id) {
    $session_id = (int)($_POST['session_id'] ?? 0);
    
    if ($session_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
        return;
    }
    
    // Verify session is pending
    $stmt = $pdo->prepare("SELECT status, user_id FROM chat_sessions WHERE id = ? AND status = 'pending'");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found or not pending']);
        return;
    }
    
    // Decline the session
    $stmt = $pdo->prepare("
        UPDATE chat_sessions 
        SET status = 'declined', admin_id = ?, closed_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$admin_id, $session_id]);
    
    // Add system message
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (session_id, sender_id, sender_type, message, message_type, created_at) 
        VALUES (?, ?, 'system', 'Chat request was declined. Please try again later or contact support via email.', 'system', NOW())
    ");
    $stmt->execute([$session_id, $admin_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Chat declined successfully'
    ]);
}

function handleEndChat($pdo, $admin_id) {
    $session_id = (int)($_POST['session_id'] ?? 0);
    
    if ($session_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
        return;
    }
    
    // Verify session exists and is active
    $stmt = $pdo->prepare("SELECT status, user_id FROM chat_sessions WHERE id = ? AND status = 'active'");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found or not active']);
        return;
    }
    
    // End the session
    $stmt = $pdo->prepare("UPDATE chat_sessions SET status = 'closed', closed_at = NOW() WHERE id = ?");
    $stmt->execute([$session_id]);
    
    // Add system message
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (session_id, sender_id, sender_type, message, message_type, created_at) 
        VALUES (?, ?, 'system', 'Chat session ended by support agent.', 'system', NOW())
    ");
    $stmt->execute([$session_id, $admin_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Chat ended successfully'
    ]);
}
?>
