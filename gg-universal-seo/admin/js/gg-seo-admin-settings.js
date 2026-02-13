/**
 * GG Universal SEO — Settings page JS (locale repeater).
 *
 * @package GG_Universal_SEO
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var container = document.getElementById('gg-seo-locales-repeater');
        var addBtn    = document.getElementById('gg-seo-add-locale');

        if (!container || !addBtn) {
            return;
        }

        /**
         * Return the next index for a new row.
         */
        function getNextIndex() {
            var rows = container.querySelectorAll('.gg-seo-locale-row');
            var max  = -1;
            rows.forEach(function (row) {
                var idx = parseInt(row.getAttribute('data-index'), 10);
                if (!isNaN(idx) && idx > max) {
                    max = idx;
                }
            });
            return max + 1;
        }

        /**
         * Add a new empty locale row.
         */
        addBtn.addEventListener('click', function () {
            var index = getNextIndex();
            var optionKey = 'gg_seo_supported_locales';

            var row = document.createElement('div');
            row.className = 'gg-seo-locale-row';
            row.setAttribute('data-index', index);

            row.innerHTML =
                '<input type="text" name="' + optionKey + '[' + index + '][code]" value="" placeholder="Locale Code (e.g. en_US)" class="regular-text gg-seo-input-code" />' +
                '<input type="text" name="' + optionKey + '[' + index + '][label]" value="" placeholder="Label (e.g. English)" class="regular-text gg-seo-input-label" />' +
                '<button type="button" class="button gg-seo-remove-locale" title="Remove">&times;</button>';

            container.appendChild(row);
        });

        /**
         * Remove a locale row via event delegation.
         */
        container.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('gg-seo-remove-locale')) {
                var row  = e.target.closest('.gg-seo-locale-row');
                var rows = container.querySelectorAll('.gg-seo-locale-row');

                // Keep at least one row.
                if (rows.length > 1 && row) {
                    row.remove();
                }
            }
        });
    });
})();
