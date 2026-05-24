/**
 * Global Variables and Configuration
 * 
 * Contains:
 * - Constants (equipment types, DC locations)
 * - Reusable configuration
 * - Editable field lists
 * - Table column configurations
 * 
 * IMPORTANT: This file contains ONLY constants and config.
 * No UI logic or API calls.
 */

window.dclarityConfig = window.dclarityConfig || {};

// ===== CURRENT USER =====
window.dclarityConfig.CURRENT_USER = 'Beta Tester';

// ===== EQUIPMENT TYPES =====
window.dclarityConfig.EQUIPMENT_TYPES = [
    { value: 'GENSET', label: 'GENSET System' },
    { value: 'UPS', label: 'UPS & Battery System' },
    { value: 'CRAC', label: 'CRAC System' },
    { value: 'CHILLER', label: 'Chiller System' },
    { value: 'FIRE', label: 'Fire Suppression System' }
];

// ===== DC LOCATIONS =====
window.dclarityConfig.DC_LOCATIONS = [
    { value: 'BFDC', label: 'BFDC' },
    { value: 'KJDC', label: 'KJDC' },
    { value: 'KVDC', label: 'KVDC' },
    { value: 'SJDC', label: 'SJDC' },
    { value: 'KTEDC', label: 'KTEDC' },
    { value: 'IPEDC', label: 'IPEDC' },
    { value: 'KNEDC', label: 'KNEDC' },
    { value: 'SNEDC', label: 'SNEDC' },
    { value: 'KGEDC', label: 'KGEDC' },
    { value: 'KKEDC', label: 'KKEDC' }
];

// ===== EDITABLE FIELDS =====
// Fields that can be edited on the details page
window.dclarityConfig.EDITABLE_FIELDS = [
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
];

// ===== EQUIPMENT TABLE COLUMNS =====
// Configuration for equipment list table
window.dclarityConfig.EQUIPMENT_TABLE_COLUMNS = [
    { key: 'actions', label: 'Actions' },
    { key: 'id', label: 'Equipment ID' },
    { key: 'type', label: 'Equipment Type' },
    { key: 'brand', label: 'Equipment Brand' },
    { key: 'spec', label: 'Specifications' },
    { key: 'status', label: 'Equipment Status' },
    { key: 'location', label: 'Detailed Location' },
    { key: 'dc', label: 'Datacenter' },
    { key: 'supplier', label: 'Supplier' },
    { key: 'vendor', label: 'Current Vendor' },
    { key: 'sn', label: 'Serial Number' },
    { key: 'asset-tag', label: 'TM Asset Number' },
    { key: 'lifespan', label: 'Designed Lifespan' },
    { key: 'installed', label: 'Install Date' },
    { key: 'latest-pm', label: 'Last Maintenance Date' },
    { key: 'notes', label: 'Additional Notes' },
    { key: 'created-by', label: 'Created By' },
    { key: 'date-created', label: 'Date Created' }
];

// ===== HISTORY TABLE COLUMNS =====
// Configuration for history table
window.dclarityConfig.HISTORY_TABLE_COLUMNS = [
    { key: 'event', label: 'Event' },
    { key: 'notes', label: 'Notes' },
    { key: 'changes', label: 'Changes' },
    { key: 'created-by', label: 'Created By' },
    { key: 'date-created', label: 'Date Created' },
    { key: 'actions', label: 'Actions' }
];

// ===== API ENDPOINTS =====
window.dclarityConfig.API_ENDPOINTS = {
    equipment: {
        create: 'backend/equipment/create.php',
        update: 'backend/equipment/update.php',
        delete: 'backend/equipment/delete.php',
        get: 'backend/equipment/get.php',
        list: 'backend/equipment/list.php'
    },
    history: {
        create: 'backend/history/create.php',
        delete: 'backend/history/delete.php',
        list: 'backend/history/list.php'
    }
};

// ===== FIELD LABEL MAPPING =====
// Maps field keys to display labels
window.dclarityConfig.FIELD_LABELS = {
    'id': 'Equipment ID',
    'type': 'Equipment Type',
    'brand': 'Equipment Brand',
    'spec': 'Specifications',
    'status': 'Equipment Status',
    'location': 'Detailed Location',
    'dc': 'Datacenter',
    'supplier': 'Supplier',
    'vendor': 'Current Vendor',
    'sn': 'Serial Number',
    'asset-tag': 'TM Asset Number',
    'lifespan': 'Designed Lifespan',
    'installed': 'Install Date',
    'latest-pm': 'Last Maintenance Date',
    'notes': 'Additional Notes',
    'created-by': 'Created By',
    'date-created': 'Date Created'
};

// ===== EQUIPMENT STATUSES =====
window.dclarityConfig.EQUIPMENT_STATUSES = [
    'Active',
    'Operational',
    'Under Maintenance',
    'Faulty',
    'Spare',
    'Decommissioned',
    'Disposed'
];

// ===== POPUP TIMEOUTS (milliseconds) =====
window.dclarityConfig.POPUP_TIMEOUT = 3000; // Auto-close success popups after 3 seconds

// ===== DEBUG MODE =====
window.dclarityConfig.DEBUG = false;
