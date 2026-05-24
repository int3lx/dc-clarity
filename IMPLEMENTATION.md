# DC Clarity - Implementation Guide

## Quick Start

### Step 1: Verify Directory Structure
Ensure all files are in the correct locations:
```
backend/
├── core/
│   ├── config.php
│   ├── response.php
│   ├── json-db.php
│   ├── validator.php
│   ├── sequence.php
│   ├── history.php
│   └── helpers.php
├── equipment/
│   ├── create.php
│   ├── update.php
│   ├── delete.php
│   ├── get.php
│   └── list.php
├── history/
│   ├── create.php
│   ├── delete.php
│   └── list.php
└── database/
    ├── dcfm-equipment-db.json
    └── dcfm-equipment-history-db.json

js/
├── dcclarity-global-var.js
├── dcfm-api.js
├── dcfm-ui.js
├── dcfm-form.js
├── dcfm-details.js
└── dcfm-history.js
```

### Step 2: Include JavaScript in HTML Pages

**All HTML pages should include in this order:**

```html
<!-- Configuration & Constants -->
<script src="js/dcclarity-global-var.js"></script>

<!-- API Communication -->
<script src="js/dcfm-api.js"></script>

<!-- UI & Popups -->
<script src="js/dcfm-ui.js"></script>

<!-- Page-specific modules (one of these per page) -->
<script src="js/dcfm-form.js"></script>        <!-- for equipment form page -->
<script src="js/dcfm-details.js"></script>     <!-- for details page -->
<script src="js/dcfm-history.js"></script>     <!-- for history on details page -->
```

### Step 3: Update Equipment Form Page

File: `dcfm-equipment-form.html`

**In the `<head>` section, add:**
```html
<script src="js/dcclarity-global-var.js"></script>
<script src="js/dcfm-api.js"></script>
<script src="js/dcfm-ui.js"></script>
<script src="js/dcfm-form.js"></script>
```

**Form element IDs must match:**
- `dc_location` - DC Location dropdown
- `dcfm_equipment_type` - Equipment Type dropdown
- `dcfm_equipment_brand` - Brand input
- `dcfm_equipment_spec` - Specification input
- `dcfm_equipment_status` - Status dropdown
- `dcfm_equipment_location` - Detailed Location input
- `dcfm_equipment_supplier` - Supplier input
- `dcfm_equipment_vendor` - Vendor input
- `dcfm_equipment_serial` - Serial Number input
- `dcfm_equipment_asset_tag` - Asset Tag input
- `dcfm_equipment_lifespan` - Lifespan input
- `dcfm_equipment_installed` - Install Date input
- `dcfm_equipment_latest_pm` - Last PM Date input
- `dcfm_equipment_notes` - Notes textarea
- `equipment-form` - Form element
- `reset_form` - Reset button (optional)

### Step 4: Update Equipment Details Page

File: `dcfm-equipment-details.html`

**In the `<head>` section, add:**
```html
<script src="js/dcclarity-global-var.js"></script>
<script src="js/dcfm-api.js"></script>
<script src="js/dcfm-ui.js"></script>
<script src="js/dcfm-details.js"></script>
<script src="js/dcfm-history.js"></script>
```

**Required element IDs:**
- `detail_id` - Equipment ID (read-only)
- `detail_type` - Type dropdown
- `detail_dc` - Datacenter dropdown
- `detail_brand` - Brand input
- `detail_spec` - Spec input
- etc. (for all equipment fields prefixed with `detail_`)
- `edit_details` - Edit button
- `update_details` - Update button
- `cancel_update` - Cancel button
- `delete_details` - Delete button
- `equipment-details` - Container for page
- `history-container` - History table container

### Step 5: Update Equipment List Page

File: `dcfm-equipment-list.html`

**In the `<head>` section, add:**
```html
<script src="js/dcclarity-global-var.js"></script>
<script src="js/dcfm-api.js"></script>
<script src="js/dcfm-ui.js"></script>
```

**Required elements:**
- Filter dropdowns for DC location and type
- Load/Filter button
- Equipment table container

## API Usage Examples

### Create Equipment
```javascript
async function addEquipment() {
    const equipment = {
        dc: 'IPEDC',
        type: 'UPS',
        brand: 'Eaton',
        spec: '100kVA',
        supplier: 'Supplier A',
        sn: 'ABC123456'
    };
    
    try {
        const result = await dclarityAPI.createEquipment(equipment);
        console.log('Created:', result.data.id);
        ui.showSuccess('Equipment created successfully');
    } catch (error) {
        ui.showError(error.message);
    }
}
```

### Get Equipment
```javascript
async function fetchEquipment() {
    try {
        const result = await dclarityAPI.getEquipment('IPEDC-UPS-00097');
        console.log('Equipment:', result.data);
    } catch (error) {
        ui.showError(error.message);
    }
}
```

### List Equipment
```javascript
async function listEquipment() {
    try {
        const result = await dclarityAPI.listEquipment({
            dc: 'IPEDC',
            type: 'UPS'
        });
        console.log('Found:', result.data.total, 'equipment');
    } catch (error) {
        ui.showError(error.message);
    }
}
```

