<?php
require_once '../includes/db_connection.php';

// --- 1️⃣ Get served counts first ---
$sql = "
SELECT 
    u.user_id,
    COUNT(qt.ticket_id) AS served_count
FROM users u
LEFT JOIN queue_tickets qt 
    ON qt.assigned_user_id = u.user_id
    AND qt.status = 'completed'
    AND DATE(qt.completed_at) = CURDATE()
WHERE u.role_id = (SELECT role_id FROM roles WHERE role_name='Teller')
GROUP BY u.user_id
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$servedCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$servedMap = [];
foreach ($servedCounts as $row) {
  $servedMap[$row['user_id']] = $row['served_count'];
}

// --- 2️⃣ Function to get current queue data ---
function getQueueData($pdo)
{
  $data = [];

  try {
    // Waiting tickets (limit 30)
    $sql = "SELECT
  qt.ticket_number,
  qt.customer_type,
  s.service_name
                FROM queue_tickets qt
                JOIN services s ON qt.service_id = s.service_id
                WHERE qt.status = 'waiting'
                ORDER BY 
                    CASE qt.priority 
                        WHEN 'high' THEN 1 
                        WHEN 'normal' THEN 2 
                        WHEN 'low' THEN 3 
                    END,
                    qt.created_at ASC
                LIMIT 30";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $data['waiting'] = $stmt->fetchAll(PDO::FETCH_ASSOC); 



    // In-progress tickets
    $sql = "SELECT qt.*, CONCAT(u.first_name,' ',u.last_name) as teller_name, s.service_name
                FROM queue_tickets qt
                JOIN services s ON qt.service_id = s.service_id
                LEFT JOIN users u ON qt.assigned_user_id = u.user_id
                WHERE qt.status = 'in_progress'
                ORDER BY qt.called_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $data['serving'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Now serving (first in queue)
    // In the function getQueueData or in the main logic:

// Now serving (most recent in_progress ticket overall)
if (count($data['serving']) > 0) {
    // Sort by called_at DESC to get most recent
    usort($data['serving'], function($a, $b) {
        return strtotime($b['called_at']) - strtotime($a['called_at']);
    });
    
    $data['now_serving'] = $data['serving'][0]['ticket_number'];
    $data['now_serving_teller'] = $data['serving'][0]['teller_name'] ?: "Teller " . $data['serving'][0]['assigned_user_id'];
    $data['now_serving_service'] = $data['serving'][0]['service_name'];
} else {
    $data['now_serving'] = '---';
    $data['now_serving_teller'] = '---';
    $data['now_serving_service'] = '---';
}
    // Active tellers
    $sql = "SELECT u.user_id, CONCAT(u.first_name,' ',u.last_name) as teller_name
                FROM users u
                WHERE u.role_id = (SELECT role_id FROM roles WHERE role_name='Teller')
                  AND u.is_active = 1
                ORDER BY u.user_id ASC
                LIMIT 4";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $data['tellers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    return [
      'waiting' => [],
      'serving' => [],
      'now_serving' => '---',
      'now_serving_teller' => '---',
      'now_serving_service' => '---',
      'tellers' => []
    ];
  }

  return $data;
}

// Get queue data
$queueData = getQueueData($pdo);

// --- 3️⃣ Teller capabilities ---
$tellerCapabilities = [
  5  => ['regular'],           // Teller 1
  8  => ['pwd', 'regular'],    // Teller 2
  10 => ['regular'],           // Teller 3
  11 => ['pwd', 'regular'],    // Teller 4
];

// --- 4️⃣ Organize teller data ---
$tellerData = [];
foreach ($queueData['tellers'] as $teller) {
  $ticket = '---';
  $userId = $teller['user_id'];
  $allowedTypes = $tellerCapabilities[$userId] ?? ['regular'];

  foreach ($queueData['serving'] as $serving) {
    if ($serving['assigned_user_id'] == $userId && in_array($serving['customer_type'], $allowedTypes)) {
      $ticket = $serving['ticket_number'];
      break;
    }
  }

  $tellerData[] = [
    'user_id' => $userId,
    'name'    => $teller['teller_name'],
    'ticket'  => $ticket,
    'count'   => $servedMap[$userId] ?? 0
  ];
}

