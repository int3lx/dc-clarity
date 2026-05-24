<?php
/**
 * Helper Functions
 * 
 * Utility functions for common operations used across backend modules.
 */

require_once __DIR__ . '/config.php';

/**
 * Sanitize and validate POST/GET data
 * 
 * @param mixed $data Input data
 * @param string $type Data type ('string', 'int', 'float', 'email', 'url')
 * @return mixed Sanitized data or null if invalid
 */
function sanitizeInput($data, $type = 'string') {
    if ($data === null) {
        return null;
    }
    
    switch ($type) {
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT);
            
        case 'float':
            return filter_var($data, FILTER_VALIDATE_FLOAT);
            
        case 'email':
            return filter_var($data, FILTER_VALIDATE_EMAIL);
            
        case 'url':
            return filter_var($data, FILTER_VALIDATE_URL);
            
        case 'string':
        default:
            if (is_string($data)) {
                return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
            }
            return trim((string)$data);
    }
}

/**
 * Safely get POST parameter
 * 
 * @param string $name Parameter name
 * @param string $type Data type
 * @param mixed $default Default value if not found
 * @return mixed Parameter value or default
 */
function getPost($name, $type = 'string', $default = null) {
    if (!isset($_POST[$name])) {
        return $default;
    }
    
    $value = sanitizeInput($_POST[$name], $type);
    
    return $value !== null ? $value : $default;
}

/**
 * Safely get GET parameter
 * 
 * @param string $name Parameter name
 * @param string $type Data type
 * @param mixed $default Default value if not found
 * @return mixed Parameter value or default
 */
function getGet($name, $type = 'string', $default = null) {
    if (!isset($_GET[$name])) {
        return $default;
    }
    
    $value = sanitizeInput($_GET[$name], $type);
    
    return $value !== null ? $value : $default;
}

/**
 * Get current timestamp in format HH:MM DD/MM/YYYY
 * 
 * @return string Formatted timestamp
 */
function getCurrentTimestamp() {
    return date('H:i d/m/Y');
}

/**
 * Get current user (for history tracking)
 * Can be extended to read from session or authentication system
 * 
 * @return string Current user identifier
 */
function getCurrentUser() {
    // Check if user is in session
    if (isset($_SESSION['user_name']) && !empty($_SESSION['user_name'])) {
        return sanitizeInput($_SESSION['user_name'], 'string');
    }
    
    // Check environment
    if (isset($_ENV['CURRENT_USER']) && !empty($_ENV['CURRENT_USER'])) {
        return sanitizeInput($_ENV['CURRENT_USER'], 'string');
    }
    
    // Default fallback
    return 'System';
}

/**
 * Convert equipment type code to label
 * 
 * @param string $type Equipment type code
 * @return string Equipment type label
 */
function getEquipmentTypeLabel($type) {
    $types = array(
        'GENSET' => 'GENSET System',
        'UPS' => 'UPS & Battery System',
        'CRAC' => 'CRAC System',
        'CHILLER' => 'Chiller System',
        'FIRE' => 'Fire Suppression System'
    );
    
    return isset($types[$type]) ? $types[$type] : $type;
}

/**
 * Convert DC code to label
 * 
 * @param string $dc DC code
 * @return string DC label
 */
function getDCLabel($dc) {
    $dcs = array(
        'BFDC' => 'BFDC',
        'KJDC' => 'KJDC',
        'KVDC' => 'KVDC',
        'SJDC' => 'SJDC',
        'KTEDC' => 'KTEDC',
        'IPEDC' => 'IPEDC',
        'KNEDC' => 'KNEDC',
        'SNEDC' => 'SNEDC',
        'KGEDC' => 'KGEDC',
        'KKEDC' => 'KKEDC'
    );
    
    return isset($dcs[$dc]) ? $dcs[$dc] : $dc;
}

/**
 * Check if method is POST
 * 
 * @return bool
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if method is GET
 * 
 * @return bool
 */
