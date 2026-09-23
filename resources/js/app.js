const indicator = document.getElementById('global-loading-indicator');
const indicatorMessage = document.getElementById('global-loading-message');
let pageIsLoading = false;
let focusBeforeLoading = null;

const loadingMessageForForm = (form) => {
    if (form.dataset.loadingText) {
        return form.dataset.loadingText;
    }

    const action = form.getAttribute('action') ?? '';
    const methodOverride = form.querySelector('input[name="_method"]')?.value?.toUpperCase();
    const method = methodOverride ?? form.method.toUpperCase();

    if (action.includes('/logout')) {
        return 'Signing out…';
    }

    if (action.includes('/login')) {
        return 'Signing in…';
    }

    if (method === 'DELETE') {
        return 'Deleting…';
    }

    if (method === 'PATCH' || method === 'PUT') {
        return 'Saving changes…';
    }

    if (method === 'POST') {
        return 'Saving…';
    }

    return 'Loading…';
};

const showLoading = (message = 'Loading…') => {
    if (pageIsLoading) {
        return;
    }

    pageIsLoading = true;
    focusBeforeLoading = document.activeElement instanceof HTMLElement
        ? document.activeElement
        : null;

    document.body?.setAttribute('aria-busy', 'true');
    document.documentElement.classList.add('cursor-wait');

    if (indicatorMessage) {
        indicatorMessage.textContent = message;
    }

    if (indicator instanceof HTMLDialogElement && !indicator.open) {
        indicator.showModal();
        indicator.focus({ preventScroll: true });
    }
};

const resetLoading = () => {
    pageIsLoading = false;
    document.body?.removeAttribute('aria-busy');
    document.documentElement.classList.remove('cursor-wait');

    document.querySelectorAll('[data-loading-disabled="true"]').forEach((control) => {
        control.disabled = false;
        control.removeAttribute('aria-busy');
        control.removeAttribute('data-loading-disabled');
        control.classList.remove('cursor-wait', 'opacity-70');
    });

    document.querySelectorAll('form[data-submitting="true"]').forEach((form) => {
        form.removeAttribute('data-submitting');
    });

    if (indicator instanceof HTMLDialogElement && indicator.open) {
        indicator.close();
    }

    if (focusBeforeLoading?.isConnected) {
        focusBeforeLoading.focus({ preventScroll: true });
    }

    focusBeforeLoading = null;
};

indicator?.addEventListener('cancel', (event) => {
    if (pageIsLoading) {
        event.preventDefault();
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (
        !(form instanceof HTMLFormElement)
        || event.defaultPrevented
        || form.matches('[data-no-loading]')
        || form.target === '_blank'
    ) {
        return;
    }

    if (form.dataset.submitting === 'true') {
        event.preventDefault();

        return;
    }

    form.dataset.submitting = 'true';

    if (event.submitter instanceof HTMLButtonElement || event.submitter instanceof HTMLInputElement) {
        if (event.submitter.name) {
            const submitterValue = document.createElement('input');
            submitterValue.type = 'hidden';
            submitterValue.name = event.submitter.name;
            submitterValue.value = event.submitter.value;
            form.append(submitterValue);
        }
    }

    form.querySelectorAll('button:not([type]), button[type="submit"], input[type="submit"], input[type="image"]').forEach((control) => {
        control.disabled = true;
        control.setAttribute('aria-busy', 'true');
        control.setAttribute('data-loading-disabled', 'true');
        control.classList.add('cursor-wait', 'opacity-70');
    });

    showLoading(loadingMessageForForm(form));
});

document.addEventListener('click', (event) => {
    if (event.defaultPrevented || event.button !== 0) {
        return;
    }

    const clickedElement = event.target instanceof Element ? event.target : null;
    const link = clickedElement?.closest('a[href]');

    if (
        !(link instanceof HTMLAnchorElement)
        || link.matches('[data-no-loading]')
        || link.hasAttribute('download')
        || (link.target && link.target !== '_self')
        || event.metaKey
        || event.ctrlKey
        || event.shiftKey
        || event.altKey
    ) {
        return;
    }

    const url = new URL(link.href, window.location.href);

    if (
        !['http:', 'https:'].includes(url.protocol)
        || (url.pathname === window.location.pathname
            && url.search === window.location.search
            && url.hash)
    ) {
        return;
    }

    if (pageIsLoading) {
        event.preventDefault();

        return;
    }

    showLoading(link.dataset.loadingText ?? 'Loading page…');
});

window.addEventListener('pageshow', resetLoading);

const sidebarToggle = document.getElementById('sidebar-toggle');
const desktopSidebar = window.matchMedia('(min-width: 1024px)');
let sidebarCollapsed = document.documentElement.dataset.sidebarCollapsed === 'true';

const applySidebarState = () => {
    const isCollapsed = desktopSidebar.matches && sidebarCollapsed;

    document.documentElement.dataset.sidebarCollapsed = isCollapsed ? 'true' : 'false';

    if (sidebarToggle) {
        const action = isCollapsed ? 'Expand sidebar' : 'Collapse sidebar';

        sidebarToggle.setAttribute('aria-expanded', String(! isCollapsed));
        sidebarToggle.setAttribute('aria-label', action);
        sidebarToggle.setAttribute('title', action);
    }
};

sidebarToggle?.addEventListener('click', () => {
    sidebarCollapsed = ! sidebarCollapsed;

    try {
        localStorage.setItem('tabulation.sidebar.collapsed', String(sidebarCollapsed));
    } catch {}

    applySidebarState();
});

desktopSidebar.addEventListener('change', applySidebarState);
applySidebarState();