// Fill empty teller slots
while (count($tellerData) < 4) {
  $idx = count($tellerData) + 1;
  $tellerData[] = [
    'user_id' => null,
    'name'    => "Teller $idx",
    'ticket'  => '---',
    'count'   => 0
  ];
}

// --- 5️⃣ Count waiting tickets by lane and type ---
$waitingRegularPayment = $waitingRegularBilling = 0;
$waitingSpecialPayment = $waitingSpecialBilling = 0;

foreach ($queueData['waiting'] as $ticket) {

  if (!is_array($ticket)) {
    continue; // skip invalid rows
  }

  $serviceName = $ticket['service_name'] ?? '';
  $type = $ticket['customer_type'] ?? 'regular';

  if ($type === 'regular' && stripos($serviceName, 'Payment') !== false) {
    $waitingRegularPayment++;
  } elseif ($type === 'regular' && stripos($serviceName, 'Billing') !== false) {
    $waitingRegularBilling++;
  } elseif ($type === 'pwd' && stripos($serviceName, 'Payment') !== false) {
    $waitingSpecialPayment++;
  } elseif ($type === 'pwd' && stripos($serviceName, 'Billing') !== false) {
    $waitingSpecialBilling++;
  }
}


// --- 6️⃣ Operating schedule (unchanged) ---
function getOperatingSchedule($pdo)
{
  $schedule = [];
  try {
    $sql = "SELECT day_of_week, open_time, close_time, is_closed 
                FROM operating_hours 
                ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $hours = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT break_name, start_time, end_time 
                FROM breaks_schedule 
                WHERE is_active = 1 
                ORDER BY start_time";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $breaks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grouped_hours = [];
    foreach ($hours as $hour) {
      if (!$hour['is_closed'] && $hour['open_time'] && $hour['close_time']) {
        $key = $hour['open_time'] . '-' . $hour['close_time'];
        if (!isset($grouped_hours[$key])) {
          $grouped_hours[$key] = ['days' => [], 'open' => $hour['open_time'], 'close' => $hour['close_time']];
        }
        $grouped_hours[$key]['days'][] = $hour['day_of_week'];
      }
    }

    $schedule_text = "OPERATING SCHEDULE --- ";
    $hour_parts = [];
    foreach ($grouped_hours as $group) {
      $days = $group['days'];
      $day_range = count($days) == 1 ? $days[0] : implode(', ', $days);
      $hour_parts[] = $day_range . ' ' . date('g:iA', strtotime($group['open'])) . ' - ' . date('g:iA', strtotime($group['close']));
    }
    $schedule_text .= implode(' | ', $hour_parts);

    if (!empty($breaks)) {
      $schedule_text .= ' --- ';
      $break_parts = [];
      foreach ($breaks as $break) {
        $break_parts[] = $break['break_name'] . ' --- ' . date('g:iA', strtotime($break['start_time'])) . ' - ' . date('g:iA', strtotime($break['end_time']));
      }
      $schedule_text .= implode(' --- ', $break_parts);
    }

    $closed_days = array_filter($hours, fn($h) => $h['is_closed']);
    if (!empty($closed_days)) {
      $schedule_text .= ' --- CLOSED ON: ' . implode(', ', array_map(fn($h) => $h['day_of_week'], $closed_days));
    }

    $schedule['text'] = $schedule_text . ' --- ';
  } catch (PDOException $e) {
    error_log("Schedule error: " . $e->getMessage());
    $schedule['text'] = "OPERATING SCHEDULE --- Monday to Saturday 8:00AM - 5:00PM --- LUNCH BREAK --- 12:00PM - 1:00PM --- ";
  }
  return $schedule;
}

