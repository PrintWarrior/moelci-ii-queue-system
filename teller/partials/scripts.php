<!-- Teller scripts: optional announcement logic, clock, and auto-refresh. -->
    <script> 
        // Text-to-Speech functionality
        let speechSynthesis = window.speechSynthesis;
        let currentUtterance = null;
        let isSpeechSupported = false;



        //Speak text with proper formatting
        //function speakText(text) {
    //if (!('speechSynthesis' in window)) return;

   // window.speechSynthesis.cancel();

    //const utterance = new SpeechSynthesisUtterance(text);
    //utterance.rate = 0.9;
    //utterance.pitch = 1;
    //utterance.volume = 1;

    //window.speechSynthesis.speak(utterance);
//}



        // Announce the current customer
        function announceCurrentCustomer() {
    const ticket = document.querySelector('.ticket-number')?.textContent;
    const tellerName = "<?php echo $teller_name; ?>";

    if (!ticket || ticket === 'None') return;

    speakText(`Customer ${ticket}, please proceed to ${tellerName}`);
}


        // Test speech functionality
        function testSpeech() {
            const testMessage = "This is a test of the announcement system. The text to speech is working properly.";
            speakText(testMessage);
        }

        // Auto-announce when calling next customer
        function setupAutoAnnounce() {
            const form = document.getElementById('actionForm');
            const callNextBtn = form.querySelector('button[name="call_next"]');
            
            if (callNextBtn) {
                callNextBtn.addEventListener('click', function() {
                    // Store the next customer info for announcement after page reload
                    const nextTicket = document.querySelector('.next-customer strong')?.nextSibling?.textContent?.trim();
                    if (nextTicket) {
                        sessionStorage.setItem('autoAnnounce', 'true');
                        sessionStorage.setItem('nextTicket', nextTicket);
                        sessionStorage.setItem('tellerName', '<?php echo $teller_name; ?>');
                    }
                });
            }
        }

        // Check if we need to auto-announce after page reload
        function checkAutoAnnounce() {
            if (sessionStorage.getItem('autoAnnounce') === 'true') {
                const nextTicket = sessionStorage.getItem('nextTicket');
                const tellerName = sessionStorage.getItem('tellerName');
                
                if (nextTicket && tellerName) {
                    // Wait a moment for the page to fully load
                    setTimeout(() => {
                        const announcement = `Customer ${nextTicket}, please proceed to ${tellerName}`;
                        speakText(announcement);
                        
                        // Clear the auto-announce flag
                        sessionStorage.removeItem('autoAnnounce');
                        sessionStorage.removeItem('nextTicket');
                        sessionStorage.removeItem('tellerName');
                    }, 1000);
                }
            }
        }



        function updateDateTime() { 
            const now = new Date(); 
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit' 
            }; 
            document.getElementById("datetime").textContent = now.toLocaleDateString('en-US', options); 
        } 
        
        // Initialize everything when page loads
        document.addEventListener('DOMContentLoaded', function() {
            updateDateTime();
            setInterval(updateDateTime, 1000);
            
         //   checkSpeechSupport();
            setupAutoAnnounce();
         //   setupVolumeControl();
            checkAutoAnnounce();
            
            // Auto-refresh page every 30 seconds to update queue status 
            setInterval(() => { 
                window.location.reload(); 
            }, 10000);
        });
    </script> 
