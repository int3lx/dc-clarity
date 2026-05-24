<?php
/**
 * Equipment Create API
 * 
 * Creates new equipment in the database.
 * - Validates all input data
 * - Generates unique equipment ID (backend only)
 * - Checks for duplicate serial numbers
 * - Creates initial history entry
 * - Returns standardized JSON response
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../core/config.php';
require_once '../core/response.php';
require_once '../core/json-db.php';
require_once '../core/validator.php';
require_once '../core/sequence.php';
require_once '../core/history.php';
require_once '../core/helpers.php';

try {
    // Only accept POST
    if (!isPost()) {
        errorResponse('Only POST method is allowed', null, 405);
    }
    
    // Get and sanitize input data
    $dc = getPost('dc', 'string');
    $type = getPost('type', 'string');
    $brand = getPost('brand', 'string');
    $spec = getPost('spec', 'string');
    $status = getPost('status', 'string', 'Active');
    $location = getPost('location', 'string', '');
    $supplier = getPost('supplier', 'string', '');
    $vendor = getPost('vendor', 'string', '');
    $sn = getPost('sn', 'string');
    $assetTag = getPost('asset-tag', 'string', '');
    $lifespan = getPost('lifespan', 'int', 0);
    $installed = getPost('installed', 'string', '');
    $latestPm = getPost('latest-pm', 'string', '');
    $notes = getPost('notes', 'string', '');
    $createdBy = getPost('created-by', 'string');
    
    if (empty($createdBy)) {
        $createdBy = getCurrentUser();
    }
    
    // Validate required fields
    $validations = array(
        'dc' => validateDCLocation($dc),
        'type' => validateEquipmentType($type),
        'brand' => validateRequired($brand, 'Brand'),
        'spec' => validateRequired($spec, 'Specification'),
        'sn' => validateSerialNumber($sn)
    );
    
    $validation = collectValidationErrors($validations);
    if (!$validation['valid']) {
        validationErrorResponse($validation['errors'], 'Validation failed');
    }
    
    // Check for duplicate serial number
    $dupCheck = validateDuplicateSerialNumber($sn);
    if (!$dupCheck['valid']) {
        validationErrorResponse(
            array('sn' => $dupCheck['error']),
            'Duplicate serial number'
        );
    }
    
    // Generate unique equipment ID
    $equipmentId = generateEquipmentId($dc, $type);
    
    // Verify uniqueness (double-check)
    if (!isEquipmentIdUnique($equipmentId)) {
        throw new Exception('Generated Equipment ID already exists. Please try again.');
    }
    
    // Build equipment record
    $equipment = array(
        'id' => $equipmentId,
        'type' => $type,
        'brand' => $brand,
        'spec' => $spec,
        'status' => $status,
        'location' => $location,
        'dc' => $dc,
        'supplier' => $supplier,
        'vendor' => $vendor,
        'sn' => $sn,
        'asset-tag' => $assetTag,
        'lifespan' => $lifespan,
        'installed' => $installed,
        'latest-pm' => $latestPm,
        'notes' => $notes,
        'created-by' => $createdBy,
        'date-created' => getCurrentTimestamp()
    );
    
    // Write to database
    appendJson(DB_EQUIPMENT_PATH, $equipment);
    
    // Create history entry
    $historyNotes = "Equipment created: $equipmentId";
    $changes = array();
    foreach ($equipment as $key => $value) {
        if ($key !== 'id' && $key !== 'date-created' && $key !== 'created-by') {
            $changes[] = array(
                'field' => $key,
                'old' => '',
                'new' => $value
            );
        }
    }
    
    addHistory(
        $equipmentId,
        'Equipment Created',
        $historyNotes,
        $changes,
        $createdBy
    );
    
    // Return success response
    successResponse(
        formatEquipmentForResponse($equipment),
        'Equipment ' . $equipmentId . ' successfully created',
        201
    );
    
} catch (Exception $e) {
    serverErrorResponse($e->getMessage());
}
