<?php
/**
 * Sequence Generator
 * 
 * Generates unique equipment IDs with format: {dc}-{type}-{5-digit-sequence}
 * Example: DC1-SERVER-00001
 * 
 * IMPORTANT: Sequence generation MUST happen only in backend.
 * Frontend MUST NOT generate sequences.
 */

require_once __DIR__ . '/json-db.php';
require_once __DIR__ . '/config.php';

/**
 * Generate next sequence number for specific DC + Equipment Type
 * 
 * Scans all existing records, filters by same DC + equipment type,
 * finds highest sequence, and increments safely.
 * 
 * @param string $dc Data Center location (e.g., "DC1", "IPEDC")
 * @param string $equipmentType Equipment type (e.g., "SERVER", "UPS")
 * @return string 5-digit zero-padded sequence number
 * @throws Exception
 */
function generateSequenceNumber($dc, $equipmentType) {
    if (empty($dc) || empty($equipmentType)) {
        throw new Exception('DC and Equipment Type are required');
    }
    
    $dc = strtoupper(trim($dc));
    $equipmentType = strtoupper(trim($equipmentType));
    
    try {
        $equipment = readJson(DB_EQUIPMENT_PATH);
    } catch (Exception $e) {
        // If database doesn't exist yet, start with sequence 1
        if (strpos($e->getMessage(), 'not found') !== false) {
            $equipment = array();
        } else {
            throw $e;
        }
    }
    
    $maxSequence = 0;
    
    // Scan all records to find highest sequence for this DC + type combination
    foreach ($equipment as $item) {
        // Check if item matches DC and equipment type
        if (!isset($item['id']) || !isset($item['dc']) || !isset($item['type'])) {
            continue;
        }
        
        $itemDc = strtoupper(trim($item['dc']));
        $itemType = strtoupper(trim($item['type']));
        
        // Only consider items with matching DC and type
        if ($itemDc !== $dc || $itemType !== $equipmentType) {
            continue;
        }
        
        // Extract sequence number from ID
        // ID format: {DC}-{TYPE}-{sequence}
        // Example: IPEDC-UPS-00097
        $idParts = explode('-', $item['id']);
        
        if (count($idParts) >= 3) {
            $sequencePart = array_pop($idParts);
            $sequence = (int)$sequencePart;
            
            if ($sequence > $maxSequence) {
                $maxSequence = $sequence;
            }
        }
    }
    
    // Increment to next sequence
    $nextSequence = $maxSequence + 1;
    
    // Validate range
    if ($nextSequence > SEQUENCE_MAX) {
        throw new Exception('Sequence number exceeded maximum: ' . SEQUENCE_MAX);
    }
    
    // Return zero-padded sequence
    return str_pad($nextSequence, SEQUENCE_PADDING, '0', STR_PAD_LEFT);
}

/**
 * Generate complete equipment ID
 * 
 * Format: {dc}-{type}-{sequence}
 * Example: IPEDC-UPS-00001
 * 
 * @param string $dc Data Center location
 * @param string $equipmentType Equipment type
 * @return string Generated equipment ID
 * @throws Exception
 */
function generateEquipmentId($dc, $equipmentType) {
    if (empty($dc) || empty($equipmentType)) {
        throw new Exception('DC and Equipment Type are required for ID generation');
    }
    
    $dc = strtoupper(trim($dc));
    $equipmentType = strtoupper(trim($equipmentType));
    
    // Generate sequence
    $sequence = generateSequenceNumber($dc, $equipmentType);
    
    // Combine to create ID
    $equipmentId = $dc . '-' . $equipmentType . '-' . $sequence;
    
    return $equipmentId;
}

/**
 * Verify equipment ID is unique
 * 
 * @param string $equipmentId Equipment ID to check
 * @return bool True if unique, false if duplicate
 */
function isEquipmentIdUnique($equipmentId) {
    try {
        $equipment = readJson(DB_EQUIPMENT_PATH);
        
        foreach ($equipment as $item) {
            if (isset($item['id']) && $item['id'] === $equipmentId) {
                return false;
            }
        }
        
        return true;
    } catch (Exception $e) {
        // If database doesn't exist, ID is unique
        return true;
    }
}

/**
 * Extract sequence number from equipment ID
 * 
 * @param string $equipmentId Equipment ID
 * @return int|null Sequence number or null if invalid format
 */
function extractSequenceFromId($equipmentId) {
    $parts = explode('-', $equipmentId);
    
    if (count($parts) < 3) {
        return null;
    }
    
    $sequencePart = array_pop($parts);
    $sequence = (int)$sequencePart;
    
    return $sequence;
}

/**
 * Extract DC from equipment ID
 * 
 * @param string $equipmentId Equipment ID
 * @return string|null DC location or null if invalid
 */
function extractDCFromId($equipmentId) {
    $parts = explode('-', $equipmentId);
    
    if (count($parts) < 1) {
        return null;
    }
    
    return $parts[0];
}

/**
 * Extract equipment type from equipment ID
 * 
 * @param string $equipmentId Equipment ID
 * @return string|null Equipment type or null if invalid
 */
function extractTypeFromId($equipmentId) {
    $parts = explode('-', $equipmentId);
    
    if (count($parts) < 2) {
        return null;
    }
    
    return $parts[1];
}

/**
 * Generate history entry ID
 * 
 * @return string Generated history ID in format HIS-00001
 */
function generateHistoryId() {
    try {
        $history = readJson(DB_HISTORY_PATH);
    } catch (Exception $e) {
        // If database doesn't exist, start with 1
        if (strpos($e->getMessage(), 'not found') !== false) {
            $history = array();
        } else {
            throw $e;
        }
    }
    
    $maxSequence = 0;
    
    // Find highest existing history sequence
    foreach ($history as $item) {
        if (!isset($item['history_id'])) {
            continue;
        }
        
        // Extract sequence: HIS-00001
        $parts = explode('-', $item['history_id']);
        if (count($parts) === 2) {
            $sequence = (int)$parts[1];
            if ($sequence > $maxSequence) {
                $maxSequence = $sequence;
            }
        }
    }
    
    $nextSequence = $maxSequence + 1;
    
    if ($nextSequence > SEQUENCE_MAX) {
        throw new Exception('History sequence exceeded maximum');
    }
    
    $paddedSequence = str_pad($nextSequence, HISTORY_ID_PADDING, '0', STR_PAD_LEFT);
    
    return 'HIS-' . $paddedSequence;
}
