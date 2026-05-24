# DC Clarity - Equipment Management System
## Restructured Architecture

### Project Overview
This is a lightweight equipment management system for data center facility management, built with HTML + PHP + JSON (no external frameworks or databases).

---

## New Project Structure

```
project-root/
│
├── backend/
│   ├── core/
│   │   ├── config.php              # Configuration & constants
│   │   ├── response.php            # Response standardization
│   │   ├── json-db.php             # Safe JSON operations
│   │   ├── validator.php           # Input validation
│   │   ├── sequence.php            # ID generation
│   │   ├── history.php             # History tracking
│   │   └── helpers.php             # Utility functions
│   │
│   ├── equipment/
│   │   ├── create.php              # Create equipment
│   │   ├── update.php              # Update equipment
│   │   ├── delete.php              # Delete equipment
│   │   ├── get.php                 # Fetch single equipment
│   │   └── list.php                # List equipment with filters
│   │
│   ├── history/
│   │   ├── create.php              # Create history entry
│   │   ├── delete.php              # Delete history entry
│   │   └── list.php                # List history entries
│   │
│   └── database/
│       ├── dcfm-equipment-db.json
│       └── dcfm-equipment-history-db.json
│
├── js/
│   ├── dcclarity-global-var.js     # Constants & config
│   ├── dcfm-api.js                 # API communication
│   ├── dcfm-ui.js                  # UI & popups
│   ├── dcfm-form.js                # Form page logic
│   ├── dcfm-details.js             # Details page logic
│   └── dcfm-history.js             # History management
│
├── dcfm-equipment-form.html        # Add equipment page
├── dcfm-equipment-details.html     # Equipment details page
├── dcfm-equipment-list.html        # Equipment list page
│
├── style.css                        # Stylesheet
├── .env                             # Environment variables
├── .gitignore                       # Git ignore rules
└── README.md                        # This file
```

---

## Backend Architecture

### Core Modules

#### 1. `backend/core/config.php`
- Database paths
- Constants (equipment types, DC locations)
- Sequence format settings
- Editable fields configuration

**Usage**: Include in all backend files
```php
require_once '../core/config.php';
```

#### 2. `backend/core/response.php`
- Standardized JSON responses
- Response helper functions

**Functions**:
- `successResponse($data, $message, $httpCode)` - Success response
- `errorResponse($message, $errors, $httpCode)` - Error response
- `validationErrorResponse($fieldErrors, $message)` - Validation errors
- `notFoundResponse($message)` - 404 response
- `serverErrorResponse($message)` - 500 response

**All responses follow format**:
```json
{
  "success": true,
  "message": "Success message",
  "data": {}
}
```

#### 3. `backend/core/json-db.php`
- Safe JSON file operations with file locking
- Prevents JSON corruption
- Implements retry logic

**Functions**:
- `readJson($path)` - Read JSON file safely
- `writeJson($path, $data)` - Write JSON with lock
- `appendJson($path, $newData)` - Append to JSON array
- `deleteJsonRow($path, $callback)` - Delete rows using callback
- `findJsonRow($path, $callback)` - Find single row
- `findJsonRows($path, $callback)` - Find multiple rows
- `updateJsonRow($path, $matcher, $updates)` - Update row

**Example**:
```php
$equipment = readJson(DB_EQUIPMENT_PATH);
$newEquipment = ['id' => 'DC1-SERVER-00001', ...];
appendJson(DB_EQUIPMENT_PATH, $newEquipment);
```

#### 4. `backend/core/validator.php`
- Input validation functions
- All validation happens server-side

**Functions**:
- `validateRequired($value, $fieldName)` - Required field
- `validateStringLength($value, $min, $max, $fieldName)` - String length
- `validateNumeric($value, $min, $max, $fieldName)` - Numeric value
- `validateEquipmentType($type)` - Valid equipment type
- `validateDCLocation($dc)` - Valid DC location
- `validateSerialNumber($sn)` - Serial number format
- `validateEquipmentId($id)` - Equipment ID format
- `validateDuplicateSerialNumber($sn, $excludeId)` - No duplicates
- `validateEquipmentExists($equipmentId)` - Equipment exists
- `collectValidationErrors($validations)` - Collect all errors

