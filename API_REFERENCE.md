# DC Clarity API Reference

## Backend Core Module Functions

### config.php - Constants

```php
// Database paths
define('DB_EQUIPMENT_PATH', __DIR__ . '/../database/dcfm-equipment-db.json');
define('DB_HISTORY_PATH', __DIR__ . '/../database/dcfm-equipment-history-db.json');

// Arrays
EQUIPMENT_TYPES: ['GENSET', 'UPS', 'CRAC', 'CHILLER', 'FIRE']
DC_LOCATIONS: ['BFDC', 'KJDC', 'KVDC', 'SJDC', 'KTEDC', 'IPEDC', 'KNEDC', 'SNEDC', 'KGEDC', 'KKEDC']
EDITABLE_FIELDS: ['brand', 'spec', 'status', 'location', 'supplier', 'vendor', 'sn', 'asset-tag', 'lifespan', 'installed', 'latest-pm', 'notes']

// Constants
SEQUENCE_FORMAT: 'DC-TYPE-SEQUENCE'
SEQUENCE_PADDING: 5
SEQUENCE_MAX: 99999
BACKUP_ENABLED: true
FILE_LOCK_TIMEOUT: 5
DEBUG_MODE: false
```

---

### response.php - Response Functions

```php
// Success response
successResponse(
    $data,           // Response data
    $message = '',   // Optional message
    $httpCode = 200  // HTTP status
)
// Returns: {success: true, message: "...", data: {...}}

// Error response
errorResponse(
    $message,        // Error message
    $errors = [],    // Field errors
    $httpCode = 400  // HTTP status
)
// Returns: {success: false, message: "...", errors: {...}}

// Validation error response
validationErrorResponse(
    $fieldErrors,        // Field-specific errors
    $message = '...'     // Optional message
)
// Returns: {success: false, message: "...", errors: {...}, code: 422}

// 404 response
notFoundResponse($message = '...')
// Returns: {success: false, message: "...", code: 404}

// 500 response
serverErrorResponse($message = '...')
// Returns: {success: false, message: "...", code: 500}
```

---

### json-db.php - Database Functions

```php
// Read JSON file
readJson($path, $maxRetries = 3)
// Returns: Array from JSON file
// Throws: Exception on failure
// Uses: Shared lock (LOCK_SH | LOCK_NB) with retry logic

// Write JSON file
writeJson($path, $data, $createBackup = false)
// Parameters: File path, data array, backup flag
// Returns: true on success
// Throws: Exception on failure
// Uses: Exclusive lock (LOCK_EX), temp file, atomic rename

// Append to JSON file
appendJson($path, $newData)
// Parameters: File path, data to append
// Returns: true on success
// Throws: Exception on failure

// Delete rows matching callback
deleteJsonRow($path, $callback)
// Parameters: File path, callback function
// Callback: function($item) { return false to delete; }
// Returns: true if deleted, false if not found

// Find single row
findJsonRow($path, $callback)
// Parameters: File path, callback function
// Callback: function($item) { return true if match; }
// Returns: Single matching item or null

// Find multiple rows
findJsonRows($path, $callback)
// Parameters: File path, callback function
// Callback: function($item) { return true if match; }
// Returns: Array of matching items

// Update single row
updateJsonRow($path, $matcher, $updates)
// Parameters: File path, matcher callback, updates array
// Matcher: function($item) { return true if match; }
// Returns: Updated item or null if not found
```

---

### validator.php - Validation Functions

```php
// Validate required field
validateRequired($value, $fieldName)
// Returns: ['valid' => true/false, 'error' => '...']

// Validate string length
validateStringLength($value, $minLength, $maxLength, $fieldName)
// Returns: ['valid' => true/false, 'error' => '...']

// Validate numeric value
validateNumeric($value, $min, $max, $fieldName)
// Returns: ['valid' => true/false, 'error' => '...']

// Validate equipment type
validateEquipmentType($type)
// Returns: ['valid' => true/false, 'error' => '...']

// Validate DC location
validateDCLocation($dc)
// Returns: ['valid' => true/false, 'error' => '...']

// Validate serial number (5-50 chars)
validateSerialNumber($sn)
// Returns: ['valid' => true/false, 'error' => '...']

// Validate equipment ID format
validateEquipmentId($id)
// Pattern: /^[A-Z]{2,6}-[A-Z]{2,10}-\d{5}$/
// Returns: ['valid' => true/false, 'error' => '...']

// Validate serial number not duplicate
validateDuplicateSerialNumber($sn, $excludeId = null)
// Parameters: Serial number, equipment ID to exclude (for updates)
// Returns: ['valid' => true/false, 'error' => '...']

// Validate equipment exists
validateEquipmentExists($equipmentId)
// Returns: ['valid' => true/false, 'error' => '...']

// Collect all validation errors
collectValidationErrors($validations)
// Parameters: Array of validation results
// Returns: Array of errors only (filters out valid items)
```

