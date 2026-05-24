<?php
/**
 * Equipment Delete API
 * 
 * Deletes equipment record and optionally related history.
 * - Validates equipment ID exists
 * - Optionally deletes related history entries
 * - Creates history entry for deletion event
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
    $deleteHistory = getPost('delete-history', 'string');
    $deletedBy = getPost('deleted-by', 'string');
    
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
    
    // Get original equipment for logging
    $original = findJsonRow(DB_EQUIPMENT_PATH, function($item) use ($equipmentId) {
        return isset($item['id']) && $item['id'] === $equipmentId;
    });
    
    if (!$original) {
        notFoundResponse('Equipment not found');
    }
    
    if (empty($deletedBy)) {
        $deletedBy = getCurrentUser();
    }
    
    // Delete equipment record
    $deleted = false;
    deleteJsonRow(DB_EQUIPMENT_PATH, function($item) use ($equipmentId, &$deleted) {
        if (isset($item['id']) && $item['id'] === $equipmentId) {
            $deleted = true;
            return false; // Exclude this item (delete it)
        }
        return true; // Keep this item
    });
    
    if (!$deleted) {
        throw new Exception('Failed to delete equipment');
    }
    
    // Delete related history if requested
    $deletedHistoryCount = 0;
    if ($deleteHistory === 'true' || $deleteHistory === '1') {
        $deletedHistoryCount = deleteEquipmentHistory($equipmentId);
    }
    
    // Create deletion history entry
    $historyNotes = 'Equipment deleted: ' . $equipmentId;
    if ($deleteHistory === 'true' || $deleteHistory === '1') {
        $historyNotes .= ' (with ' . $deletedHistoryCount . ' history entries)';
    }
    
    $changes = array(
        array(
            'field' => 'Status',
            'old' => isset($original['status']) ? $original['status'] : '',
            'new' => 'DELETED'
        )
    );
    
    addHistory(
        $equipmentId,
        'Equipment Deleted',
        $historyNotes,
        $changes,
        $deletedBy
    );
    
    // Return success response
    successResponse(
        array(
            'id' => $equipmentId,
            'deleted' => true,
            'history_deleted' => $deletedHistoryCount
        ),
        'Equipment ' . $equipmentId . ' deleted successfully',
        200
    );
    
} catch (Exception $e) {
    serverErrorResponse($e->getMessage());
}