#### 5. `backend/core/sequence.php`
- Backend-only ID generation
- **Frontend NEVER generates IDs**

**Functions**:
- `generateSequenceNumber($dc, $type)` - Generate 5-digit sequence
- `generateEquipmentId($dc, $type)` - Generate full equipment ID
- `isEquipmentIdUnique($equipmentId)` - Check uniqueness
- `generateHistoryId()` - Generate history ID

**Format**: `{DC}-{TYPE}-{00001}`
Example: `IPEDC-UPS-00097`

#### 6. `backend/core/history.php`
- History logging and retrieval
- Automatic change detection

**Functions**:
- `addHistory($equipmentId, $event, $notes, $changes, $createdBy)` - Add entry
- `detectChanges($original, $new, $editableFields)` - Detect changes
- `generateChangeNotes($changes)` - Format change notes
- `getEquipmentHistory($equipmentId)` - Get equipment history
- `deleteHistory($historyId)` - Delete entry
- `deleteEquipmentHistory($equipmentId)` - Delete all entries
- `formatHistoryEntry($entry)` - Format for response

#### 7. `backend/core/helpers.php`
- Utility functions
- Input sanitization
- User tracking

**Functions**:
- `sanitizeInput($data, $type)` - Sanitize input
- `getPost($name, $type, $default)` - Get POST parameter
- `getGet($name, $type, $default)` - Get GET parameter
- `getCurrentUser()` - Get current user
- `getCurrentTimestamp()` - Get formatted timestamp
- `getEquipmentTypeLabel($type)` - Convert type code to label
- `getDCLabel($dc)` - Convert DC code to label
- `formatEquipmentForResponse($equipment)` - Format for API response

### API Endpoints

#### Equipment Endpoints

**POST `/backend/equipment/create.php`**
Creates new equipment.

Request:
```json
{
  "dc": "IPEDC",
  "type": "UPS",
  "brand": "Brand G",
  "spec": "Spec F",
  "supplier": "Supplier C",
  "sn": "NE39981514"
}
```

Response:
```json
{
  "success": true,
  "message": "Equipment IPEDC-UPS-00097 successfully created",
  "data": { ... }
}
```

**POST `/backend/equipment/update.php`**
Updates equipment record.

Request:
```json
{
  "id": "IPEDC-UPS-00097",
  "brand": "New Brand",
  "supplier": "New Supplier"
}
```

Response:
```json
{
  "success": true,
  "message": "Equipment updated successfully",
  "data": { ... }
}
```

**POST `/backend/equipment/delete.php`**
Deletes equipment and optionally history.

Request:
```json
{
  "id": "IPEDC-UPS-00097",
  "delete-history": "true"
}
```

**GET `/backend/equipment/get.php?id=IPEDC-UPS-00097`**
Fetches single equipment.

**GET `/backend/equipment/list.php?dc=IPEDC&type=UPS`**
Lists equipment with optional filters.

#### History Endpoints

**POST `/backend/history/create.php`**
Creates history entry.

**POST `/backend/history/delete.php`**
Deletes history entry.

**GET `/backend/history/list.php?equipment_id=IPEDC-UPS-00097`**
Lists history for equipment.

---

## Frontend Architecture

### Core Modules

#### 1. `js/dcclarity-global-var.js`
Constants and configuration only. **No UI logic.**

**Contents**:
- `EQUIPMENT_TYPES` - Type definitions
- `DC_LOCATIONS` - Location options
- `EDITABLE_FIELDS` - Fields that can be edited
- `EQUIPMENT_TABLE_COLUMNS` - Table structure
- `API_ENDPOINTS` - API paths
- `FIELD_LABELS` - Display labels

**Usage**:
```javascript
const types = window.dclarityConfig.EQUIPMENT_TYPES;
```

#### 2. `js/dcfm-api.js`
API communication module. **No UI logic inside.**

