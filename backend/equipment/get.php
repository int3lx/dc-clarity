<?php
/**
 * Equipment Get API
 * 
 * Retrieves single equipment record by ID.
 * - Accepts GET request with equipment ID parameter
 * - Returns equipment data in JSON format
 * - Returns 404 if equipment not found
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
    $equipmentId = getGet('id', 'string');
    
    if (empty($equipmentId)) {
        validationErrorResponse(
            array('id' => 'Equipment ID is required'),
            'Missing required parameter'
        );
    }
    
    // Verify equipment exists
    $existsCheck = validateEquipmentExists($equipmentId);
    if (!$existsCheck['valid']) {
        notFoundResponse($existsCheck['error']);
    }
    
    // Fetch equipment
    $equipment = findJsonRow(DB_EQUIPMENT_PATH, function($item) use ($equipmentId) {
        return isset($item['id']) && $item['id'] === $equipmentId;
    });
    
    if (!$equipment) {
        notFoundResponse('Equipment not found');
    }
    
    // Return success response
    successResponse(
        formatEquipmentForResponse($equipment),
        'Equipment retrieved successfully',
        200
    );
    
} catch (Exception $e) {
    serverErrorResponse($e->getMessage());
}
