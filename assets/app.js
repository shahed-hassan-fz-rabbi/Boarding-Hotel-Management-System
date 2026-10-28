/**
 * Boarding Hotel Management System — application behaviors
 * Theme handling: persisted preference > system preference, no flash on load
 * (the early-init inline script in <head> sets data-theme before first paint).
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'bhm-theme';

    function currentTheme() {
        return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    function applyTheme(theme, animate) {
        if (animate) {
            document.documentElement.classList.add('theme-anim');
            window.setTimeout(function () {
                document.documentElement.classList.remove('theme-anim');
            }, 350);
        }
        document.documentElement.setAttribute('data-theme', theme);
        try { localStorage.setItem(STORAGE_KEY, theme); } catch (e) { /* private mode */ }

        /* keep every toggle on the page in sync (app shell + auth pages) */
        document.querySelectorAll('.theme-toggle').forEach(function (btn) {
            btn.setAttribute('aria-label', theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme');
            btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
        });
    }

    function bindToggles() {
        document.querySelectorAll('.theme-toggle').forEach(function (btn) {
            if (btn.dataset.themeBound) return;
            btn.dataset.themeBound = '1';
            btn.addEventListener('click', function () {
                applyTheme(currentTheme() === 'dark' ? 'light' : 'dark', true);
            });
        });
    }

    /* If the OS preference changes and the user has not chosen manually, follow it. */
    if (window.matchMedia) {
        var media = window.matchMedia('(prefers-color-scheme: dark)');
        if (media.addEventListener) {
            media.addEventListener('change', function (e) {
                var saved = null;
                try { saved = localStorage.getItem(STORAGE_KEY); } catch (err) { /* ignore */ }
                if (!saved) applyTheme(e.matches ? 'dark' : 'light', true);
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindToggles);
    } else {
        bindToggles();
    }

    /* expose for pages that re-render toggle buttons dynamically */
    window.bhmApplyTheme = applyTheme;
})();
