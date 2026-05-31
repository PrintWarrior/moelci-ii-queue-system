function updateDateTime() {
    const now = new Date();

    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const date = now.toLocaleDateString('en-US', options);
    const time = now.toLocaleTimeString('en-US');

    document.getElementById("datetime").textContent = `${date} (${time})`;
}

setInterval(updateDateTime, 1000);
updateDateTime();
        
        // Update time immediately and then every second
        updateDateTime();
        setInterval(updateDateTime, 1000);