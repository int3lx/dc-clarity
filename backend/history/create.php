<?php
/**
 * History Create API
 * 
 * Creates history entry for equipment event.
 * - Validates equipment ID exists
 * - Validates event type
 * - Creates history entry with timestamp
 * - Returns standardized JSON response
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../core/config.php';
require_once '../core/response.php';
require_once '../core/json-db.php';
require_once '../core/validator.php';
require_once '../core/history.php';
require_once '../core/helpers.php';

try {
    // Only accept POST
    if (!isPost()) {
        errorResponse('Only POST method is allowed', null, 405);
    }
    
    // Get input
    $equipmentId = getPost('equipment_id', 'string');
    $event = getPost('event', 'string');
    $notes = getPost('notes', 'string', '');
    $createdBy = getPost('created_by', 'string');
    
    if (empty($createdBy)) {
        $createdBy = getCurrentUser();
    }
    
    // Validate required fields
    $validations = array(
        'equipment_id' => validateRequired($equipmentId, 'Equipment ID'),
        'event' => validateRequired($event, 'Event')
    );
    
    $validation = collectValidationErrors($validations);
    if (!$validation['valid']) {
        validationErrorResponse($validation['errors'], 'Validation failed');
    }
    
    // Verify equipment exists
    $existsCheck = validateEquipmentExists($equipmentId);
    if (!$existsCheck['valid']) {
        notFoundResponse($existsCheck['error']);
    }
    
    // Create history entry
    $entry = addHistory(
        $equipmentId,
        $event,
        $notes,
        array(),
        $createdBy
    );
    
    // Return success response
    successResponse(
        formatHistoryEntry($entry),
        'History entry created successfully',
        201
    );
    
} catch (Exception $e) {
    serverErrorResponse($e->getMessage());
}
