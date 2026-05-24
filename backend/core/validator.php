<?php
/**
 * Validator
 * 
 * Provides reusable validation functions for equipment data.
 * All user input MUST be validated on the backend.
 */

/**
 * Validate required field
 * 
 * @param mixed $value Field value
 * @param string $fieldName Field name for error message
 * @return array Array with 'valid' bool and 'error' message
 */
function validateRequired($value, $fieldName = 'Field') {
    $value = is_string($value) ? trim($value) : $value;
    
    if (empty($value) && $value !== 0 && $value !== '0') {
        return array(
            'valid' => false,
            'error' => $fieldName . ' is required'
        );
    }
    
    return array('valid' => true);
}

/**
 * Validate string length
 * 
 * @param string $value Field value
 * @param int $minLength Minimum length
 * @param int $maxLength Maximum length
 * @param string $fieldName Field name
 * @return array Validation result
 */
function validateStringLength($value, $minLength = 1, $maxLength = 255, $fieldName = 'Field') {
    $value = is_string($value) ? trim($value) : '';
    $length = strlen($value);
    
    if ($length < $minLength) {
        return array(
            'valid' => false,
            'error' => $fieldName . ' must be at least ' . $minLength . ' characters'
        );
    }
    
    if ($length > $maxLength) {
        return array(
            'valid' => false,
            'error' => $fieldName . ' must not exceed ' . $maxLength . ' characters'
        );
    }
    
    return array('valid' => true);
}

/**
 * Validate numeric value
 * 
 * @param mixed $value Field value
 * @param int $min Minimum value
 * @param int $max Maximum value
 * @param string $fieldName Field name
 * @return array Validation result
 */
function validateNumeric($value, $min = null, $max = null, $fieldName = 'Field') {
    if (!is_numeric($value)) {
        return array(
            'valid' => false,
            'error' => $fieldName . ' must be numeric'
        );
    }
    
    $numValue = (int)$value;
    
    if ($min !== null && $numValue < $min) {
        return array(
            'valid' => false,
            'error' => $fieldName . ' must be at least ' . $min
        );
    }
    
    if ($max !== null && $numValue > $max) {
        return array(
            'valid' => false,
            'error' => $fieldName . ' must not exceed ' . $max
        );
    }
    
    return array('valid' => true);
}

/**
 * Validate equipment type
 * 
 * @param string $type Equipment type value
 * @return array Validation result
 */
function validateEquipmentType($type) {
    $type = is_string($type) ? trim($type) : '';
    
    $validTypes = array('GENSET', 'UPS', 'CRAC', 'CHILLER', 'FIRE');
    
    if (empty($type)) {
        return array(
            'valid' => false,
            'error' => 'Equipment Type is required'
        );
    }
    
    if (!in_array($type, $validTypes)) {
        return array(
            'valid' => false,
            'error' => 'Invalid Equipment Type: ' . htmlspecialchars($type)
        );
    }
    
    return array('valid' => true);
}

/**
 * Validate DC location
 * 
 * @param string $dc DC location value
 * @return array Validation result
 */
function validateDCLocation($dc) {
    $dc = is_string($dc) ? trim($dc) : '';
    
    $validLocations = array('BFDC', 'KJDC', 'KVDC', 'SJDC', 'KTEDC', 'IPEDC', 'KNEDC', 'SNEDC', 'KGEDC', 'KKEDC');
    
    if (empty($dc)) {
        return array(
            'valid' => false,
            'error' => 'DC Location is required'
        );
    }
    
    if (!in_array($dc, $validLocations)) {
        return array(
            'valid' => false,
            'error' => 'Invalid DC Location: ' . htmlspecialchars($dc)
        );
    }
    
    return array('valid' => true);
}

/**
 * Validate serial number format
 * 
 * @param string $sn Serial number
 * @return array Validation result
 */
function validateSerialNumber($sn) {
    $sn = is_string($sn) ? trim($sn) : '';
    
    // Serial number validation: alphanumeric, 5-50 characters
    if (empty($sn)) {
        return array(
            'valid' => false,
            'error' => 'Serial Number is required'
        );
    }
    
    if (strlen($sn) < 5 || strlen($sn) > 50) {
        return array(
            'valid' => false,
            'error' => 'Serial Number must be between 5 and 50 characters'
        );
    }
    
    return array('valid' => true);
}

