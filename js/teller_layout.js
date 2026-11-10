(() => {
    const screen = document.getElementById('tellerScreen');
    if (!screen) return;

    let pendingFrame;
    function fitScreen() {
        cancelAnimationFrame(pendingFrame);
        pendingFrame = requestAnimationFrame(() => {
            const viewport = window.visualViewport;
            const height = viewport ? viewport.height : window.innerHeight;
            const width = viewport ? viewport.width : window.innerWidth;
            // Scale only when the compact layout still exceeds the viewport.
            // Measure the unscaled content so repeated calls cannot shrink it again.
            const scale = Math.min(1, height / screen.scrollHeight, width / screen.scrollWidth);
            screen.style.setProperty('--teller-scale', String(scale));
        });
    }

    window.addEventListener('resize', fitScreen);
    window.visualViewport?.addEventListener('resize', fitScreen);
    if ('ResizeObserver' in window) {
        const observer = new ResizeObserver(fitScreen);
        screen.querySelectorAll('.workspace-bar, .dashboard-container').forEach(element => observer.observe(element));
    }
    document.fonts?.ready.then(fitScreen);
    fitScreen();
})();
