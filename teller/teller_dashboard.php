<?php 
session_start(); 
require_once '../includes/db_connection.php';

// Check if user is logged in as teller 
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Teller') { 
    header("Location: ../index.php"); 
    exit; 
} 

// Set teller ID from session 
$user_id = $_SESSION['user_id']; 
$teller_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; 

// ---- FUNCTION TO FETCH NEXT QUEUE ---- 
function getNextQueue($pdo, $user_id) {

    // Map teller to rules
    $rules = [
        5  => ['service' => 'Payment', 'type' => 'regular'], // Teller 1
        8  => ['service' => 'Payment', 'type' => 'pwd_fallback'], // Teller 2
        10 => ['service' => 'Billing', 'type' => 'regular'], // Teller 3
        11 => ['service' => 'Billing', 'type' => 'pwd_fallback'], // Teller 4
    ];

    if (!isset($rules[$user_id])) return null;

    $service = $rules[$user_id]['service'];
    $typeRule = $rules[$user_id]['type'];

    // 1️⃣ Try PWD first (if applicable)
    if ($typeRule === 'pwd_fallback') {
        $stmt = $pdo->prepare("
            SELECT qt.*, s.service_name
            FROM queue_tickets qt
            JOIN services s ON qt.service_id = s.service_id
            WHERE qt.status = 'waiting'
              AND qt.customer_type = 'pwd'
              AND s.service_name LIKE ?
            ORDER BY qt.created_at ASC
            LIMIT 1
        ");
        $stmt->execute(["%$service%"]);
        $pwd = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($pwd) return $pwd;
    }

    // 2️⃣ Regular customers
    $stmt = $pdo->prepare("
        SELECT qt.*, s.service_name
        FROM queue_tickets qt
        JOIN services s ON qt.service_id = s.service_id
        WHERE qt.status = 'waiting'
          AND qt.customer_type = 'regular'
          AND s.service_name LIKE ?
        ORDER BY qt.created_at ASC
        LIMIT 1
    ");
    $stmt->execute(["%$service%"]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
 

// ---- FETCH CURRENT SERVING QUEUE ---- 
// ---- FETCH CURRENT SERVING QUEUE ---- 
try { 
    // Get ONLY THE LATEST customer being served (not all in_progress)
    $currentQuery = "SELECT qt.*, s.service_name, 
                            COALESCE(CONCAT(u.first_name, ' ', u.last_name), qt.customer_name) as customer_name 
                     FROM queue_tickets qt 
                     JOIN services s ON qt.service_id = s.service_id 
                     LEFT JOIN users u ON qt.customer_id = u.user_id 
                     WHERE qt.assigned_user_id = ? 
                       AND qt.status = 'in_progress' 
                     ORDER BY qt.called_at DESC 
                     LIMIT 1";  // Show only the latest one
    
    $stmt = $pdo->prepare($currentQuery); 
    $stmt->execute([$user_id]); 
    $current = $stmt->fetch(PDO::FETCH_ASSOC); 

    // Continue with existing code...

    // ---- FETCH NEXT QUEUE ---- 
    $next = getNextQueue($pdo, $user_id); 

    // ---- HANDLE ACTIONS ---- 
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    // CALL NEXT 
    if (isset($_POST['call_next'])) { 
        // REMOVE THIS BLOCK (don't move current back to waiting)
        // if ($current) { 
        //     // Move current back to waiting if not completed 
        //     $updateStmt = $pdo->prepare("UPDATE queue_tickets SET status='waiting', assigned_user_id = NULL WHERE ticket_id = ?"); 
        //     $updateStmt->execute([$current['ticket_id']]); 
        // } 

        if ($next) { 
            // Assign next ticket to this teller and mark as in progress 
            $updateStmt = $pdo->prepare("UPDATE queue_tickets SET status='in_progress', assigned_user_id = ?, called_at = NOW() WHERE ticket_id = ?"); 
            $updateStmt->execute([$user_id, $next['ticket_id']]); 
            
            // Store the called ticket for speech synthesis
            $_SESSION['last_called_ticket'] = $next['ticket_number'];
            $_SESSION['last_called_teller'] = $teller_name;
        } 

        header("Location: teller_dashboard.php"); 
        exit; 
    } 

    // MARK SERVED/COMPLETED 
    if (isset($_POST['mark_served'])) { 
        if ($current) { 
            // Mark current as completed 
            $updateStmt = $pdo->prepare("UPDATE queue_tickets SET status='completed', completed_at = NOW() WHERE ticket_id = ?"); 
            $updateStmt->execute([$current['ticket_id']]); 
        } 

        // REMOVE automatic call next (optional, based on client preference)
        // $next = getNextQueue($pdo, $user_id); 
        // if ($next) { 
        //     $updateStmt = $pdo->prepare("UPDATE queue_tickets SET status='in_progress', assigned_user_id = ?, called_at = NOW() WHERE ticket_id = ?"); 
        //     $updateStmt->execute([$user_id, $next['ticket_id']]); 
        // } 

        header("Location: teller_dashboard.php"); 
        exit; 
    } 

    // CANCEL CURRENT 
    if (isset($_POST['cancel_current'])) { 
        if ($current) { 
            // Mark current as cancelled 
            $updateStmt = $pdo->prepare("UPDATE queue_tickets SET status='cancelled', completed_at = NOW() WHERE ticket_id = ?"); 
            $updateStmt->execute([$current['ticket_id']]); 
        } 

        // REMOVE automatic call next (optional, based on client preference)
        // $next = getNextQueue($pdo, $user_id); 
        // if ($next) { 
        //     $updateStmt = $pdo->prepare("UPDATE queue_tickets SET status='in_progress', assigned_user_id = ?, called_at = NOW() WHERE ticket_id = ?"); 
        //     $updateStmt->execute([$user_id, $next['ticket_id']]); 
        // } 

        header("Location: teller_dashboard.php"); 
        exit; 
    } 
}

    // Get queue statistics - FIXED: Show ALL tickets, not just teller's services
    $statsQuery = "SELECT 
                    COUNT(CASE WHEN status = 'waiting' THEN 1 END) as waiting_count,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_count,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_today
                   FROM queue_tickets 
                   WHERE DATE(created_at) = CURDATE()";
    
    $stmt = $pdo->prepare($statsQuery); 
    $stmt->execute(); 
    $stats = $stmt->fetch(PDO::FETCH_ASSOC); 

} catch (PDOException $e) { 
    die("Database error: " . $e->getMessage()); 
} 
?> 

<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <title>MOELCI-II Teller Dashboard - <?php echo $teller_name; ?></title> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <link rel="stylesheet" href="../css/teller_board.css"> 
    <link rel="icon" type="image/png" href="../assets/imgs/moelci_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> 
    <style>
        .speech-controls {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
           /* border-left: 4px solid #007bff; */
        }
        .speech-btn {
            background: #6f42c1;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 5px;
        }
        .speech-btn:hover {
            background: #5a32a3;
        }
        .speech-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        .volume-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
    </style>
</head> 
<body> 
    <div class="dashboard-container"> 
        <div class="header"> 
           
            <div class="datetime" id="datetime"></div> 
            <h3>Teller Dashboard - <?php echo $teller_name; ?></h3> 
        </div> 
        
        <div class="content"> 
            <!-- Text-to-Speech Controls -->
            <!--div class="speech-controls"-->
                <!--h5>Announcement System</h5-->
                <!--div-->
                    <!--button class="speech-btn" onclick="announceCurrentCustomer()" id="announceBtn"-->
                    <!--    🔊 Announce Current Customer -->
                    <!--/button -->
                    <!--button class="speech-btn" onclick="testSpeech()" -->
                    <!--    🎵 Test Sound -->
                    <!--/button>
                </div-->
             
            <!--/div-->

            <!-- Statistics --> 
            <div class="stats-grid"> 
                <div class="stat-card"> 
                    <div class="stat-number"><?php echo $stats['waiting_count'] ?? 0; ?></div> 
                    <div>Waiting</div> 
                </div> 
                <div class="stat-card"> 
                    <div class="stat-number"><?php echo $stats['in_progress_count'] ?? 0; ?></div> 
                    <div>In Progress</div> 
                </div> 
                <div class="stat-card"> 
                    <div class="stat-number"><?php echo $stats['completed_today'] ?? 0; ?></div> 
                    <div>Served Today</div> 
                </div> 
            </div> 

            <!-- Current Customer --> 
            <div class="now-serving"> 
                <h3>Now Serving:</h3> 
                <?php if ($current): ?> 
                    <div class="ticket-number"><?php echo htmlspecialchars($current['ticket_number']); ?></div> 
                   <!-- <p><strong>Customer:</strong> <?php echo htmlspecialchars($current['customer_name']); ?></p> -->
                    <p><strong>Service:</strong> <?php echo htmlspecialchars($current['service_name']); ?></p> 
                    <p><strong>Priority:</strong> <span class="badge bg-<?php echo $current['priority'] == 'high' ? 'danger' : ($current['priority'] == 'normal' ? 'warning' : 'secondary'); ?>"> 
                        <?php echo ucfirst($current['priority']); ?> 
                    </span></p> 
                   <!-- <p><strong>Wait Time:</strong> 
                        <?php if ($current['called_at']) { 
                            $waitTime = time() - strtotime($current['called_at']); 
                            echo floor($waitTime / 60) . ' minutes'; 
                        } else { 
                            echo 'Just started'; 
                        } ?> 
                    </p> -->
                <?php else: ?> 
                    <div class="ticket-number" style="color: #6c757d;">None</div> 
                    <p>No customer currently being served</p> 
                <?php endif; ?> 
            </div> 

            <!-- Next Customer --> 
            <?php if ($next): ?> 
                <div class="next-customer"> 
                    <h4>Next Customer:</h4> 
                    <p><strong>Ticket:</strong> <?php echo htmlspecialchars($next['ticket_number']); ?></p> 
                    <p><strong>Service:</strong> <?php echo htmlspecialchars($next['service_name']); ?></p> 
                   <!-- <p><strong>Wait Time:</strong> 
                        <?php $waitTime = time() - strtotime($next['created_at']); 
                        echo floor($waitTime / 60) . ' minutes'; ?> 
                    </p>  -->
                </div> 
            <?php else: ?> 
                <div class="alert alert-info"> 
                    <strong>No customers waiting in queue for your services.</strong> 
                </div> 
            <?php endif; ?> 

            <!-- Action Buttons --> 
            <form method="POST" class="buttons" id="actionForm"> 
                <button type="submit" name="call_next" class="btn btn-call" style="background-color: #28a745; color: black;" <?php echo !$next ? 'disabled' : ''; ?>> 
                    Call Next Customer 
                </button> 
                <button type="submit" name="mark_served" class="btn btn-served" style="background-color: #007bff; color: black;" <?php echo !$current ? 'disabled' : ''; ?>> 
                    Mark as Served 
                </button> 
                <button type="submit" name="cancel_current" class="btn btn-cancel" style="background-color: #dc3545; color: black;" <?php echo !$current ? 'disabled' : ''; ?>> 
                    Cancel Current 
                </button> 
            </form> 

            <!-- Logout Button --> 
            <div class="text-center mt-4"> 
                <a href="../includes/logout.php" class="btn btn-outline-danger btn-sm">Logout</a> 
            </div> 
        </div> 
    </div> 

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
</body> 
</html>
