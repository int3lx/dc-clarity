<?php
/**
 * JSON Database Handler
 * 
 * Provides safe read/write operations for JSON files with file locking
 * to prevent corruption and ensure data integrity.
 */

/**
 * Safely read JSON file with retry logic
 * 
 * @param string $path File path
 * @param int $maxRetries Maximum retry attempts
 * @return array Decoded JSON data
 * @throws Exception
 */
function readJson($path, $maxRetries = 3) {
    if (!file_exists($path)) {
        throw new Exception('Database file not found: ' . $path);
    }
    
    $attempt = 0;
    while ($attempt < $maxRetries) {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new Exception('Cannot open file for reading: ' . $path);
        }
        
        // Try to acquire shared lock
        if (flock($handle, LOCK_SH | LOCK_NB)) {
            $content = file_get_contents($path);
            flock($handle, LOCK_UN);
            fclose($handle);
            
            if (empty($content)) {
                return array();
            }
            
            $data = json_decode($content, true);
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON format: ' . json_last_error_msg());
            }
            
            return is_array($data) ? $data : array();
        }
        
        fclose($handle);
        $attempt++;
        
        if ($attempt < $maxRetries) {
            usleep(50000); // Wait 50ms before retry
        }
    }
    
    throw new Exception('Unable to acquire read lock on file: ' . $path);
}

/**
 * Safely write JSON file with file locking
 * Creates backup before writing if enabled
 * 
 * @param string $path File path
 * @param array $data Data to write
 * @param bool $createBackup Whether to create backup
 * @return bool Success status
 * @throws Exception
 */
function writeJson($path, $data, $createBackup = false) {
    if (!is_array($data)) {
        throw new Exception('Data must be an array');
    }
    
    $dir = dirname($path);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            throw new Exception('Cannot create directory: ' . $dir);
        }
    }
    
    // Create backup if enabled and file exists
    if ($createBackup && file_exists($path)) {
        $backupDir = dirname($path) . '/backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupFile = $backupDir . basename($path) . '.' . date('Y-m-d_H-i-s') . '.bak';
        if (!copy($path, $backupFile)) {
            throw new Exception('Failed to create backup');
        }
    }
    
    // Write to temporary file first
    $tempFile = $path . '.tmp';
    
    $jsonContent = json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );
    
    if ($jsonContent === false) {
        throw new Exception('Failed to encode JSON: ' . json_last_error_msg());
    }
    
    // Write to temp file with locking
    $handle = fopen($tempFile, 'w');
    if (!$handle) {
        throw new Exception('Cannot open temp file for writing: ' . $tempFile);
    }
    
    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        throw new Exception('Cannot acquire exclusive lock on temp file: ' . $tempFile);
    }
    
    $bytesWritten = fwrite($handle, $jsonContent);
    
    if ($bytesWritten === false || $bytesWritten !== strlen($jsonContent)) {
        flock($handle, LOCK_UN);
        fclose($handle);
        @unlink($tempFile);
        throw new Exception('Failed to write data to temp file');
    }
    
    // Flush and close
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    
    // Atomic rename
    if (!rename($tempFile, $path)) {
        @unlink($tempFile);
        throw new Exception('Failed to move temp file to target: ' . $path);
    }
    
    return true;
}

/**
 * Append data to JSON array file
 * 
 * @param string $path File path
 * @param array $newData Data to append
 * @return array Updated data
 * @throws Exception
 */
function appendJson($path, $newData) {
    if (!is_array($newData)) {
        throw new Exception('Data to append must be an array');
    }
    
    // Read existing data
    if (file_exists($path)) {
        $data = readJson($path);
    } else {
        $data = array();
    }
    
    // Ensure it's an array of items
    if (!is_array($data)) {
        $data = array();
    }
    
    // Append new data
    $data[] = $newData;
    
    // Write back
    writeJson($path, $data, false);
    
    return $data;
}

/**
 * Delete rows from JSON file using callback filter
 * 
 * @param string $path File path
 * @param callable $callback Filter callback that returns true for items to keep
 * @return array Updated data
 * @throws Exception
 */
function deleteJsonRow($path, $callback) {
    if (!is_callable($callback)) {
        throw new Exception('Callback must be callable');
    }
    
    $data = readJson($path);
    
    if (!is_array($data)) {
        throw new Exception('File data must be an array');
    }
    
    // Filter data using callback
    $filtered = array_filter($data, $callback);
    
    // Re-index array
    $updated = array_values($filtered);
    
    // Write back
    writeJson($path, $updated, false);
    
    return $updated;
}

/**
 * Find single row in JSON file using callback
 * 
 * @param string $path File path
 * @param callable $callback Search callback that returns true on match
 * @return array|null Matching row or null
 * @throws Exception
 */
function findJsonRow($path, $callback) {
    if (!is_callable($callback)) {
        throw new Exception('Callback must be callable');
    }
    
    $data = readJson($path);
    
    if (!is_array($data)) {
        return null;
    }
    
    foreach ($data as $row) {
        if ($callback($row)) {
            return $row;
        }
    }
    
    return null;
}

/**
 * Find multiple rows in JSON file using callback
 * 
 * @param string $path File path
 * @param callable $callback Search callback that returns true on match
 * @return array Matching rows
 * @throws Exception
 */
function findJsonRows($path, $callback) {
    if (!is_callable($callback)) {
        throw new Exception('Callback must be callable');
    }
    
    $data = readJson($path);
    
    if (!is_array($data)) {
        return array();
    }
    
    $results = array();
    foreach ($data as $row) {
        if ($callback($row)) {
            $results[] = $row;
        }
    }
    
    return $results;
}

/**
 * Update single row in JSON file
 * 
 * @param string $path File path
 * @param callable $matcher Callback to find row to update
 * @param array $updates Fields to update
 * @return array|null Updated row or null if not found
 * @throws Exception
 */
function updateJsonRow($path, $matcher, $updates) {
    if (!is_callable($matcher)) {
        throw new Exception('Matcher must be callable');
    }
    
    if (!is_array($updates)) {
        throw new Exception('Updates must be an array');
    }
    
    $data = readJson($path);
    
    if (!is_array($data)) {
        throw new Exception('File data must be an array');
    }
    
    $updated = false;
    $resultRow = null;
    
    foreach ($data as &$row) {
        if ($matcher($row)) {
            $row = array_merge($row, $updates);
            $updated = true;
            $resultRow = $row;
            break;
        }
    }
    
    if ($updated) {
        writeJson($path, $data, false);
    }
    
    return $resultRow;
}
