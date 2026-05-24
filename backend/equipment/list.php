<?php
/**
 * Equipment List API
 * 
 * Retrieves list of equipment with optional filters.
 * - Accepts GET request with optional dc and type filters
 * - Returns filtered equipment list in JSON format
 * - Supports pagination (optional)
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../core/config.php';
require_once '../core/response.php';
require_once '../core/json-db.php';
require_once '../core/helpers.php';

try {
    // Only accept GET
    if (!isGet()) {
        errorResponse('Only GET method is allowed', null, 405);
    }
    
    // Get filter parameters
    $dc = getGet('dc', 'string', '');
    $type = getGet('type', 'string', '');
    $status = getGet('status', 'string', '');
    $limit = getGet('limit', 'int', null);
    $offset = getGet('offset', 'int', 0);
    
    // Read equipment database
    try {
        $equipment = readJson(DB_EQUIPMENT_PATH);
    } catch (Exception $e) {
        // Return empty list if database doesn't exist
        $equipment = array();
    }
    
    // Apply filters
    $filtered = $equipment;
    
    if (!empty($dc)) {
        $filtered = array_filter($filtered, function($item) use ($dc) {
            return isset($item['dc']) && strtoupper($item['dc']) === strtoupper($dc);
        });
    }
    
    if (!empty($type)) {
        $filtered = array_filter($filtered, function($item) use ($type) {
            return isset($item['type']) && strtoupper($item['type']) === strtoupper($type);
        });
    }
    
    if (!empty($status)) {
        $filtered = array_filter($filtered, function($item) use ($status) {
            return isset($item['status']) && strtoupper($item['status']) === strtoupper($status);
        });
    }
    
    // Re-index array after filtering
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
        'equipment' => array_map('formatEquipmentForResponse', $filtered),
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
        'Equipment list retrieved successfully',
        200
    );
    
} catch (Exception $e) {
    serverErrorResponse($e->getMessage());
}
