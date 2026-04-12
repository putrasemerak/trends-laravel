/**
 * AIN System — Theme Manager
 * Persists theme choice in localStorage and applies on load.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'ain-theme';

    function getPreferred() {
        return localStorage.getItem(STORAGE_KEY) ||
            (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    }

    function apply(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(STORAGE_KEY, theme);
        // Update all toggle icons
        document.querySelectorAll('.theme-icon').forEach(function (el) {
            el.className = theme === 'dark'
                ? 'bi bi-sun-fill theme-icon'
                : 'bi bi-moon-fill theme-icon';
        });
    }

    function toggle() {
        var current = document.documentElement.getAttribute('data-theme') || 'light';
        apply(current === 'dark' ? 'light' : 'dark');
    }

    // Apply immediately (before paint)
    apply(getPreferred());

    // Bind toggle buttons once DOM is ready
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-toggle-theme]').forEach(function (btn) {
            btn.addEventListener('click', toggle);
        });
        // Re-apply icons after DOM
        apply(document.documentElement.getAttribute('data-theme') || 'light');
    });

    // Expose globally if needed
    window.AINTheme = { toggle: toggle, apply: apply };
})();
