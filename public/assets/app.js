'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const navButton = document.querySelector('[data-toggle-nav]');
    const sidebar = document.getElementById('main-navigation');
    const mainShell = document.querySelector('.main-shell');
    const mobileViewport = window.matchMedia('(max-width: 760px)');
    const syncNavigation = () => {
        const modal = mobileViewport.matches && document.body.classList.contains('nav-open');
        if (sidebar) sidebar.inert = mobileViewport.matches && !modal;
        if (mainShell) mainShell.inert = modal;
    };
    const closeNavigation = () => {
        document.body.classList.remove('nav-open');
        navButton?.setAttribute('aria-expanded', 'false');
        syncNavigation();
    };
    navButton?.addEventListener('click', () => {
        const open = document.body.classList.toggle('nav-open');
        navButton.setAttribute('aria-expanded', String(open));
        syncNavigation();
        if (open) document.querySelector('.sidebar a')?.focus();
    });
    document.querySelectorAll('[data-close-nav]').forEach(button => button.addEventListener('click', () => {
        closeNavigation();
        navButton?.focus();
    }));
    mobileViewport.addEventListener('change', closeNavigation);
    syncNavigation();
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && document.body.classList.contains('nav-open')) {
            closeNavigation();
            navButton?.focus();
        }
        if (event.key === 'Tab' && document.body.classList.contains('nav-open') && mobileViewport.matches) {
            const controls = sidebar.querySelectorAll('a[href], button:not([disabled])');
            const first = controls[0];
            const last = controls[controls.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    document.querySelector('[data-toggle-password]')?.addEventListener('click', event => {
        const button = event.currentTarget;
        const input = document.getElementById(button.getAttribute('aria-controls'));
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.textContent = visible ? 'Hide' : 'Show';
        button.setAttribute('aria-pressed', String(visible));
    });
    document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => window.print()));
    // Associate labels in preserved forms with their controls for keyboard and screen-reader access.
    document.querySelectorAll('.form-group').forEach((group, index) => {
        const label = group.querySelector('label');
        const control = group.querySelector('input:not([type="hidden"]), select, textarea');
        if (label && control && !label.htmlFor && !label.contains(control)) {
            control.id ||= `form-control-${index}`;
            label.htmlFor = control.id;
        }
    });
    // Form submission remains native; prevent repeated writes while the response loads.
    document.querySelectorAll('form[method="post"]').forEach(form => {
        form.addEventListener('submit', event => {
            if (event.defaultPrevented || !form.checkValidity()) return;
            if (form.dataset.submitting) { event.preventDefault(); return; }
            form.dataset.submitting = 'true';
            form.setAttribute('aria-busy', 'true');
        });
    });
    window.addEventListener('pageshow', () => document.querySelectorAll('form[data-submitting]').forEach(form => {
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');
    }));
});
