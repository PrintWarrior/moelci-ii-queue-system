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
        // Refresh queue eligibility and heartbeat every 10 seconds.
        setInterval(() => window.location.reload(), 10000);
    });
</script>
