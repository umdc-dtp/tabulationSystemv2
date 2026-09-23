<dialog
    id="global-loading-indicator"
    aria-labelledby="global-loading-message"
    aria-describedby="global-loading-description"
    aria-modal="true"
    role="alertdialog"
    tabindex="-1"
    class="loading-overlay"
>
    <div class="loading-overlay__viewport">
        <div class="loading-overlay__card">
            <span class="loading-overlay__spinner" aria-hidden="true">
                <svg class="loading-overlay__icon" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" stroke-opacity="0.25"></circle>
                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                </svg>
            </span>
            <p id="global-loading-message" class="loading-overlay__message">Loading…</p>
            <p id="global-loading-description" class="loading-overlay__description">Please wait and do not close this page.</p>
        </div>
    </div>
</dialog>
