<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

$role = $_SESSION['role'];
$action = $_GET['action'] ?? '';
$req_role = $_GET['role'] ?? $role;

header('Content-Type: application/json');

try {
    // Ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
        for_role ENUM('Admin', 'Karyawan', 'All') DEFAULT 'All',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (\PDOException $e) { /* ignore */ }

if ($action === 'fetch') {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE for_role IN (?, 'All') ORDER BY created_at DESC LIMIT 15");
    $stmt->execute([$req_role]);
    $notifications = $stmt->fetchAll();
    
    $unread_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE for_role IN (?, 'All') AND is_read = 0");
    $unread_stmt->execute([$req_role]);
    $unread = $unread_stmt->fetchColumn();
    
    $result = [];
    foreach ($notifications as $n) {
        $diff = time() - strtotime($n['created_at']);
        if ($diff < 60) $ago = 'Baru saja';
        else if ($diff < 3600) $ago = floor($diff / 60) . ' menit lalu';
        else if ($diff < 86400) $ago = floor($diff / 3600) . ' jam lalu';
        else $ago = floor($diff / 86400) . ' hari lalu';
        
        $result[] = [
            'id' => (int)$n['id'],
            'title' => htmlspecialchars($n['title']),
            'message' => htmlspecialchars($n['message']),
            'type' => $n['type'],
            'is_read' => (bool)$n['is_read'],
            'time_ago' => $ago
        ];
    }
    echo json_encode(['notifications' => $result, 'unread' => (int)$unread]);
    exit;
}

if ($action === 'count') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE for_role IN (?, 'All') AND is_read = 0");
    $stmt->execute([$req_role]);
    echo json_encode(['count' => (int)$stmt->fetchColumn()]);
    exit;
}

if ($action === 'mark_read') {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE for_role IN (?, 'All')");
    $stmt->execute([$req_role]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
?>
