/**
 * Equipment Details Module
 * 
 * Contains ONLY:
 * - Equipment details page logic
 * - Edit mode handling
 * - Update handling
 * - Delete handling
 * - Change comparison logic
 */

const dclarityDetails = (function() {
    'use strict';

    const config = window.dclarityConfig || {};
    const api = window.dclarityAPI;
    const ui = window.dclarityUI;

    let currentEquipment = null;
    let isEditing = false;
    let isProcessing = false;

    /**
     * Initialize details page
     */
    async function init() {
        const equipmentId = getEquipmentIdFromUrl();
        if (!equipmentId) {
            ui.showError('Equipment ID not found');
            return;
        }

        await loadEquipment(equipmentId);
        populateSelectFields();
        attachEventListeners();
    }

    /**
     * Get equipment ID from URL query parameter
     */
    function getEquipmentIdFromUrl() {
        const params = new URLSearchParams(window.location.search);
        return params.get('id');
    }

    /**
     * Load equipment data from API
     */
    async function loadEquipment(equipmentId) {
        try {
            ui.showLoading('equipment-details');

            const result = await api.getEquipment(equipmentId);
            currentEquipment = result.data;

            displayEquipmentData(currentEquipment);
            ui.hideLoading('equipment-details');

        } catch (error) {
            console.error('Error loading equipment:', error);
            ui.showError('Failed to load equipment: ' + error.message);
        }
    }

    /**
     * Display equipment data in form fields
     */
    function displayEquipmentData(equipment) {
        if (!equipment) return;

        // Display all fields
        Object.keys(equipment).forEach(key => {
            const fieldId = 'detail_' + key;
            ui.setFieldValue(fieldId, equipment[key]);
        });
    }

    /**
     * Populate select fields
     */
    function populateSelectFields() {
        ui.populateSelect(
            'detail_type',
            config.EQUIPMENT_TYPES
        );

        ui.populateSelect(
            'detail_dc',
            config.DC_LOCATIONS
        );

        ui.populateSelect(
            'detail_status',
            config.EQUIPMENT_STATUSES.map(s => ({ value: s, label: s }))
        );
    }

    /**
     * Attach event listeners
     */
    function attachEventListeners() {
        const editBtn = document.getElementById('edit_details');
        const updateBtn = document.getElementById('update_details');
        const cancelBtn = document.getElementById('cancel_update');
        const deleteBtn = document.getElementById('delete_details');

        if (editBtn) editBtn.addEventListener('click', enterEditMode);
        if (updateBtn) updateBtn.addEventListener('click', handleUpdate);
        if (cancelBtn) cancelBtn.addEventListener('click', exitEditMode);
        if (deleteBtn) deleteBtn.addEventListener('click', handleDelete);
    }

    /**
     * Enter edit mode - enable editable fields
     */
    function enterEditMode() {
        isEditing = true;

        // Disable read-only fields
        const readOnlyFields = ['detail_id', 'detail_dc', 'detail_type', 'detail_date-created', 'detail_created-by'];
        ui.disableFields(readOnlyFields);

        // Enable editable fields
        const editableFields = config.EDITABLE_FIELDS.map(f => 'detail_' + f);
        ui.enableFields(editableFields);

        // Update button visibility
        document.getElementById('edit_details').style.display = 'none';
        document.getElementById('update_details').style.display = 'inline-block';
        document.getElementById('cancel_update').style.display = 'inline-block';
        document.getElementById('delete_details').style.display = 'none';
    }

    /**
     * Exit edit mode - disable editable fields
     */
    function exitEditMode() {
        isEditing = false;

        // Reload page to reset
        const equipmentId = getEquipmentIdFromUrl();
        window.location.href = window.location.pathname + '?id=' + equipmentId;
    }

    /**
     * Handle update
     */
    async function handleUpdate() {
        if (isProcessing) return;
        isProcessing = true;

        try {
            const updates = gatherUpdates();

            if (Object.keys(updates).length === 0) {
                ui.showError('No changes detected');
                isProcessing = false;
                return;
            }

            ui.showConfirm(
                'Are you sure you want to update this equipment?',
                async () => {
                    try {
                        ui.showLoading('equipment-details');

                        updates.id = currentEquipment.id;
                        updates['updated-by'] = config.CURRENT_USER;

                        const result = await api.updateEquipment(currentEquipment.id, updates);

                        ui.showSuccess(
                            'Equipment updated successfully',
                            () => {
                                exitEditMode();
                            }
                        );

                    } catch (error) {
                        console.error('Update error:', error);
                        ui.showError('Failed to update equipment: ' + error.message);
                    } finally {
                        ui.hideLoading('equipment-details');
                        isProcessing = false;
                    }
                }
            );

        } catch (error) {
            console.error('Update preparation error:', error);
            ui.showError(error.message);
            isProcessing = false;
        }
    }

    /**
     * Gather updates from form
     */
    function gatherUpdates() {
        const updates = {};

        config.EDITABLE_FIELDS.forEach(field => {
            const fieldId = 'detail_' + field;
            const newValue = ui.getFieldValue(fieldId);
            const oldValue = currentEquipment[field];

            if (String(newValue) !== String(oldValue)) {
                updates[field] = newValue;
            }
        });

        return updates;
    }

    /**
     * Handle delete
     */
    function handleDelete() {
        ui.showConfirm(
            'Are you sure you want to delete this equipment? This action cannot be undone.',
            () => {
                ui.showConfirm(
                    'Delete related history entries as well?',
                    async () => {
                        await performDelete(true);
                    },
                    async () => {
                        await performDelete(false);
                    }
                );
            }
        );
    }

    /**
     * Perform equipment deletion
     */
    async function performDelete(deleteHistory) {
        if (isProcessing) return;
        isProcessing = true;

        try {
            ui.showLoading('equipment-details');

            const result = await api.deleteEquipment(currentEquipment.id, deleteHistory);

            ui.showSuccess(
                'Equipment deleted successfully',
                () => {
                    window.location.href = 'dcfm-equipment-list.html';
                }
            );

        } catch (error) {
            console.error('Delete error:', error);
            ui.showError('Failed to delete equipment: ' + error.message);

        } finally {
            ui.hideLoading('equipment-details');
            isProcessing = false;
        }
    }

    return {
        init
    };
})();

// Initialize on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    dclarityDetails.init();
});