---

### sequence.php - Sequence Functions

```php
// Generate sequence number for DC+type
generateSequenceNumber($dc, $equipmentType)
// Returns: 5-digit zero-padded number (e.g., "00097")
// Throws: Exception on failure

// Generate full equipment ID
generateEquipmentId($dc, $type)
// Returns: Full ID (e.g., "IPEDC-UPS-00097")
// Throws: Exception on failure

// Verify equipment ID is unique
isEquipmentIdUnique($equipmentId)
// Returns: true/false

// Extract sequence from ID
extractSequenceFromId($equipmentId)
// Returns: 5-digit sequence

// Extract DC from ID
extractDCFromId($equipmentId)
// Returns: DC code (e.g., "IPEDC")

// Extract type from ID
extractTypeFromId($equipmentId)
// Returns: Type code (e.g., "UPS")

// Generate history ID
generateHistoryId()
// Returns: History ID (e.g., "HIS-00001")
```

---

### history.php - History Functions

```php
// Add history entry
addHistory(
    $equipmentId,
    $event,
    $notes = '',
    $changes = [],
    $createdBy = null
)
// Returns: Created history entry
// Auto-generates: history_id, date_created, timestamp

// Detect changes between old and new data
detectChanges($original, $new, $editableFields)
// Parameters: Original data, new data, array of field names
// Returns: Array of changes [{field, old, new}, ...]

// Generate human-readable change notes
generateChangeNotes($changes)
// Parameters: Array of change items
// Returns: Formatted string "Changed 'Field' from 'X' to 'Y'; ..."

// Get all history for equipment
getEquipmentHistory($equipmentId)
// Returns: Array of history entries

// Delete single history entry
deleteHistory($historyId)
// Returns: true/false

// Delete all history for equipment
deleteEquipmentHistory($equipmentId)
// Returns: true/false

// Format history entry for API response
formatHistoryEntry($entry)
// Returns: Formatted history entry with proper fields
```

---

### helpers.php - Utility Functions

```php
// Sanitize input by type
sanitizeInput($data, $type = 'string')
// Types: 'int', 'float', 'email', 'url', 'string'
// Returns: Sanitized value

// Get POST parameter
getPost($name, $type = 'string', $default = '')
// Returns: POST value or default

// Get GET parameter
getGet($name, $type = 'string', $default = '')
// Returns: GET value or default

// Get current user
getCurrentUser()
// Returns: User from session/environment or 'System'

// Get current timestamp
getCurrentTimestamp()
// Format: "H:i d/m/Y" (e.g., "10:45 24/05/2026")
// Returns: Formatted timestamp string

// Convert equipment type code to label
getEquipmentTypeLabel($type)
// Returns: Display label (e.g., "UPS & Battery System")

// Convert DC code to label
getDCLabel($dc)
// Returns: Display label

// Check if request is POST
isPost()
// Returns: true/false

// Check if request is GET
isGet()
// Returns: true/false

// Check if request is AJAX
isAjax()
// Checks: X-Requested-With header
// Returns: true/false

// Format equipment for API response
formatEquipmentForResponse($equipment)
// Returns: Equipment with proper field names

// Log error message
logError($message, $level = 'ERROR')
// Levels: 'ERROR', 'WARNING', 'INFO'
// Writes to: backend/logs/error.log (if DEBUG_MODE enabled)
```

---

## Frontend Module API

### dcclarity-global-var.js - Constants

```javascript
// Access any constant via window.dclarityConfig
window.dclarityConfig.CURRENT_USER
window.dclarityConfig.EQUIPMENT_TYPES
window.dclarityConfig.DC_LOCATIONS
window.dclarityConfig.EDITABLE_FIELDS
window.dclarityConfig.EQUIPMENT_TABLE_COLUMNS
window.dclarityConfig.HISTORY_TABLE_COLUMNS
window.dclarityConfig.FIELD_LABELS
window.dclarityConfig.EQUIPMENT_STATUSES
window.dclarityConfig.API_ENDPOINTS
window.dclarityConfig.POPUP_TIMEOUT
window.dclarityConfig.DEBUG
```

---

### dcfm-api.js - API Functions

