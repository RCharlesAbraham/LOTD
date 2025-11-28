<?php
/**
 * OTP Logs API
 * 
 * GET /admin/api/logs.php
 */

require_once __DIR__ . '/../../api/config/database.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $db = getDBConnection();
    
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    
    $where = '';
    $params = [];
    
    if ($type !== '' && in_array($type, ['whatsapp', 'sms'])) {
        $where = 'WHERE n.notification_type = ?';
        $params[] = $type;
    }
    
    // Count total
    $countSql = "SELECT COUNT(*) as count FROM notification_logs n {$where}";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['count'];
    
    // Get logs with entry info
    $sql = "SELECT n.*, e.name, e.whatsapp as entry_whatsapp, e.phone as entry_phone, e.entry_number 
            FROM notification_logs n 
            LEFT JOIN entries e ON n.entry_id = e.id 
            {$where}
            ORDER BY n.created_at DESC 
            LIMIT {$limit} OFFSET {$offset}";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $logs,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
