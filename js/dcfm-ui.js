/**
 * UI Module
 * 
 * Contains ONLY:
 * - Popup handling
 * - Notification display
 * - Loading states
 * - Enable/disable field handling
 * - Reusable table rendering
 */

const dclarityUI = (function() {
    'use strict';

    let currentPopup = null;
    const config = window.dclarityConfig || {};

    /**
     * Show confirmation popup
     * @param {string} message - Message to display
     * @param {function} onConfirm - Callback on confirm
     * @param {function} onCancel - Callback on cancel
     */
    function showConfirm(message, onConfirm, onCancel) {
        hideCurrentPopup();

        const popup = document.createElement('div');
        popup.className = 'popup popup-confirm';
        popup.innerHTML = `
            <div class="popup-content">
                <h3>Confirm</h3>
                <p>${escapeHtml(message)}</p>
                <div class="popup-actions">
                    <button class="btn btn-primary" id="popup-confirm">Confirm</button>
                    <button class="btn btn-secondary" id="popup-cancel">Cancel</button>
                </div>
            </div>
        `;

        document.body.appendChild(popup);
        currentPopup = popup;

        document.getElementById('popup-confirm').addEventListener('click', () => {
            hideCurrentPopup();
            if (typeof onConfirm === 'function') onConfirm();
        });

        document.getElementById('popup-cancel').addEventListener('click', () => {
            hideCurrentPopup();
            if (typeof onCancel === 'function') onCancel();
        });
    }

    /**
     * Show success popup
     * @param {string} message - Success message
     * @param {function} onClose - Callback on close
     */
    function showSuccess(message, onClose) {
        hideCurrentPopup();

        const popup = document.createElement('div');
        popup.className = 'popup popup-success';
        popup.innerHTML = `
            <div class="popup-content">
                <h3>Success</h3>
                <p>${escapeHtml(message)}</p>
                <button class="btn btn-primary" id="popup-close">Close</button>
            </div>
        `;

        document.body.appendChild(popup);
        currentPopup = popup;

        const closeBtn = document.getElementById('popup-close');
        closeBtn.addEventListener('click', () => {
            hideCurrentPopup();
            if (typeof onClose === 'function') onClose();
        });

        // Auto-close after timeout
        const timeout = config.POPUP_TIMEOUT || 3000;
        const autoCloseTimer = setTimeout(() => {
            hideCurrentPopup();
            if (typeof onClose === 'function') onClose();
        }, timeout);

        popup.autoCloseTimer = autoCloseTimer;
    }

    /**
     * Show error popup
     * @param {string} message - Error message
     * @param {object} errors - Field-specific errors
     * @param {function} onClose - Callback on close
     */
    function showError(message, errors = {}, onClose) {
        hideCurrentPopup();

        let errorHtml = `<p>${escapeHtml(message)}</p>`;

        if (Object.keys(errors).length > 0) {
            errorHtml += '<ul class="error-list">';
            Object.keys(errors).forEach(field => {
                errorHtml += `<li><strong>${escapeHtml(field)}:</strong> ${escapeHtml(errors[field])}</li>`;
            });
            errorHtml += '</ul>';
        }

        const popup = document.createElement('div');
        popup.className = 'popup popup-error';
        popup.innerHTML = `
            <div class="popup-content">
                <h3>Error</h3>
                ${errorHtml}
                <button class="btn btn-primary" id="popup-close">Close</button>
            </div>
        `;

        document.body.appendChild(popup);
        currentPopup = popup;

        document.getElementById('popup-close').addEventListener('click', () => {
            hideCurrentPopup();
            if (typeof onClose === 'function') onClose();
        });
    }

    /**
     * Hide current popup
     */
    function hideCurrentPopup() {
        if (currentPopup) {
            if (currentPopup.autoCloseTimer) {
                clearTimeout(currentPopup.autoCloseTimer);
            }
            currentPopup.remove();
            currentPopup = null;
        }
    }

    /**
     * Show loading state
     * @param {string} containerId - Container element ID
     */
    function showLoading(containerId) {
        const container = document.getElementById(containerId);
        if (!container) {
            return;
        }

        if (container.querySelector('.loading-overlay')) {
            return;
        }

        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = '<div class="loading">Loading...</div>';
        container.appendChild(overlay);
        container.classList.add('loading-state');
    }

    /**
     * Hide loading state
     * @param {string} containerId - Container element ID
     */
    function hideLoading(containerId) {
        const container = document.getElementById(containerId);
        if (!container) {
            return;
        }

        const overlay = container.querySelector('.loading-overlay');
        if (overlay) {
            overlay.remove();
        }

        container.classList.remove('loading-state');
    }

    /**
     * Enable form fields
     * @param {array} fieldIds - Array of field IDs to enable
     */
    function enableFields(fieldIds) {
        if (!Array.isArray(fieldIds)) return;

        fieldIds.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.disabled = false;
                field.classList.remove('disabled');
            }
        });
    }

    /**
     * Disable form fields
     * @param {array} fieldIds - Array of field IDs to disable
     */
    function disableFields(fieldIds) {
        if (!Array.isArray(fieldIds)) return;

        fieldIds.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.disabled = true;
                field.classList.add('disabled');
            }
        });
    }

    /**
     * Populate select dropdown
     * @param {string} selectId - Select element ID
     * @param {array} items - Array of {value, label} objects
     * @param {string} placeholder - Placeholder text
     */
    function populateSelect(selectId, items, placeholder = '') {
        const select = document.getElementById(selectId);
        if (!select || !Array.isArray(items)) return;

        select.innerHTML = '';

        if (placeholder) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = placeholder;
            select.appendChild(option);
        }

        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.value;
            option.textContent = item.label;
            select.appendChild(option);
        });
    }

    /**
     * Escape HTML special characters
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    /**
     * Get form field value
     * @param {string} fieldId - Field ID
     * @returns {string} Field value
     */
    function getFieldValue(fieldId) {
        const field = document.getElementById(fieldId);
        return field ? field.value : '';
    }

    /**
     * Set form field value
     * @param {string} fieldId - Field ID
     * @param {string} value - Value to set
     */
    function setFieldValue(fieldId, value) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.value = value;
        }
    }

    /**
     * Clear form fields
     * @param {array} fieldIds - Array of field IDs
     */
    function clearFields(fieldIds) {
        if (!Array.isArray(fieldIds)) return;

        fieldIds.forEach(fieldId => {
            setFieldValue(fieldId, '');
        });
    }

    return {
        showConfirm,
        showSuccess,
        showError,
        hideCurrentPopup,
        showLoading,
        hideLoading,
        enableFields,
        disableFields,
        populateSelect,
        escapeHtml,
        getFieldValue,
        setFieldValue,
        clearFields
    };
})();
