(() => {
    // Capture before page-specific AJAX handlers so Cancel prevents all action side effects.
    function ask(event, message) {
        if (!message || window.confirm(message)) return;
        event.preventDefault();
        event.stopImmediatePropagation();
    }
    document.addEventListener('submit', event => {
        if (event.defaultPrevented) return;
        const form = event.target;
        const submitter = event.submitter || form.querySelector('button[type="submit"]:not(:disabled), input[type="submit"]:not(:disabled)');
        const message = submitter?.getAttribute('data-confirm') || form.getAttribute('data-confirm');
        ask(event, message);
    }, true);
    document.addEventListener('click', event => {
        if (event.defaultPrevented) return;
        const action = event.target.closest('[data-confirm-click]');
        if (!action || action.disabled) return;
        ask(event, action.getAttribute('data-confirm-click'));
    }, true);
})();
