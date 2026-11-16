<!-- The TV monitor handles spoken queue announcements. -->
<script>
    function updateDateTime() {
        const now = new Date();
        const options = {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        };
        document.getElementById('datetime').textContent = now.toLocaleDateString('en-US', options);
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateDateTime();
        setInterval(updateDateTime, 1000);
        let updating = false;
        let lastActionButton = null;

        document.addEventListener('click', function (event) {
            const button = event.target.closest('#actionForm button[type="submit"]');
            if (button) lastActionButton = button;
        });

        async function refreshTellerScreen() {
            if (updating || document.hidden) return;
            updating = true;
            try {
                const response = await fetch('teller_dashboard.php?partial=1', {
                    cache: 'no-store',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error('Could not refresh queue');
                const html = await response.text();
                const parsed = new DOMParser().parseFromString(html, 'text/html');
                const updatedDashboard = parsed.querySelector('.dashboard-container');
                const currentDashboard = document.querySelector('.dashboard-container');
                if (!updatedDashboard || !currentDashboard) throw new Error('Incomplete teller dashboard');
                currentDashboard.replaceWith(updatedDashboard);
                window.dispatchEvent(new Event('resize'));
            } catch (error) {
                console.warn('Teller dashboard refresh failed:', error);
            } finally {
                updating = false;
            }
        }

        document.addEventListener('submit', async function (event) {
            const form = event.target;
            if (!form.matches('#actionForm') || event.defaultPrevented) return;
            event.preventDefault();
            const submitter = event.submitter || lastActionButton || document.activeElement;
            const payload = new FormData(form);
            if (submitter?.name && !payload.has(submitter.name)) {
                payload.append(submitter.name, submitter.value || '');
            }
            lastActionButton = null;
            let actionCompleted = false;
            form.querySelectorAll('button[type="submit"]').forEach(button => button.disabled = true);
            try {
                const response = await fetch(form.action || window.location.href, {
                    method: 'POST',
                    body: payload,
                    cache: 'no-store',
                    headers: { 'X-Teller-Ajax': '1', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error('Queue action failed');
                actionCompleted = true;
                await refreshTellerScreen();
            } catch (error) {
                window.alert(actionCompleted
                    ? 'The action completed, but the dashboard could not refresh. It will retry automatically.'
                    : 'The queue action could not be completed. Please try again.');
                form.querySelectorAll('button[type="submit"]').forEach(button => button.disabled = false);
            }
        });

        // This small update also records the heartbeat; no full-page reload is needed.
        window.setInterval(refreshTellerScreen, 15000);
    });
</script>
