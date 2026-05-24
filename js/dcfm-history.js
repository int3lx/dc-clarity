/**
 * Equipment History Module
 * 
 * Contains ONLY:
 * - History table rendering
 * - History delete handling
 * - History add event handling
 */

const dclarityHistory = (function() {
    'use strict';

    const config = window.dclarityConfig || {};
    const api = window.dclarityAPI;
    const ui = window.dclarityUI;

    let equipmentHistory = [];

    /**
     * Initialize history module
     */
    function init(equipmentId) {
        if (!equipmentId) return;
        loadHistory(equipmentId);
    }

    /**
     * Load equipment history
     */
    async function loadHistory(equipmentId) {
        try {
            const result = await api.getHistory(equipmentId);
            equipmentHistory = result.data?.history || [];
            renderHistoryTable();

        } catch (error) {
            console.error('Error loading history:', error);
            document.getElementById('history-container').innerHTML =
                '<p class="error">Failed to load history</p>';
        }
    }

    /**
     * Render history table
     */
    function renderHistoryTable() {
        const container = document.getElementById('history-container');
        if (!container) return;

        if (equipmentHistory.length === 0) {
            container.innerHTML = '<p>No history records found</p>';
            return;
        }

        let html = '<table class="history-table"><thead><tr>';

        // Add column headers
        config.HISTORY_TABLE_COLUMNS.forEach(col => {
            if (col.key !== 'actions') {
                html += `<th>${ui.escapeHtml(col.label)}</th>`;
            }
        });
        html += '<th>Actions</th></tr></thead><tbody>';

        // Add rows
        equipmentHistory.forEach(entry => {
            html += renderHistoryRow(entry);
        });

        html += '</tbody></table>';
        container.innerHTML = html;

        // Attach delete handlers
        attachDeleteHandlers();
    }

    /**
     * Render single history row
     */
    function renderHistoryRow(entry) {
        const changesSummary = entry.changes && Array.isArray(entry.changes)
            ? entry.changes.map(c => `${c.field}: ${c.old} → ${c.new}`).join('; ')
            : '';

        return `
            <tr class="history-row" data-history-id="${ui.escapeHtml(entry.history_id || '')}">
                <td>${ui.escapeHtml(entry.event || '')}</td>
                <td>${ui.escapeHtml(entry.notes || '')}</td>
                <td>${ui.escapeHtml(changesSummary)}</td>
                <td>${ui.escapeHtml(entry.created_by || entry['created-by'] || '')}</td>
                <td>${ui.escapeHtml(entry.date_created || entry['date-created'] || '')}</td>
                <td>
                    <button class="btn btn-sm btn-danger delete-history-btn" 
                            data-history-id="${ui.escapeHtml(entry.history_id || '')}">
                        Delete
                    </button>
                </td>
            </tr>
        `;
    }

    /**
     * Attach delete button handlers
     */
    function attachDeleteHandlers() {
        const deleteButtons = document.querySelectorAll('.delete-history-btn');

        deleteButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const historyId = this.getAttribute('data-history-id');
                handleDeleteHistory(historyId);
            });
        });
    }

    /**
     * Handle history delete
     */
    function handleDeleteHistory(historyId) {
        ui.showConfirm(
            'Are you sure you want to delete this history entry?',
            async () => {
                try {
                    ui.showLoading('history-container');

                    await api.deleteHistory(historyId);

                    ui.showSuccess('History entry deleted successfully');

                    // Reload history
                    const equipmentId = new URLSearchParams(window.location.search).get('id');
                    if (equipmentId) {
                        await loadHistory(equipmentId);
                    }

                } catch (error) {
                    console.error('Error deleting history:', error);
                    ui.showError('Failed to delete history entry: ' + error.message);

                } finally {
                    ui.hideLoading('history-container');
                }
            }
        );
    }

    /**
     * Add history entry (used from outside this module)
     */
    async function addEntry(equipmentId, event, notes = '') {
        try {
            await api.addHistory(equipmentId, event, notes);

            // Reload history
            await loadHistory(equipmentId);

        } catch (error) {
            console.error('Error adding history:', error);
            ui.showError('Failed to add history entry: ' + error.message);
        }
    }

    return {
        init,
        loadHistory,
        addEntry
    };
})();
