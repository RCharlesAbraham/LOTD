<?php
/**
 * Admin Entries API
 * 
 * GET /admin/api/entries.php - List entries
 * GET /admin/api/entries.php?id=1 - Get single entry
 * DELETE /admin/api/entries.php?id=1 - Delete entry
 * DELETE /admin/api/entries.php (body: {ids: [1,2,3]}) - Bulk delete
 */

require_once __DIR__ . '/../../api/config/database.php';

setCorsHeaders();

$db = getDBConnection();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGet($db);
        break;
    case 'DELETE':
        handleDelete($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function handleGet($db) {
    // Single entry
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $db->prepare("SELECT * FROM entries WHERE id = ?");
        $stmt->execute([$id]);
        $entry = $stmt->fetch();
        
        if ($entry) {
            echo json_encode(['success' => true, 'data' => $entry]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Entry not found']);
        }
        return;
    }
    
    // List entries with pagination and filters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created_at';
    $sortOrder = isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'ASC' ? 'ASC' : 'DESC';
    
    // Validate sort column
    $allowedSorts = ['created_at', 'name', 'email', 'phone', 'entry_number', 'verified_at'];
    if (!in_array($sortBy, $allowedSorts)) {
        $sortBy = 'created_at';
    }
    
    // Build query
    $where = [];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR entry_number LIKE ?)";
        $searchParam = "%{$search}%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    }
    
    if ($status !== '') {
        $where[] = "is_verified = ?";
        $params[] = intval($status);
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count
    $countSql = "SELECT COUNT(*) as count FROM entries {$whereClause}";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['count'];
    
    // Get entries
    $sql = "SELECT * FROM entries {$whereClause} ORDER BY {$sortBy} {$sortOrder} LIMIT {$limit} OFFSET {$offset}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $entries = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $entries,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
}

function handleDelete($db) {
    // Single delete
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $db->prepare("DELETE FROM entries WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Entry deleted successfully'
        ]);
        return;
    }
    
    // Bulk delete
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['ids']) && is_array($input['ids'])) {
        $ids = array_map('intval', $input['ids']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        $stmt = $db->prepare("DELETE FROM entries WHERE id IN ({$placeholders})");
        $stmt->execute($ids);
        
        echo json_encode([
            'success' => true,
            'message' => count($ids) . ' entries deleted successfully'
        ]);
        return;
    }
    
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No ID provided']);
}
