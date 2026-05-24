/**
 * Equipment List Module
 *
 * Contains ONLY:
 * - Equipment list page logic
 * - Filter selection handling
 * - Table rendering
 * - Pagination-ready structure
 */

const dclarityList = (function() {
    'use strict';

    const config = window.dclarityConfig || {};
    const api = window.dclarityAPI;
    const ui = window.dclarityUI;

    function init() {
        populateFilterSelects();
        attachListeners();
    }

    function populateFilterSelects() {
        ui.populateSelect('dc_location', config.DC_LOCATIONS, 'Select Location');
        ui.populateSelect('dcfm_equipment_type', config.EQUIPMENT_TYPES, 'Select Equipment Type');
    }

    function attachListeners() {
        const loadBtn = document.getElementById('load_list_btn');
        if (loadBtn) {
            loadBtn.addEventListener('click', handleLoadList);
        }
    }

    async function handleLoadList(event) {
        event.preventDefault();

        const filters = {
            dc: ui.getFieldValue('dc_location'),
            type: ui.getFieldValue('dcfm_equipment_type')
        };

        ui.showLoading('equipment-table');
        try {
            const result = await api.listEquipment(filters);
            const equipment = result.data?.equipment || [];
            renderEquipmentTable(equipment);
            updateStats(equipment.length, result.data?.total || equipment.length);

        } catch (error) {
            console.error('Error loading equipment list:', error);
            ui.showError(error.message || 'Failed to load equipment list');
        } finally {
            ui.hideLoading('equipment-table');
        }
    }

    function renderEquipmentTable(items) {
        const table = document.getElementById('equipment-table');
        if (!table) return;

        const thead = table.querySelector('thead');
        const tbody = table.querySelector('tbody');
        thead.innerHTML = '';
        tbody.innerHTML = '';

        if (!Array.isArray(items) || items.length === 0) {
            thead.innerHTML = '<tr><th>No equipment found</th></tr>';
            tbody.innerHTML = '<tr><td colspan="100">No equipment records match the selected filters.</td></tr>';
            return;
        }

        const columns = config.EQUIPMENT_TABLE_COLUMNS || [];
        const headerRow = document.createElement('tr');
        columns.forEach(column => {
            const th = document.createElement('th');
            th.textContent = column.label;
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);

        items.forEach(item => {
            const row = document.createElement('tr');
            columns.forEach(column => {
                const td = document.createElement('td');
                if (column.key === 'actions') {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = 'View';
                    button.className = 'btn btn-sm btn-primary';
                    button.addEventListener('click', () => {
                        window.location.href = `dcfm-equipment-details.html?id=${encodeURIComponent(item.id)}`;
                    });
                    td.appendChild(button);
                } else {
                    td.textContent = item[column.key] ?? '';
                }
                row.appendChild(td);
            });
            tbody.appendChild(row);
        });
    }

    function updateStats(count, total) {
        const statsEl = document.getElementById('equipment-list-stats');
        if (!statsEl) return;
        statsEl.value = `${count} of ${total} equipment loaded`;
    }

    return {
        init
    };
})();

// Initialize on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    dclarityList.init();
});