function isGet() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Check if request is AJAX
 * 
 * @return bool
 */
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get data from either POST or GET
 * 
 * @param string $name Parameter name
 * @param string $type Data type
 * @param mixed $default Default value
 * @return mixed Parameter value
 */
function getInput($name, $type = 'string', $default = null) {
    if (isPost()) {
        return getPost($name, $type, $default);
    } else {
        return getGet($name, $type, $default);
    }
}

/**
 * Format equipment data for API response
 * Removes system fields and formats data
 * 
 * @param array $equipment Equipment data
 * @return array Formatted equipment data
 */
function formatEquipmentForResponse($equipment) {
    if (!is_array($equipment)) {
        return array();
    }
    
    return array(
        'id' => isset($equipment['id']) ? $equipment['id'] : '',
        'type' => isset($equipment['type']) ? $equipment['type'] : '',
        'brand' => isset($equipment['brand']) ? $equipment['brand'] : '',
        'spec' => isset($equipment['spec']) ? $equipment['spec'] : '',
        'status' => isset($equipment['status']) ? $equipment['status'] : '',
        'location' => isset($equipment['location']) ? $equipment['location'] : '',
        'dc' => isset($equipment['dc']) ? $equipment['dc'] : '',
        'supplier' => isset($equipment['supplier']) ? $equipment['supplier'] : '',
        'vendor' => isset($equipment['vendor']) ? $equipment['vendor'] : '',
        'sn' => isset($equipment['sn']) ? $equipment['sn'] : '',
        'asset-tag' => isset($equipment['asset-tag']) ? $equipment['asset-tag'] : '',
        'lifespan' => isset($equipment['lifespan']) ? $equipment['lifespan'] : '',
        'installed' => isset($equipment['installed']) ? $equipment['installed'] : '',
        'latest-pm' => isset($equipment['latest-pm']) ? $equipment['latest-pm'] : '',
        'notes' => isset($equipment['notes']) ? $equipment['notes'] : '',
        'created-by' => isset($equipment['created-by']) ? $equipment['created-by'] : '',
        'date-created' => isset($equipment['date-created']) ? $equipment['date-created'] : ''
    );
}

/**
 * Log error to file (optional)
 * 
 * @param string $message Error message
 * @param string $level Log level (ERROR, WARNING, INFO)
 * @return bool Success
 */
function logError($message, $level = 'ERROR') {
    // Only log if debug mode is enabled
    if (!DEBUG_MODE) {
        return false;
    }
    
    $logFile = dirname(__DIR__) . '/logs/error.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        if (!mkdir($logDir, 0755, true)) {
            return false;
        }
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    return error_log($logEntry, 3, $logFile);
}

/**
 * Generate CORS headers (for future API expansion)
 * 
 * @param string $origin Allowed origin
 */
function setCorsHeaders($origin = '*') {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
}

/**
 * Verify CSRF token (basic implementation)
 * 
 * @param string $token Token to verify
 * @return bool
 */
function verifyCsrfToken($token) {
    // This is a basic implementation
    // In production, implement proper CSRF protection
    return !empty($token);
}

/**
 * Generate CSRF token
 * 
 * @return string Generated token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Convert array keys from snake_case to camelCase
 * 
 * @param array $array Input array
 * @return array Converted array
 */
function snakeToCamel($array) {
    $result = array();
    
    foreach ($array as $key => $value) {
        $camelKey = preg_replace_callback('/_([a-z])/', function($m) {
            return strtoupper($m[1]);
        }, $key);
        
        $result[$camelKey] = $value;
    }
    
    return $result;
}

/**
 * Convert array keys from camelCase to snake_case
 * 
 * @param array $array Input array
 * @return array Converted array
 */
function camelToSnake($array) {
    $result = array();
    
    foreach ($array as $key => $value) {
        $snakeKey = strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $key));
        $result[$snakeKey] = $value;
    }
    
    return $result;
}