### Update Equipment
```javascript
async function updateEquipment() {
    const updates = {
        brand: 'New Brand',
        supplier: 'New Supplier'
    };
    
    try {
        const result = await dclarityAPI.updateEquipment('IPEDC-UPS-00097', updates);
        console.log('Updated:', result.data);
    } catch (error) {
        ui.showError(error.message);
    }
}
```

### Delete Equipment
```javascript
async function deleteEquipment() {
    try {
        const result = await dclarityAPI.deleteEquipment('IPEDC-UPS-00097', true);
        console.log('Deleted:', result.data.id);
    } catch (error) {
        ui.showError(error.message);
    }
}
```

## UI Component Usage

### Show Popup
```javascript
// Confirmation
ui.showConfirm('Delete this item?', 
    () => { console.log('Confirmed'); },
    () => { console.log('Cancelled'); }
);

// Success
ui.showSuccess('Operation successful', () => {
    // Called after popup closes
});

// Error
ui.showError('Something went wrong', {
    email: 'Invalid email format',
    name: 'Name is required'
});
```

### Form Field Operations
```javascript
// Get value
const value = ui.getFieldValue('fieldId');

// Set value
ui.setFieldValue('fieldId', 'new value');

// Clear fields
ui.clearFields(['field1', 'field2']);

// Enable/Disable
ui.enableFields(['field1', 'field2']);
ui.disableFields(['field1', 'field2']);

// Populate select
ui.populateSelect('selectId', 
    [
        { value: 'opt1', label: 'Option 1' },
        { value: 'opt2', label: 'Option 2' }
    ],
    'Select an option'
);
```

### Loading States
```javascript
ui.showLoading('containerId');
// ... do work ...
ui.hideLoading('containerId');
```

## Configuration

### Change Current User
Edit `js/dcclarity-global-var.js`:
```javascript
window.dclarityConfig.CURRENT_USER = 'New User Name';
```

### Add Equipment Type
Edit `js/dcclarity-global-var.js` and `backend/core/config.php`:
```javascript
// In dcclarity-global-var.js
{ value: 'NEWTYPE', label: 'New Equipment Type' }
```

### Change Editable Fields
Edit `js/dcclarity-global-var.js` and `backend/core/config.php`:
```javascript
window.dclarityConfig.EDITABLE_FIELDS = [
    'brand',
    'spec',
    // ... add or remove fields
];
```

## Security Best Practices

1. **Always validate server-side**: Frontend validation is convenience only
2. **Use the standard response format**: All endpoints return standardized JSON
3. **Check for errors**: Always wrap API calls in try-catch
4. **Sanitize output**: Use `ui.escapeHtml()` for user-generated content
5. **Handle file locking**: JSON operations automatically handle locking
6. **Verify user permissions**: Consider adding authentication layer

## Common Issues

### "Cannot open file for reading"
- Check database directory permissions
- Ensure `backend/database/` is readable by PHP

### "Invalid JSON format"
- Verify database files contain valid JSON
- Check for encoding issues

### "Duplicate serial number"
- Clear existing record with that serial number
- Or edit to use different serial number

### API returns 404
- Verify endpoint paths are correct
- Check equipment ID format
- Ensure equipment exists in database

### ID generation issues
- Check that sequences are incrementing properly
- Verify equipment database is not corrupted
- Review sequence numbering in backend/core/sequence.php

## Testing

### Test Equipment Creation
1. Go to form page
2. Fill all required fields
3. Submit
4. Verify success message shows new ID
5. Check database for new entry

### Test Equipment Update
1. Go to details page
2. Click Edit
3. Change one field
4. Click Update
5. Verify change history shows the update
6. Check old → new values

### Test History Logging
1. Make multiple changes to equipment
2. View history section
3. Verify all changes are logged
4. Check timestamps and users

### Test Error Handling
1. Try creating duplicate serial number
2. Try updating with invalid data
3. Try deleting non-existent equipment
4. Verify error messages appear

## Performance Considerations

1. **Large datasets**: Consider adding pagination
2. **Locking conflicts**: Retry logic handles most cases
3. **Concurrent writes**: File locking prevents corruption
4. **Database size**: No hard limits, but keep backups

## Backup & Recovery

### Backup Database
```bash
cp backend/database/dcfm-equipment-db.json backup/dcfm-equipment-db.json.bak
cp backend/database/dcfm-equipment-history-db.json backup/dcfm-equipment-history-db.json.bak
```

### Restore Database
```bash
cp backup/dcfm-equipment-db.json.bak backend/database/dcfm-equipment-db.json
cp backup/dcfm-equipment-history-db.json.bak backend/database/dcfm-equipment-history-db.json
```

## Next Steps

1. Update all HTML pages with new JavaScript includes
2. Verify element IDs match between HTML and JavaScript
3. Test all workflows (create, read, update, delete)
4. Verify history tracking works correctly
5. Test error handling and validation
6. Deploy to production environment

