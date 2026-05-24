<?php
/**
 * Equipment Update API
 * 
 * Updates existing equipment record.
 * - Validates equipment ID exists
 * - Validates only editable fields
 * - Detects changes and creates history entry
 * - Prevents updates with no changes
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
    
    // Get equipment ID
    $equipmentId = getPost('id', 'string');
    
    if (empty($equipmentId)) {
        validationErrorResponse(
            array('id' => 'Equipment ID is required'),
            'Missing required field'
        );
    }
    
    // Verify equipment exists
    $existsCheck = validateEquipmentExists($equipmentId);
    if (!$existsCheck['valid']) {
        notFoundResponse($existsCheck['error']);
    }
    
    // Get original equipment data
    $original = findJsonRow(DB_EQUIPMENT_PATH, function($item) use ($equipmentId) {
        return isset($item['id']) && $item['id'] === $equipmentId;
    });
    
    if (!$original) {
        notFoundResponse('Equipment not found');
    }
    
    // Get user
    $updatedBy = getPost('updated-by', 'string');
    if (empty($updatedBy)) {
        $updatedBy = getCurrentUser();
    }
    
    // Prepare updates - only allow editable fields
    $updates = array(
        'updated-by' => $updatedBy,
        'date-updated' => getCurrentTimestamp()
    );
    
    $editableFields = array(
        'brand', 'spec', 'status', 'location', 'supplier', 'vendor',
        'sn', 'asset-tag', 'lifespan', 'installed', 'latest-pm', 'notes'
    );
    
    $fieldErrors = array();
    $providedUpdates = array();
    
    foreach ($editableFields as $field) {
        if (isset($_POST[$field])) {
            $value = getPost($field, 'string');
            $providedUpdates[$field] = $value;
            
            // Validate serial number if it's being updated and changed
            if ($field === 'sn' && $value !== $original['sn']) {
                $dupCheck = validateDuplicateSerialNumber($value, $equipmentId);
                if (!$dupCheck['valid']) {
                    $fieldErrors['sn'] = $dupCheck['error'];
                }
            }
            
            if (!isset($fieldErrors[$field])) {
                $updates[$field] = $value;
            }
        }
    }
    
    if (!empty($fieldErrors)) {
        validationErrorResponse($fieldErrors, 'Validation failed');
    }
    
    // Detect changes
    $changes = detectChanges($original, $updates, $editableFields);
    
    // If no changes, return message
    if (empty($changes)) {
        successResponse(
            formatEquipmentForResponse($original),
            'No changes detected',
            200
        );
    }
    
    // Update record
    $updated = updateJsonRow(
        DB_EQUIPMENT_PATH,
        function($item) use ($equipmentId) {
            return isset($item['id']) && $item['id'] === $equipmentId;
        },
        $updates
    );
    
    if (!$updated) {
        throw new Exception('Failed to update equipment');
    }
    
    // Generate change notes
    $changeNotes = generateChangeNotes($changes);
    
    // Create history entry
    addHistory(
        $equipmentId,
        'Detail Update',
        $changeNotes,
        $changes,
        $updatedBy
    );
    
    // Return success response
    successResponse(
        formatEquipmentForResponse($updated),
        'Equipment updated successfully',
        200
    );
    
} catch (Exception $e) {
    serverErrorResponse($e->getMessage());
}
