<?php
/**
 * Export Entries to CSV
 * 
 * GET /admin/api/export.php
 */

require_once __DIR__ . '/../../api/config/database.php';

try {
    $db = getDBConnection();
    
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    
    $where = '';
    $params = [];
    
    if ($status !== '') {
        $where = 'WHERE is_verified = ?';
        $params[] = intval($status);
    }
    
    $stmt = $db->prepare("SELECT entry_number, name, whatsapp, phone, is_verified, verified_at, created_at FROM entries {$where} ORDER BY created_at DESC");
    $stmt->execute($params);
    $entries = $stmt->fetchAll();
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="lotd_entries_' . date('Y-m-d_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, ['Entry Number', 'Name', 'WhatsApp', 'Phone', 'Status', 'Verified At', 'Created At']);
    
    // Data rows
    foreach ($entries as $entry) {
        fputcsv($output, [
            $entry['entry_number'],
            $entry['name'],
            $entry['whatsapp'],
            $entry['phone'],
            $entry['is_verified'] ? 'Verified' : 'Pending',
            $entry['verified_at'] ?? '-',
            $entry['created_at']
        ]);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error exporting data';
}
