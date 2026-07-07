/* eslint-env amd */
define([], function() {
    'use strict';

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getItemIndexFromRow(row) {
        if (!row) {
            return -1;
        }

        var index = parseInt(row.getAttribute('data-index'), 10);

        return isNaN(index) ? -1 : index;
    }

    return {
        escapeHtml: escapeHtml,
        getItemIndexFromRow: getItemIndexFromRow
    };
});