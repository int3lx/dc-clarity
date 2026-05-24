/**
 * Equipment Form Module
 * 
 * Contains ONLY:
 * - Equipment form page logic
 * - Form submission
 * - Form reset
 * - Frontend validation (not relied upon - backend validates)
 */

const dclarityForm = (function() {
    'use strict';

    const config = window.dclarityConfig || {};
    const api = window.dclarityAPI;
    const ui = window.dclarityUI;

    let isSubmitting = false;

    /**
     * Initialize form page
     */
    function init() {
        populateFormSelects();
        attachFormListeners();
    }

    /**
     * Populate equipment type and DC location dropdowns
     */
    function populateFormSelects() {
        ui.populateSelect(
            'dc_location',
            config.DC_LOCATIONS,
            'Select Location'
        );

        ui.populateSelect(
            'dcfm_equipment_type',
            config.EQUIPMENT_TYPES,
            'Select Equipment Type'
        );

        ui.populateSelect(
            'dcfm_equipment_status',
            config.EQUIPMENT_STATUSES.map(s => ({ value: s, label: s })),
            'Select Status'
        );
    }

    /**
     * Attach form event listeners
     */
    function attachFormListeners() {
        const form = document.getElementById('equipment-form');
        if (!form) return;

        form.addEventListener('submit', handleFormSubmit);

        const resetBtn = document.getElementById('reset_form');
        if (resetBtn) {
            resetBtn.addEventListener('click', resetForm);
        }
    }

    /**
     * Handle form submission
     */
    async function handleFormSubmit(event) {
        event.preventDefault();

        if (isSubmitting) return;
        isSubmitting = true;

        try {
            const formData = gatherFormData();

            // Frontend validation (basic)
            const validation = validateFormData(formData);
            if (!validation.valid) {
                ui.showError('Please fill in all required fields', validation.errors);
                isSubmitting = false;
                return;
            }

            // Show loading
            ui.showLoading('form-status');

            // Submit to backend
            const result = await api.createEquipment(formData);

            // Success
            const equipmentId = result.data?.id || 'Equipment';
            ui.showSuccess(
                `${equipmentId} successfully added to the record`,
                () => {
                    resetForm();
                }
            );

        } catch (error) {
            console.error('Form submission error:', error);

            let errorMessage = error.message || 'Failed to create equipment';
            let fieldErrors = {};

            // Parse error response if available
            if (error.response && error.response.errors) {
                fieldErrors = error.response.errors;
            }

            ui.showError(errorMessage, fieldErrors);

        } finally {
            ui.hideLoading('form-status');
            isSubmitting = false;
        }
    }

    /**
     * Gather form data
     */
    function gatherFormData() {
        return {
            dc: ui.getFieldValue('dc_location'),
            type: ui.getFieldValue('dcfm_equipment_type'),
            brand: ui.getFieldValue('dcfm_equipment_brand'),
            spec: ui.getFieldValue('dcfm_equipment_spec'),
            status: ui.getFieldValue('dcfm_equipment_status') || 'Active',
            location: ui.getFieldValue('dcfm_equipment_location') || '',
            supplier: ui.getFieldValue('dcfm_equipment_supplier') || '',
            vendor: ui.getFieldValue('dcfm_equipment_vendor') || '',
            sn: ui.getFieldValue('dcfm_equipment_serial'),
            'asset-tag': ui.getFieldValue('dcfm_equipment_asset_tag') || '',
            lifespan: parseInt(ui.getFieldValue('dcfm_equipment_lifespan')) || 0,
            installed: ui.getFieldValue('dcfm_equipment_installed') || '',
            'latest-pm': ui.getFieldValue('dcfm_equipment_latest_pm') || '',
            notes: ui.getFieldValue('dcfm_equipment_notes') || '',
            'created-by': config.CURRENT_USER
        };
    }

    /**
     * Validate form data (frontend validation)
     */
    function validateFormData(data) {
        const errors = {};
        const required = ['dc', 'type', 'brand', 'spec', 'sn'];

        required.forEach(field => {
            if (!data[field] || String(data[field]).trim() === '') {
                errors[field] = 'This field is required';
            }
        });

        return {
            valid: Object.keys(errors).length === 0,
            errors: errors
        };
    }

    /**
     * Reset form to empty state
     */
    function resetForm() {
        const form = document.getElementById('equipment-form');
        if (!form) return;

        form.reset();
        ui.clearFields([
            'dc_location',
            'dcfm_equipment_type',
            'dcfm_equipment_brand',
            'dcfm_equipment_spec',
            'dcfm_equipment_status',
            'dcfm_equipment_location',
            'dcfm_equipment_supplier',
            'dcfm_equipment_vendor',
            'dcfm_equipment_serial',
            'dcfm_equipment_asset_tag',
            'dcfm_equipment_lifespan',
            'dcfm_equipment_installed',
            'dcfm_equipment_latest_pm',
            'dcfm_equipment_notes'
        ]);

        ui.hideCurrentPopup();
    }

    return {
        init,
        resetForm
    };
})();

// Initialize on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    dclarityForm.init();
});