$schedule = getOperatingSchedule($pdo);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>MOELCI-ll TV display</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    /* Your existing CSS styles */
    * {
      box-sizing: border-box;
      font-family: Arial, Helvetica, sans-serif;
    }

    body {
      margin: 0;
      background: #fff;
    }

    /* ===== STATIC CENTERED HEADER ===== */
    .header {
      background: #fff176;
      border: 2px solid #000;
      height: 90px;

      display: flex;
      align-items: center;
      justify-content: center;
    }

    .header-content,
    .marquee span {
      font-weight: bold;
      font-size: clamp(25px, 2vw, 50px);
      font-size: 18px;
      color: #333;
      text-align: center;
      white-space: nowrap;
      /* keeps it on one line */
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 100%;
      padding: 0 20px;
    }



    /* ===== MAIN WRAPPER ===== */
    .main {
      display: flex;
      width: 100%;
      height: 583px;
      max-height: 583px;
      border-left: 2px solid #000;
      border-right: 2px solid #000;
    }

    /* ===== SIDE PANELS ===== */
    .side {
      width: 14%;
      min-width: 180px;
      background: #e0e0e0;
      border: 1px solid #000;
      display: flex;
      flex-direction: column;
    }

    .side .box {
      flex: 1;
      border-bottom: 1px solid #000;
      padding: 20px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .side .box:last-child {
      border-bottom: none;
    }

    .side h3 {
  margin: 0;
  font-size: clamp(28px, 2.5vw, 48px);
  text-align: center;
}


   .side p {
  margin: 0;
  text-align: center;
  font-size: clamp(22px, 2vw, 40px);
  color: #333;
}

    .ticket-number {
  font-size: clamp(56px, 4.5vw, 10px);
  font-weight: bold;
  color: #d32f2f;
  text-align: center;
  margin-top: 20px;
}

    /* ===== CENTER DISPLAY ===== */
    .center {
      flex: 1;
      border: 1px solid #000;
      text-align: center;

      padding: 60px 30px;

      display: flex;
      flex-direction: column;
      justify-content: center;

      background: linear-gradient(135deg, #fdfdfd 0%, #ececec 100%);
    }

    .center h1 {
  margin: 0;
  font-size: clamp(80px, 10vw, 140px);
  color: #333;
  text-shadow: 3px 3px 6px rgba(0,0,0,0.2);
}

    .center h2 {
      margin: 30px 0;
      font-size: clamp(80px, 10vw, 140px);
      color: #d32f2f;
      font-weight: 900;
      letter-spacing: 2px;
      text-shadow: 4px 4px 8px rgba(0, 0, 0, 0.25);
    }

    .center h3 {
      font-size: clamp(36px, 4vw, 52px);
      color: #333;
      margin-top: 20px;
    }

    .service-type {
      font-size: clamp(20px, 3vw, 28px);
      color: #666;
      margin-top: 10px;
    }

    /* ===== BOTTOM TABLE ===== */
    .table-container {
      border: 1px solid #000;
      border-top: none;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      text-align: center;
    }

    th, td {
  border: 1px solid #000;
  padding: 14px;
  font-size: clamp(24px, 2vw, 42px);
}

    th {
      background: #e0e0e0;
    }

    .waiting-count {
  font-weight: bold;
  color: #d32f2f;
  font-size: clamp(28px, 2.5vw, 48px);
}

    /* ===== RESPONSIVE ===== */
    @media (max-width: 900px) {
      .main {
        flex-direction: column;
      }

      .side {
        width: 100%;
        flex-direction: row;
      }

      .side .box {
        flex: 1;
        border-bottom: none;
        border-right: 1px solid #000;
        min-height: 150px;
      }

      .side .box:last-child {
        border-right: none;
      }

      .ticket-number {
        font-size: 24px;
      }

      .center h2 {
        margin: 20px 0;
      }
    }

    @media (max-width: 600px) {
      .side .box {
        padding: 10px;
      }

      .side h3 {
        font-size: 16px;
      }

      .ticket-number {
        font-size: 20px;
      }

      th,
      td {
        padding: 6px;
        font-size: 14px;
      }
    }
  </style>
</head>

<body>
<div id="monitorContent">
  <!-- STATIC HEADER -->
  <div class="header">
    <div class="marquee">
      <span><?php echo htmlspecialchars($schedule['text']); ?></span>
    </div>
  </div>

  <!-- MAIN DISPLAY -->
  <div class="main">

    <!-- LEFT TELLERS -->
    <div class="side">
      <div class="box">
        <h3><?php echo htmlspecialchars($tellerData[0]['name']); ?></h3>
        <div class="ticket-number"><?php echo $tellerData[0]['ticket']; ?></div>
        <p style="font-size:14px;color:#333;">
          Served: <strong><?php echo $tellerData[0]['count']; ?></strong>
        </p>
      </div>



      <div class="box">
        <h3><?php echo htmlspecialchars($tellerData[1]['name']); ?></h3>
        <div class="ticket-number"><?php echo $tellerData[1]['ticket']; ?></div>
        <p style="font-size:14px;color:#333;">
          Served: <strong><?php echo $tellerData[1]['count']; ?></strong>
        </p>
      </div>
    </div>

    <!-- CENTER -->
    <div class="center">
      <h1>NOW SERVING</h1>
      <h2 id="nowServing"><?php echo $queueData['now_serving']; ?></h2>

<?php if ($queueData['now_serving_service'] != '---'): ?>
<div id="nowServiceType" class="service-type">
  <?php echo htmlspecialchars($queueData['now_serving_service']); ?>
</div>
<?php endif; ?>

<h3 id="nowTeller">at <?php echo $queueData['now_serving_teller']; ?></h3>
    </div>

    <!-- RIGHT TELLERS -->
    <div class="side">
      <div class="box">
        <h3><?php echo htmlspecialchars($tellerData[2]['name']); ?></h3>
        <div class="ticket-number"><?php echo $tellerData[2]['ticket']; ?></div>
        <p style="font-size:14px;color:#333;">
          Served: <strong><?php echo $tellerData[2]['count']; ?></strong>
      </div>

      <div class="box">
        <h3><?php echo htmlspecialchars($tellerData[3]['name']); ?></h3>
        <div class="ticket-number"><?php echo $tellerData[3]['ticket']; ?></div>
        <p style="font-size:14px;color:#333;">
          Served: <strong><?php echo $tellerData[3]['count']; ?></strong>
      </div>
    </div>

  </div>

  <!-- BOTTOM TABLE -->
  <div class="table-container">
    <table>
      <tr>
        <th>Lane Type</th>
        <th>Service Type</th>
        <th>Waiting...</th>
        <th>Lane Type</th>
        <th>Service Type</th>
        <th>Waiting...</th>
      </tr>
      <tr>
        <td>Regular</td>
        <td>Payment</td>
        <td class="waiting-count"><?php echo $waitingRegularPayment; ?></td>
        <td>Regular</td>
        <td>Billing</td>
        <td class="waiting-count"><?php echo $waitingRegularBilling; ?></td>
      </tr>
      <tr>
        <td>Special</td>
        <td>Payment</td>
        <td class="waiting-count"><?php echo $waitingSpecialPayment; ?></td>
        <td>Special</td>
        <td>Billing</td>
        <td class="waiting-count"><?php echo $waitingSpecialBilling; ?></td>
      </tr>
    </table>
    <button onclick="openFullscreen()">Go Fullscreen</button>
  </div>
</div>

  <script>
function openFullscreen() {
  document.documentElement.requestFullscreen();
  startAutoRefresh();
}

function startAutoRefresh() {
  setInterval(refreshMonitor, 2000);
}

function refreshMonitor() {
  fetch(window.location.href)
    .then(res => res.text())
    .then(html => {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, "text/html");
      const newContent = doc.getElementById("monitorContent");
      document.getElementById("monitorContent").innerHTML = newContent.innerHTML;
    });
}

</script>

<script>
let lastCalled = "";

function speakNowServing(ticket, teller) {
  if (!('speechSynthesis' in window)) return;

  const text = `Now serving ${ticket} at ${teller}`;
  const utter = new SpeechSynthesisUtterance(text);

  utter.rate = 0.9;
  utter.pitch = 1;
  utter.volume = 1;

  speechSynthesis.cancel(); // stop previous
  speechSynthesis.speak(utter);
}

function checkAnnouncement() {
  const ticketEl = document.getElementById("nowServing");
  const tellerEl = document.getElementById("nowTeller");

  if (!ticketEl || !tellerEl) return;

  const ticket = ticketEl.textContent.trim();
  const teller = tellerEl.textContent.replace("at","").trim();

  if (ticket !== "---" && ticket !== lastCalled) {
    lastCalled = ticket;
    speakNowServing(ticket, teller);
  }
}

// run after every refresh
setInterval(checkAnnouncement, 1000);
</script>

</body>

</html>