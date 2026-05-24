<?php
/**
 * History Delete API
 * 
 * Deletes history entry by ID.
 * - Validates history ID exists
 * - Deletes history entry from database
 * - Returns standardized JSON response
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../core/config.php';
require_once '../core/response.php';
require_once '../core/json-db.php';
require_once '../core/history.php';
require_once '../core/helpers.php';

try {
    // Only accept POST
    if (!isPost()) {
        errorResponse('Only POST method is allowed', null, 405);
    }
    
    // Get history ID
    $historyId = getPost('history_id', 'string');
    $deletedBy = getPost('deleted_by', 'string');
    
    if (empty($historyId)) {
        validationErrorResponse(
            array('history_id' => 'History ID is required'),
            'Missing required field'
        );
    }
    
    if (empty($deletedBy)) {
        $deletedBy = getCurrentUser();
    }
    
    // Verify history entry exists
    $historyEntry = findJsonRow(DB_HISTORY_PATH, function($item) use ($historyId) {
        return isset($item['history_id']) && $item['history_id'] === $historyId;
    });
    
    if (!$historyEntry) {
        notFoundResponse('History entry not found');
    }
    
    // Delete history entry
    $deleted = deleteHistory($historyId);
    
    if (!$deleted) {
        throw new Exception('Failed to delete history entry');
    }
    
    // Return success response
    successResponse(
        array('history_id' => $historyId, 'deleted' => true),
        'History entry deleted successfully',
        200
    );
    
} catch (Exception $e) {
    serverErrorResponse($e->getMessage());
}