```javascript
const dclarityAPI = (function() {
    // Create equipment
    async createEquipment(equipmentData)
    // POST to backend/equipment/create.php
    // Returns: {success, message, data: equipment}

    // Update equipment
    async updateEquipment(equipmentId, updates)
    // POST to backend/equipment/update.php
    // Returns: {success, message, data: updated_equipment}

    // Delete equipment
    async deleteEquipment(equipmentId, deleteHistory = false)
    // POST to backend/equipment/delete.php
    // Returns: {success, message, data: {id, deleted}}

    // Get single equipment
    async getEquipment(equipmentId)
    // GET from backend/equipment/get.php
    // Returns: {success, message, data: equipment}

    // List equipment
    async listEquipment(filters = {})
    // GET from backend/equipment/list.php
    // Filters: {dc, type, status, limit, offset}
    // Returns: {success, message, data: {equipment, total, count, has_more}}

    // Get history for equipment
    async getHistory(equipmentId)
    // GET from backend/history/list.php
    // Returns: {success, message, data: {history, total}}

    // Add history entry
    async addHistory(equipmentId, event, notes = '')
    // POST to backend/history/create.php
    // Returns: {success, message, data: history_entry}

    // Delete history entry
    async deleteHistory(historyId)
    // POST to backend/history/delete.php
    // Returns: {success, message, data: {history_id, deleted}}
})();
```

---

### dcfm-ui.js - UI Functions

```javascript
const dclarityUI = (function() {
    // Show confirmation popup
    showConfirm(message, onConfirm, onCancel)
    // Shows modal with Yes/No buttons
    // Calls: onConfirm() or onCancel()

    // Show success popup
    showSuccess(message, onClose)
    // Shows modal with close button
    // Auto-closes after POPUP_TIMEOUT
    // Calls: onClose() when closed

    // Show error popup
    showError(message, errors = {}, onClose)
    // Shows modal with error message
    // Shows field-specific errors if provided
    // Calls: onClose() when closed

    // Show loading state
    showLoading(containerId)
    // Sets innerHTML to loading spinner
    // Adds 'loading-state' class

    // Hide loading state
    hideLoading(containerId)
    // Removes loading spinner
    // Removes 'loading-state' class

    // Enable form fields
    enableFields(fieldIds)
    // Array of field IDs to enable
    // Sets disabled = false

    // Disable form fields
    disableFields(fieldIds)
    // Array of field IDs to disable
    // Sets disabled = true

    // Populate select dropdown
    populateSelect(selectId, items, placeholder = '')
    // items: [{value, label}, ...]
    // Clears existing options and adds new ones

    // Get field value
    getFieldValue(fieldId)
    // Returns: Value of field with given ID

    // Set field value
    setFieldValue(fieldId, value)
    // Sets value of field with given ID

    // Clear multiple fields
    clearFields(fieldIds)
    // Array of field IDs to clear
    // Sets value = '' for each

    // Escape HTML
    escapeHtml(text)
    // Returns: HTML-escaped text
})();
```

---

### dcfm-form.js - Form Page

```javascript
const dclarityForm = (function() {
    // Initialize form page
    init()
    // Call on DOMContentLoaded
    // Populates selects, attaches listeners

    // Populate form dropdowns
    populateFormSelects()
    // Fills DC Location, Equipment Type, Status

    // Handle form submission
    handleFormSubmit(event)
    // Validates, calls API, shows success/error

    // Gather form data
    gatherFormData()
    // Returns: Object with all form fields

    // Validate form data
    validateFormData(data)
    // Basic frontend validation
    // Returns: {valid, errors}

    // Reset form
    resetForm()
    // Clears all fields
})();
```

---

### dcfm-details.js - Details Page

```javascript
const dclarityDetails = (function() {
    // Initialize details page
    init()
    // Call on DOMContentLoaded
    // Loads equipment from URL parameter

    // Get equipment ID from URL
    getEquipmentIdFromUrl()
    // Reads ?id=XXX from URL
    // Returns: Equipment ID

    // Load equipment data
    async loadEquipment(equipmentId)
    // Calls API to fetch equipment
    // Displays data in form fields

    // Display equipment data
    displayEquipmentData(equipment)
    // Sets all field values
    // Uses ui.setFieldValue(detail_{field})

    // Enter edit mode
    enterEditMode()
    // Enables editable fields
    // Disables read-only fields
    // Shows Update/Cancel buttons

    // Exit edit mode
    exitEditMode()
    // Reloads page to reset form

    // Handle update
    async handleUpdate()
    // Gathers changes, shows confirm, sends API

    // Gather updates
    gatherUpdates()
    // Compares current vs original
    // Returns: Only changed fields

    // Handle delete
    handleDelete()
    // Shows confirmation popup
    // Asks about history deletion

    // Perform delete
    async performDelete(deleteHistory)
    // Calls API to delete equipment
    // Redirects to list on success
})();
```

---

### dcfm-history.js - History Management

