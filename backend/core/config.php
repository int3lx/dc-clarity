<?php
/**
 * Configuration File
 * 
 * Centralized configuration for database paths, constants, and system settings.
 */

// Database paths
defined('DB_EQUIPMENT_PATH') or define('DB_EQUIPMENT_PATH', __DIR__ . '/../database/dcfm-equipment-db.json');
defined('DB_HISTORY_PATH') or define('DB_HISTORY_PATH', __DIR__ . '/../database/dcfm-equipment-history-db.json');

// Response settings
defined('JSON_INDENT') or define('JSON_INDENT', 2);
defined('JSON_FLAGS') or define('JSON_FLAGS', JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Editable fields configuration
defined('EDITABLE_FIELDS') or define('EDITABLE_FIELDS', array(
    'brand',
    'spec',
    'status',
    'location',
    'supplier',
    'vendor',
    'sn',
    'asset-tag',
    'lifespan',
    'installed',
    'latest-pm',
    'notes'
));

// Equipment types
defined('EQUIPMENT_TYPES') or define('EQUIPMENT_TYPES', array(
    'GENSET',
    'UPS',
    'CRAC',
    'CHILLER',
    'FIRE'
));

// DC Locations
defined('DC_LOCATIONS') or define('DC_LOCATIONS', array(
    'BFDC',
    'KJDC',
    'KVDC',
    'SJDC',
    'KTEDC',
    'IPEDC',
    'KNEDC',
    'SNEDC',
    'KGEDC',
    'KKEDC'
));

// Sequence format settings
defined('SEQUENCE_FORMAT') or define('SEQUENCE_FORMAT', 'DC_{dc}_{type}_{seq}');
defined('SEQUENCE_PADDING') or define('SEQUENCE_PADDING', 5);
defined('SEQUENCE_START') or define('SEQUENCE_START', 1);
defined('SEQUENCE_MAX') or define('SEQUENCE_MAX', 99999);

// History settings
defined('HISTORY_ID_FORMAT') or define('HISTORY_ID_FORMAT', 'HIS-{seq}');
defined('HISTORY_ID_PADDING') or define('HISTORY_ID_PADDING', 5);

// Backup settings
defined('BACKUP_ENABLED') or define('BACKUP_ENABLED', false);
defined('BACKUP_DIR') or define('BACKUP_DIR', __DIR__ . '/../database/backups/');

// File lock timeout (seconds)
defined('FILE_LOCK_TIMEOUT') or define('FILE_LOCK_TIMEOUT', 5);

// Error handling
defined('DEBUG_MODE') or define('DEBUG_MODE', false);