/**
 * Validate equipment ID format
 * Format: {DC}-{TYPE}-{5-digit-sequence}
 * 
 * @param string $id Equipment ID
 * @return array Validation result
 */
function validateEquipmentId($id) {
    $id = is_string($id) ? trim($id) : '';
    
    // Pattern: DC1-SERVER-00001
    $pattern = '/^[A-Z]{2,6}-[A-Z]{2,10}-\d{5}$/';
    
    if (empty($id)) {
        return array(
            'valid' => false,
            'error' => 'Equipment ID is required'
        );
    }
    
    if (!preg_match($pattern, $id)) {
        return array(
            'valid' => false,
            'error' => 'Invalid Equipment ID format'
        );
    }
    
    return array('valid' => true);
}

/**
 * Validate date format (DD/MM/YYYY or HH:MM DD/MM/YYYY)
 * 
 * @param string $date Date string
 * @param bool $requiresTime Whether time is required
 * @return array Validation result
 */
function validateDate($date, $requiresTime = false) {
    $date = is_string($date) ? trim($date) : '';
    
    if (empty($date)) {
        return array(
            'valid' => false,
            'error' => 'Date is required'
        );
    }
    
    if ($requiresTime) {
        // Pattern: HH:MM DD/MM/YYYY
        $pattern = '/^\d{2}:\d{2}\s\d{2}\/\d{2}\/\d{4}$/';
    } else {
        // Pattern: DD/MM/YYYY
        $pattern = '/^\d{2}\/\d{2}\/\d{4}$/';
    }
    
    if (!preg_match($pattern, $date)) {
        return array(
            'valid' => false,
            'error' => 'Invalid date format'
        );
    }
    
    return array('valid' => true);
}

/**
 * Validate duplicate serial number in equipment database
 * 
 * @param string $serialNumber Serial number to check
 * @param string $excludeEquipmentId Equipment ID to exclude (for updates)
 * @return array Validation result
 */
function validateDuplicateSerialNumber($serialNumber, $excludeEquipmentId = null) {
    require_once __DIR__ . '/json-db.php';
    require_once __DIR__ . '/config.php';
    
    try {
        $equipment = readJson(DB_EQUIPMENT_PATH);
        
        foreach ($equipment as $item) {
            // Skip if this is the same equipment being updated
            if ($excludeEquipmentId && isset($item['id']) && $item['id'] === $excludeEquipmentId) {
                continue;
            }
            
            // Check if serial number matches
            if (isset($item['sn']) && strtoupper(trim($item['sn'])) === strtoupper(trim($serialNumber))) {
                return array(
                    'valid' => false,
                    'error' => 'Serial Number already exists in database',
                    'duplicate_id' => $item['id']
                );
            }
        }
        
        return array('valid' => true);
    } catch (Exception $e) {
        return array(
            'valid' => false,
            'error' => 'Error checking duplicate: ' . $e->getMessage()
        );
    }
}

/**
 * Validate that equipment ID exists
 * 
 * @param string $equipmentId Equipment ID to check
 * @return array Validation result
 */
function validateEquipmentExists($equipmentId) {
    require_once __DIR__ . '/json-db.php';
    require_once __DIR__ . '/config.php';
    
    try {
        $equipment = findJsonRow(DB_EQUIPMENT_PATH, function($item) use ($equipmentId) {
            return isset($item['id']) && $item['id'] === $equipmentId;
        });
        
        if ($equipment === null) {
            return array(
                'valid' => false,
                'error' => 'Equipment ID not found'
            );
        }
        
        return array('valid' => true);
    } catch (Exception $e) {
        return array(
            'valid' => false,
            'error' => 'Error checking equipment: ' . $e->getMessage()
        );
    }
}

/**
 * Run multiple validations and collect errors
 * 
 * @param array $validations Array of validation results
 * @return array Array with 'valid' bool and 'errors' array
 */
function collectValidationErrors($validations) {
    $errors = array();
    
    foreach ($validations as $field => $result) {
        if (isset($result['valid']) && !$result['valid'] && isset($result['error'])) {
            $errors[$field] = $result['error'];
        }
    }
    
    return array(
        'valid' => empty($errors),
        'errors' => $errors
    );
}