```javascript
const dclarityHistory = (function() {
    // Initialize history
    init(equipmentId)
    // Call with equipment ID
    // Loads and renders history

    // Load history
    async loadHistory(equipmentId)
    // Calls API to fetch history
    // Stores in array, renders table

    // Render history table
    renderHistoryTable()
    // Dynamically builds HTML table
    // Calls renderHistoryRow for each entry

    // Render single history row
    renderHistoryRow(entry)
    // Returns: HTML table row

    // Attach delete handlers
    attachDeleteHandlers()
    // Adds click listeners to delete buttons

    // Handle history delete
    async handleDeleteHistory(historyId)
    // Shows confirm, calls API, reloads

    // Add history entry
    async addEntry(equipmentId, event, notes)
    // Calls API to add entry
    // Reloads history
})();
```

---

## Common Usage Patterns

### Create Equipment with Error Handling
```javascript
try {
    const result = await dclarityAPI.createEquipment({
        dc: 'IPEDC',
        type: 'UPS',
        brand: 'Eaton',
        spec: '100kVA',
        sn: 'ABC123456'
    });
    ui.showSuccess(`Equipment ${result.data.id} created successfully`);
} catch (error) {
    ui.showError(error.message);
}
```

### Update with Confirmation
```javascript
ui.showConfirm('Save changes?',
    async () => {
        try {
            const result = await dclarityAPI.updateEquipment(
                equipmentId,
                { brand: 'New Brand' }
            );
            ui.showSuccess('Equipment updated');
        } catch (error) {
            ui.showError(error.message);
        }
    },
    () => {
        console.log('Cancelled');
    }
);
```

### Populate Dropdown
```javascript
ui.populateSelect('select_id',
    [
        { value: 'opt1', label: 'Option 1' },
        { value: 'opt2', label: 'Option 2' }
    ],
    'Select an option'
);
```

### Field Operations
```javascript
// Get value
const value = ui.getFieldValue('field_id');

// Set value
ui.setFieldValue('field_id', 'new value');

// Disable fields during loading
ui.disableFields(['field1', 'field2']);
ui.showLoading('container_id');

// Re-enable and hide loading
ui.hideLoading('container_id');
ui.enableFields(['field1', 'field2']);
```

---

## Error Response Format

All API errors return this format:
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field_name": "Specific error for this field"
  }
}
```

Frontend catches errors and displays with `ui.showError()`.

---

## HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200  | Success |
| 201  | Created |
| 400  | Bad Request |
| 404  | Not Found |
| 405  | Method Not Allowed |
| 422  | Validation Error |
| 500  | Server Error |

---

## Database Query Examples

### Read All Equipment
```php
$equipment = readJson(DB_EQUIPMENT_PATH);
```

### Find Equipment by Type
```php
$gensets = findJsonRows(DB_EQUIPMENT_PATH, function($item) {
    return $item['type'] === 'GENSET';
});
```

### Update Equipment
```php
$updated = updateJsonRow(DB_EQUIPMENT_PATH,
    function($item) { return $item['id'] === $equipmentId; },
    ['brand' => 'New Brand']
);
```

### Delete Equipment
```php
deleteJsonRow(DB_EQUIPMENT_PATH, function($item) {
    return $item['id'] === $equipmentId;
});
```

---

## Configuration Customization

### Add New Equipment Type
1. Edit `js/dcclarity-global-var.js`:
```javascript
{ value: 'NEWTYPE', label: 'New Type' }
```

2. Edit `backend/core/config.php`:
```php
'NEWTYPE'
```

### Add Editable Field
1. Edit both config files to include new field name
2. Update validation in validator.php if needed
3. Update HTML form to include field
4. Update FIELD_LABELS in dcclarity-global-var.js

### Change Popup Timeout
Edit `js/dcclarity-global-var.js`:
```javascript
window.dclarityConfig.POPUP_TIMEOUT = 5000; // milliseconds
```

---

## Debugging

### Enable Debug Mode
Edit `js/dcclarity-global-var.js`:
```javascript
window.dclarityConfig.DEBUG = true;
```

Then check browser console for debug messages.

### Enable Backend Logging
Edit `backend/core/config.php`:
```php
define('DEBUG_MODE', true);
```

Then check `backend/logs/error.log`.

---

## Performance Tips

1. **Batch API calls**: Load multiple things in parallel
2. **Cache results**: Store equipment list locally
3. **Pagination**: Use limit/offset for large datasets
4. **Debounce**: Delay input validation until user stops typing
5. **Minimize DOM updates**: Update only changed fields

---

## Testing Checklist

- [ ] Create equipment
- [ ] Update equipment
- [ ] Delete equipment
- [ ] View equipment details
- [ ] List equipment with filters
- [ ] View change history
- [ ] Delete history entry
- [ ] Validate duplicate serial numbers
- [ ] Test error messages
- [ ] Test success messages
- [ ] Test form reset
- [ ] Test edit mode enable/disable

