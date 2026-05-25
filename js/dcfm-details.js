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
            ui.showLoading('equipment-details-container');

            const result = await api.getEquipment(equipmentId);
            currentEquipment = result.data;

            displayEquipmentData(currentEquipment);
            dclarityHistory.init(equipmentId);
            ui.hideLoading('equipment-details-container');

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

        // Map equipment fields to existing HTML element IDs
        const mapping = {
            id: 'dcfm_equipment_id',
            type: 'dcfm_equipment_type',
            brand: 'dcfm_equipment_brand',
            spec: 'dcfm_equipment_spec',
            status: 'dcfm_equipment_status',
            location: 'dcfm_equipment_detailed_loc',
            dc: 'dc_location',
            supplier: 'dcfm_equipment_supplier',
            vendor: 'dcfm_equipment_current_vendor',
            sn: 'dcfm_equipment_serial_number',
            'asset-tag': 'dcfm_equipment_tm_asset_number',
            lifespan: 'dcfm_equipment_designed_lifespan',
            installed: 'dcfm_equipment_install_date',
            'latest-pm': 'dcfm_equipment_last_maintenance_date',
            notes: 'dcfm_equipment_note',
            'created-by': 'dcfm_equipment_create_by',
            'date-created': 'dcfm_equipment_created_date'
        };

        Object.keys(mapping).forEach(key => {
            const fid = mapping[key];
            ui.setFieldValue(fid, equipment[key]);
        });
    }

    /**
     * Populate select fields
     */
    function populateSelectFields() {
        ui.populateSelect('dcfm_equipment_type', config.EQUIPMENT_TYPES, 'Select Equipment Type');
        ui.populateSelect('dc_location', config.DC_LOCATIONS, 'Select Location');
        ui.populateSelect('dcfm_equipment_status', config.EQUIPMENT_STATUSES.map(s => ({ value: s, label: s })), 'Select Status');
    }

    /**
     * Attach event listeners
     */
    function attachEventListeners() {
        const editBtn = document.getElementById('edit_details');
        const updateBtn = document.getElementById('update_details');
        const cancelBtn = document.getElementById('cancel_update');
        const deleteBtn = document.getElementById('delete_details');
        const historyForm = document.getElementById('equipment-history-form');

        if (editBtn) editBtn.addEventListener('click', enterEditMode);
        if (updateBtn) updateBtn.addEventListener('click', handleUpdate);
        if (cancelBtn) cancelBtn.addEventListener('click', exitEditMode);
        if (deleteBtn) deleteBtn.addEventListener('click', handleDelete);
        if (historyForm) historyForm.addEventListener('submit', handleHistorySubmit);
    }

    /**
     * Enter edit mode - enable editable fields
     */
    function enterEditMode() {
        isEditing = true;

        // Disable read-only fields (map to DOM IDs)
        const readOnlyFields = [
            'dcfm_equipment_id',
            'dc_location',
            'dcfm_equipment_type',
            'dcfm_equipment_created_date',
            'dcfm_equipment_create_by'
        ];
        ui.disableFields(readOnlyFields);

        // Enable editable fields (map config keys to DOM IDs)
        const fieldMap = {
            brand: 'dcfm_equipment_brand',
            spec: 'dcfm_equipment_spec',
            status: 'dcfm_equipment_status',
            location: 'dcfm_equipment_detailed_loc',
            supplier: 'dcfm_equipment_supplier',
            vendor: 'dcfm_equipment_current_vendor',
            sn: 'dcfm_equipment_serial_number',
            'asset-tag': 'dcfm_equipment_tm_asset_number',
            lifespan: 'dcfm_equipment_designed_lifespan',
            installed: 'dcfm_equipment_install_date',
            'latest-pm': 'dcfm_equipment_last_maintenance_date',
            notes: 'dcfm_equipment_note'
        };

        const editableFields = config.EDITABLE_FIELDS
            .map(f => fieldMap[f])
            .filter(Boolean);
        ui.enableFields(editableFields);

        // Update button states
        const editBtn = document.getElementById('edit_details');
        const updateBtn = document.getElementById('update_details');
        const cancelBtn = document.getElementById('cancel_update');
        const deleteBtn = document.getElementById('delete_details');

        if (editBtn) editBtn.disabled = true;
        if (updateBtn) updateBtn.disabled = false;
        if (cancelBtn) cancelBtn.disabled = false;
        if (deleteBtn) deleteBtn.disabled = true;
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
                        ui.showLoading('equipment-details-container');

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
                        ui.hideLoading('equipment-details-container');
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

        const fieldMap = {
            brand: 'dcfm_equipment_brand',
            spec: 'dcfm_equipment_spec',
            status: 'dcfm_equipment_status',
            location: 'dcfm_equipment_detailed_loc',
            supplier: 'dcfm_equipment_supplier',
            vendor: 'dcfm_equipment_current_vendor',
            sn: 'dcfm_equipment_serial_number',
            'asset-tag': 'dcfm_equipment_tm_asset_number',
            lifespan: 'dcfm_equipment_designed_lifespan',
            installed: 'dcfm_equipment_install_date',
            'latest-pm': 'dcfm_equipment_last_maintenance_date',
            notes: 'dcfm_equipment_note'
        };

        config.EDITABLE_FIELDS.forEach(field => {
            const fieldId = fieldMap[field];
            if (!fieldId) return;
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
            ui.showLoading('equipment-details-container');

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
            ui.hideLoading('equipment-details-container');
            isProcessing = false;
        }
    }

    async function handleHistorySubmit(event) {
        event.preventDefault();
        if (!currentEquipment || !currentEquipment.id) {
            ui.showError('Unable to add history: equipment not loaded');
            return;
        }

        const historyEvent = ui.getFieldValue('equipment_history_event');
        const historyNotes = ui.getFieldValue('equipment_history_desc');
        const historyDate = ui.getFieldValue('equipment_history_date');

        if (!historyEvent || !historyNotes) {
            ui.showError('Event and description are required');
            return;
        }

        try {
            ui.showLoading('history-container');
            await dclarityHistory.addEntry(currentEquipment.id, historyEvent, historyNotes, historyDate);

            ui.showSuccess('History event added successfully');

            if (historyEvent.toLowerCase().trim() === 'preventive maintenance') {
                await loadEquipment(currentEquipment.id);
            }

            document.getElementById('equipment-history-form')?.reset();
        } catch (error) {
            console.error('History submission error:', error);
            ui.showError('Failed to add history event: ' + error.message);
        } finally {
            ui.hideLoading('history-container');
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