**Exposed Methods**:
- `createEquipment(data)` - Create equipment
- `updateEquipment(id, updates)` - Update equipment
- `deleteEquipment(id, deleteHistory)` - Delete equipment
- `getEquipment(id)` - Fetch equipment
- `listEquipment(filters)` - List equipment
- `getHistory(equipmentId)` - Get history
- `addHistory(equipmentId, event, notes)` - Add history
- `deleteHistory(historyId)` - Delete history

**Usage**:
```javascript
try {
  const result = await dclarityAPI.createEquipment(data);
  console.log(result.data);
} catch (error) {
  console.error(error.message);
}
```

#### 3. `js/dcfm-ui.js`
UI and popup handling. **Centralized popup logic.**

**Exposed Methods**:
- `showConfirm(message, onConfirm, onCancel)` - Confirmation popup
- `showSuccess(message, onClose)` - Success popup
- `showError(message, errors, onClose)` - Error popup
- `showLoading(containerId)` - Show loading state
- `hideLoading(containerId)` - Hide loading state
- `enableFields(fieldIds)` - Enable form fields
- `disableFields(fieldIds)` - Disable form fields
- `populateSelect(selectId, items, placeholder)` - Populate dropdown
- `getFieldValue(fieldId)` - Get field value
- `setFieldValue(fieldId, value)` - Set field value
- `clearFields(fieldIds)` - Clear fields

**Popup Types**:
- Confirmation popups (yes/no)
- Success popups (auto-close after 3 seconds)
- Error popups with field-specific errors

**Usage**:
```javascript
ui.showConfirm('Delete equipment?', 
  () => { /* confirm */ },
  () => { /* cancel */ }
);
```

#### 4. `js/dcfm-form.js`
Equipment form page logic.

**Features**:
- Auto-populates dropdowns
- Frontend validation (basic - backend validates)
- Form submission to `backend/equipment/create.php`
- Success popup with equipment ID
- Form reset after successful submission

**Usage**: Auto-initializes on page load

#### 5. `js/dcfm-details.js`
Equipment details page logic.

**Features**:
- Load equipment by ID from URL parameter (?id=XXX)
- Edit mode toggle
- Update equipment with change detection
- Delete equipment with confirmation
- History integration

**Usage**: Auto-initializes on page load

#### 6. `js/dcfm-history.js`
History table rendering and management.

**Features**:
- Dynamically renders history table
- Delete history entries
- Shows change details
- Formatted timestamp display

**Usage**:
```javascript
dclarityHistory.init(equipmentId);
```

---

## Database Structure

### Equipment Database: `dcfm-equipment-db.json`

```json
[
  {
    "id": "IPEDC-UPS-00097",
    "type": "UPS",
    "brand": "Brand G",
    "spec": "Spec F",
    "status": "Decommissioned",
    "location": "Level 8, Datahall 4",
    "dc": "IPEDC",
    "supplier": "Supplier C",
    "vendor": "Vendor E",
    "sn": "NE39981514",
    "asset-tag": 900000067736918,
    "lifespan": 10,
    "installed": "27/10/2001",
    "latest-pm": "02/06/2025",
    "notes": "Additional notes",
    "created-by": "Staff D",
    "date-created": "04:43 01/01/2026"
  }
]
```

### History Database: `dcfm-equipment-history-db.json`

```json
[
  {
    "history_id": "HIS-00001",
    "equipment_id": "IPEDC-UPS-00097",
    "event": "Detail Update",
    "notes": "Changed 'Supplier' from 'A' to 'B';",
    "changes": [
      {
        "field": "Supplier",
        "old": "A",
        "new": "B"
      }
    ],
    "created_by": "Beta Tester",
    "date_created": "10:00 24/05/2026"
  }
]
```

---

## Security Features

1. **File Locking**: All JSON operations use `flock()` to prevent corruption
2. **Input Validation**: All POST/GET data validated on backend
3. **Backend Sequence Generation**: IDs generated only on backend (never by frontend)
4. **Duplicate Prevention**: Serial numbers checked against existing records
5. **Safe JSON Parsing**: Proper error handling for malformed JSON
6. **Atomic Operations**: Temp file writes ensure data integrity

---

## Common Tasks

### Add New Equipment

