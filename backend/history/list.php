<?php
/**
 * History List API
 * 
 * Retrieves history entries for specific equipment.
 * - Accepts GET request with equipment_id parameter
 * - Returns filtered history entries
 * - Supports pagination (optional)
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../core/config.php';
require_once '../core/response.php';
require_once '../core/json-db.php';
require_once '../core/validator.php';
require_once '../core/helpers.php';

try {
    // Only accept GET
    if (!isGet()) {
        errorResponse('Only GET method is allowed', null, 405);
    }
    
    // Get equipment ID from query parameter
    $equipmentId = getGet('equipment_id', 'string');
    $limit = getGet('limit', 'int', null);
    $offset = getGet('offset', 'int', 0);
    
    if (empty($equipmentId)) {
        validationErrorResponse(
            array('equipment_id' => 'Equipment ID is required'),
            'Missing required parameter'
        );
    }
    
    // Verify equipment exists
    $existsCheck = validateEquipmentExists($equipmentId);
    if (!$existsCheck['valid']) {
        notFoundResponse($existsCheck['error']);
    }
    
    // Read history database
    try {
        $history = readJson(DB_HISTORY_PATH);
    } catch (Exception $e) {
        // Return empty list if database doesn't exist
        $history = array();
    }
    
    // Filter by equipment ID
    $filtered = array_filter($history, function($item) use ($equipmentId) {
        return isset($item['equipment_id']) && $item['equipment_id'] === $equipmentId;
    });
    
    // Sort by date (newest first)
    usort($filtered, function($a, $b) {
        $timeA = strtotime($a['date_created']);
        $timeB = strtotime($b['date_created']);
        return $timeB - $timeA; // Descending order
    });
    
    // Re-index array
    $filtered = array_values($filtered);
    
    // Calculate pagination
    $total = count($filtered);
    $hasMore = false;
    
    if ($limit !== null && $limit > 0) {
        $hasMore = ($offset + $limit) < $total;
        $filtered = array_slice($filtered, $offset, $limit);
    }
    
    // Format response data
    $responseData = array(
        'history' => array_map('formatHistoryEntry', $filtered),
        'equipment_id' => $equipmentId,
        'total' => $total,
        'count' => count($filtered),
        'offset' => $offset
    );
    
    if ($limit !== null && $limit > 0) {
        $responseData['limit'] = $limit;
        $responseData['has_more'] = $hasMore;
    }
    
    // Return success response
    successResponse(
        $responseData,
        'History retrieved successfully',
        200
    );
    
} catch (Exception $e) {
    serverErrorResponse($e->getMessage());
}
