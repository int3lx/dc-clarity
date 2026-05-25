<?php
/**
 * History Manager
 * 
 * Handles history logging for equipment changes.
 * Provides reusable functions for adding history entries.
 */

require_once __DIR__ . '/json-db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sequence.php';

/**
 * Add history entry for equipment event
 * 
 * @param string $equipmentId Equipment ID
 * @param string $event Event type (e.g., "Detail Update", "Equipment Created")
 * @param string $notes Event notes/description
 * @param array $changes Array of change details with 'field', 'old', 'new' keys
 * @param string $createdBy User who made the change
 * @return array Created history entry
 * @throws Exception
 */
function addHistory($equipmentId, $event, $notes, $changes = array(), $createdBy = 'System', $historyDate = '') {
    if (empty($equipmentId) || empty($event)) {
        throw new Exception('Equipment ID and Event are required');
    }
    
    // Generate history ID
    $historyId = generateHistoryId();
    
    // Generate timestamp in format: HH:MM DD/MM/YYYY
    $dateCreated = date('H:i d/m/Y');
    
    // Build history entry
    $entry = array(
        'history_id' => $historyId,
        'equipment_id' => $equipmentId,
        'event' => $event,
        'notes' => $notes,
        'history_date' => $historyDate,
        'changes' => is_array($changes) ? $changes : array(),
        'created_by' => $createdBy,
        'date_created' => $dateCreated
    );
    
    // Append to history database
    appendJson(DB_HISTORY_PATH, $entry);
    
    return $entry;
}

/**
 * Detect changes between two equipment records
 * 
 * @param array $originalData Original equipment data
 * @param array $newData Updated equipment data
 * @param array $editableFields List of fields that can be edited
 * @return array Array of changes with 'field', 'old', 'new' keys
 */
function detectChanges($originalData, $newData, $editableFields = array()) {
    $changes = array();
    
    if (empty($editableFields)) {
        // Default editable fields
        $editableFields = array(
            'brand', 'spec', 'status', 'location', 'supplier', 'vendor',
            'sn', 'asset-tag', 'lifespan', 'installed', 'latest-pm', 'notes'
        );
    }
    
    // Map of field keys to display labels
    $fieldLabels = array(
        'brand' => 'Brand',
        'spec' => 'Specification',
        'status' => 'Status',
        'location' => 'Detailed Location',
        'supplier' => 'Supplier',
        'vendor' => 'Current Vendor',
        'sn' => 'Serial Number',
        'asset-tag' => 'TM Asset Number',
        'lifespan' => 'Designed Lifespan',
        'installed' => 'Install Date',
        'latest-pm' => 'Last Maintenance Date',
        'notes' => 'Additional Notes'
    );
    
    foreach ($editableFields as $field) {
        $originalValue = isset($originalData[$field]) ? (string)$originalData[$field] : '';
        $newValue = isset($newData[$field]) ? (string)$newData[$field] : '';
        
        // Skip if no change
        if ($originalValue === $newValue) {
            continue;
        }
        
        $label = isset($fieldLabels[$field]) ? $fieldLabels[$field] : ucfirst($field);
        
        $changes[] = array(
            'field' => $label,
            'old' => $originalValue,
            'new' => $newValue
        );
    }
    
    return $changes;
}

/**
 * Generate change notes from detected changes
 * 
 * Example output:
 * "Changed 'Supplier' from 'ABC' to 'XYZ'; Changed 'Serial Number' from '111' to '222';"
 * 
 * @param array $changes Array of changes from detectChanges()
 * @return string Formatted change notes
 */
function generateChangeNotes($changes) {
    if (!is_array($changes) || empty($changes)) {
        return 'No changes detected';
    }
    
    $notes = array();
    
    foreach ($changes as $change) {
        if (!isset($change['field']) || !isset($change['old']) || !isset($change['new'])) {
            continue;
        }
        
        $note = "Changed '" . $change['field'] . "' from '" . $change['old'] . "' to '" . $change['new'] . "'";
        $notes[] = $note;
    }
    
    return implode('; ', $notes) . (empty($notes) ? '' : ';');
}

/**
 * Get history entries for specific equipment
 * 
 * @param string $equipmentId Equipment ID to filter by
 * @return array Array of history entries
 * @throws Exception
 */
function getEquipmentHistory($equipmentId) {
    if (empty($equipmentId)) {
        throw new Exception('Equipment ID is required');
    }
    
    $history = readJson(DB_HISTORY_PATH);
    
    $filtered = array_filter($history, function($item) use ($equipmentId) {
        return isset($item['equipment_id']) && $item['equipment_id'] === $equipmentId;
    });
    
    return array_values($filtered);
}

/**
 * Delete history entry by history ID
 * 
 * @param string $historyId History ID to delete
 * @return bool True if deleted
 * @throws Exception
 */
function deleteHistory($historyId) {
    if (empty($historyId)) {
        throw new Exception('History ID is required');
    }
    
    $deleted = false;
    
    deleteJsonRow(DB_HISTORY_PATH, function($item) use ($historyId, &$deleted) {
        if (isset($item['history_id']) && $item['history_id'] === $historyId) {
            $deleted = true;
            return false; // Exclude this item (delete it)
        }
        return true; // Keep this item
    });
    
    return $deleted;
}

/**
 * Delete all history entries for equipment
 * 
 * @param string $equipmentId Equipment ID
 * @return int Number of entries deleted
 * @throws Exception
 */
function deleteEquipmentHistory($equipmentId) {
    if (empty($equipmentId)) {
        throw new Exception('Equipment ID is required');
    }
    
    $count = 0;
    
    deleteJsonRow(DB_HISTORY_PATH, function($item) use ($equipmentId, &$count) {
        if (isset($item['equipment_id']) && $item['equipment_id'] === $equipmentId) {
            $count++;
            return false; // Exclude this item
        }
        return true; // Keep this item
    });
    
    return $count;
}

/**
 * Format history entry for display
 * 
 * @param array $entry History entry
 * @return array Formatted entry
 */
function formatHistoryEntry($entry) {
    return array(
        'history_id' => isset($entry['history_id']) ? $entry['history_id'] : '',
        'equipment_id' => isset($entry['equipment_id']) ? $entry['equipment_id'] : '',
        'event' => isset($entry['event']) ? $entry['event'] : '',
        'notes' => isset($entry['notes']) ? $entry['notes'] : '',
        'history_date' => isset($entry['history_date']) ? $entry['history_date'] : '',
        'changes' => isset($entry['changes']) ? $entry['changes'] : array(),
        'created_by' => isset($entry['created_by']) ? $entry['created_by'] : '',
        'date_created' => isset($entry['date_created']) ? $entry['date_created'] : ''
    );
}
