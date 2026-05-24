/**
 * API Communication Module
 * 
 * Contains ONLY:
 * - Fetch requests
 * - API communication
 * - Response parsing
 * - Request helpers
 * 
 * NO UI logic inside this file.
 */

const dclarityAPI = (function() {
    'use strict';

    const config = window.dclarityConfig || {};

    /**
     * Make generic API request
     * @param {string} endpoint - API endpoint path
     * @param {string} method - HTTP method (GET, POST)
     * @param {object} data - Request data
     * @returns {Promise<object>} Response data
     */
    async function request(endpoint, method = 'GET', data = null) {
        try {
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };

            if (method === 'POST' && data) {
                options.body = JSON.stringify(data);
            }

            // For GET requests, append data as query string
            let url = endpoint;
            if (method === 'GET' && data) {
                const params = new URLSearchParams();
                Object.keys(data).forEach(key => {
                    params.append(key, data[key]);
                });
                url += '?' + params.toString();
            }

            const response = await fetch(url, options);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'API request failed');
            }

            return result;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    /**
     * Create equipment
     * @param {object} equipment - Equipment data
     * @returns {Promise<object>} Created equipment
     */
    async function createEquipment(equipment) {
        const endpoint = config.API_ENDPOINTS?.equipment?.create || 'backend/equipment/create.php';
        return request(endpoint, 'POST', equipment);
    }

    /**
     * Update equipment
     * @param {string} equipmentId - Equipment ID
     * @param {object} updates - Equipment updates
     * @returns {Promise<object>} Updated equipment
     */
    async function updateEquipment(equipmentId, updates) {
        const endpoint = config.API_ENDPOINTS?.equipment?.update || 'backend/equipment/update.php';
        const data = { id: equipmentId, ...updates };
        return request(endpoint, 'POST', data);
    }

    /**
     * Delete equipment
     * @param {string} equipmentId - Equipment ID
     * @param {boolean} deleteHistory - Whether to delete related history
     * @returns {Promise<object>} Deletion result
     */
    async function deleteEquipment(equipmentId, deleteHistory = false) {
        const endpoint = config.API_ENDPOINTS?.equipment?.delete || 'backend/equipment/delete.php';
        const data = {
            id: equipmentId,
            'delete-history': deleteHistory ? 'true' : 'false',
            'deleted-by': config.CURRENT_USER
        };
        return request(endpoint, 'POST', data);
    }

    /**
     * Get single equipment
     * @param {string} equipmentId - Equipment ID
     * @returns {Promise<object>} Equipment data
     */
    async function getEquipment(equipmentId) {
        const endpoint = config.API_ENDPOINTS?.equipment?.get || 'backend/equipment/get.php';
        return request(endpoint, 'GET', { id: equipmentId });
    }

    /**
     * List equipment with filters
     * @param {object} filters - Filter parameters
     * @returns {Promise<object>} Equipment list
     */
    async function listEquipment(filters = {}) {
        const endpoint = config.API_ENDPOINTS?.equipment?.list || 'backend/equipment/list.php';
        return request(endpoint, 'GET', filters);
    }

    /**
     * Get equipment history
     * @param {string} equipmentId - Equipment ID
     * @returns {Promise<object>} History entries
     */
    async function getHistory(equipmentId) {
        const endpoint = config.API_ENDPOINTS?.history?.list || 'backend/history/list.php';
        return request(endpoint, 'GET', { equipment_id: equipmentId });
    }

    /**
     * Add history entry
     * @param {string} equipmentId - Equipment ID
     * @param {string} event - Event type
     * @param {string} notes - Event notes
     * @returns {Promise<object>} Created history entry
     */
    async function addHistory(equipmentId, event, notes = '') {
        const endpoint = config.API_ENDPOINTS?.history?.create || 'backend/history/create.php';
        const data = {
            equipment_id: equipmentId,
            event: event,
            notes: notes,
            created_by: config.CURRENT_USER
        };
        return request(endpoint, 'POST', data);
    }

    /**
     * Delete history entry
     * @param {string} historyId - History ID
     * @returns {Promise<object>} Deletion result
     */
    async function deleteHistory(historyId) {
        const endpoint = config.API_ENDPOINTS?.history?.delete || 'backend/history/delete.php';
        const data = {
            history_id: historyId,
            deleted_by: config.CURRENT_USER
        };
        return request(endpoint, 'POST', data);
    }

    return {
        request,
        createEquipment,
        updateEquipment,
        deleteEquipment,
        getEquipment,
        listEquipment,
        getHistory,
        addHistory,
        deleteHistory
    };
})();