1. Navigate to `dcfm-equipment-form.html`
2. Fill form (DC Location, Equipment Type, Brand, Spec, Serial Number required)
3. Click Submit
4. Success popup shows equipment ID
5. Form resets

### View Equipment Details

1. From equipment list, click on equipment ID
2. Details page loads with full information
3. Click "Edit" to modify editable fields
4. Click "Update" to save changes
5. History shows all changes

### Update Equipment

1. From details page, click "Edit"
2. Modify editable fields (read-only fields disabled)
3. Click "Update"
4. System detects changes
5. History entry created automatically
6. Page reloads to show updates

### Delete Equipment

1. From details page, click "Delete"
2. Confirm deletion
3. Optionally delete related history
4. Returns to list page

### View Change History

1. On details page, scroll to "Change History" section
2. Shows all events with:
   - Event type
   - Changed fields (old → new)
   - User who made change
   - Timestamp
3. Can delete individual entries

---

## Editable Fields

These fields can be modified after equipment creation:
- Brand
- Specification
- Status
- Location
- Supplier
- Vendor
- Serial Number
- Asset Tag
- Lifespan
- Install Date
- Last Maintenance Date
- Notes

Read-only fields (cannot edit):
- Equipment ID
- Equipment Type
- Datacenter
- Created By
- Date Created

---

## API Response Format

All API responses use standardized format:

**Success Response**:
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { /* response data */ }
}
```

**Error Response**:
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field_name": "Specific error for this field"
  }
}
```

**HTTP Status Codes**:
- 200 - Success
- 201 - Created
- 400 - Bad Request
- 404 - Not Found
- 405 - Method Not Allowed
- 422 - Validation Failed
- 500 - Server Error

---

## Maintenance

### Adding New Equipment Type

1. Edit `js/dcclarity-global-var.js`:
   ```javascript
   { value: 'NEWTYPE', label: 'New Type' }
   ```

2. Edit `backend/core/config.php`:
   ```php
   'NEWTYPE',
   ```

3. Edit `backend/core/validator.php` equipment type validation if needed

### Changing Editable Fields

1. Edit `js/dcclarity-global-var.js`:
   ```javascript
   window.dclarityConfig.EDITABLE_FIELDS = [ ... ];
   ```

2. Edit `backend/core/config.php`:
   ```php
   define('EDITABLE_FIELDS', array( ... ));
   ```

### Modifying API Endpoints

All endpoints are in `backend/equipment/` and `backend/history/` directories. Each endpoint:
1. Includes all necessary core modules
2. Validates input using `validator.php`
3. Uses JSON database functions
4. Returns standardized response

---

## Technology Stack

- **Frontend**: HTML5, CSS3, Vanilla JavaScript (ES6+)
- **Backend**: PHP 7.0+
- **Database**: JSON files
- **No External Dependencies**: All code is vanilla

---

## Best Practices

1. **Always validate on backend** - Never trust frontend validation alone
2. **Use standardized responses** - All endpoints return JSON in same format
3. **Use JSON database functions** - Never manipulate JSON directly
4. **Generate IDs on backend** - Never generate in frontend
5. **Use file locking** - All writes use `flock()`
6. **Centralize UI logic** - Use `dcfm-ui.js` for all popups
7. **Separate concerns** - Each module has single responsibility
8. **Log errors** - Use error logging for debugging

---

## Future Enhancements

- User authentication
- Role-based access control
- Backup and restore functionality
- Export to CSV
- Advanced filtering and search
- Pagination for large datasets
- API rate limiting
- Request logging

---

## Troubleshooting

### Database file permission errors
- Ensure `backend/database/` directory is writable
- Set permissions: `chmod 755 backend/database/`

### JSON corruption
- Check for proper file locking
- Verify JSON syntax in database files
- Use `json_last_error_msg()` for debugging

### Duplicate serial numbers
- System prevents duplicates automatically
- Check database for existing entries
- Delete duplicate then recreate

### Equipment ID conflicts
- Should not occur due to sequence logic
- If encountered, check database for gaps
- Rebuild sequences if needed

---

## Support & Documentation

For detailed API documentation, see individual module comments.
For configuration details, check `backend/core/config.php`.

