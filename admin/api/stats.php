<?php
/**
 * Admin Stats API
 * 
 * GET /admin/api/stats.php
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
    
    // Total entries
    $stmt = $db->query("SELECT COUNT(*) as count FROM entries");
    $totalEntries = $stmt->fetch()['count'];
    
    // Verified entries
    $stmt = $db->query("SELECT COUNT(*) as count FROM entries WHERE is_verified = 1");
    $verifiedCount = $stmt->fetch()['count'];
    
    // Pending entries
    $pendingCount = $totalEntries - $verifiedCount;
    
    // Total OTPs sent
    $stmt = $db->query("SELECT COUNT(*) as count FROM otps");
    $otpsSent = $stmt->fetch()['count'];
    
    // Entries today
    $stmt = $db->query("SELECT COUNT(*) as count FROM entries WHERE DATE(created_at) = CURDATE()");
    $entriesToday = $stmt->fetch()['count'];
    
    // Verified today
    $stmt = $db->query("SELECT COUNT(*) as count FROM entries WHERE DATE(verified_at) = CURDATE()");
    $verifiedToday = $stmt->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_entries' => (int)$totalEntries,
            'verified_count' => (int)$verifiedCount,
            'pending_count' => (int)$pendingCount,
            'otps_sent' => (int)$otpsSent,
            'entries_today' => (int)$entriesToday,
            'verified_today' => (int)$verifiedToday
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
