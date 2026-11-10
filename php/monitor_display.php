<?php
require_once __DIR__ . '/../includes/db_connection.php';

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
    // All waiting tickets contribute to the public waiting totals.
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
                    qt.created_at ASC";
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

// --- 4️⃣ Organize teller data ---
$tellerData = [];
foreach ($queueData['tellers'] as $teller) {
  $ticket = '---';
  $userId = $teller['user_id'];

  foreach ($queueData['serving'] as $serving) {
    if ($serving['assigned_user_id'] == $userId) {
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

function getActiveAnnouncements($pdo)
{
  try {
    $stmt = $pdo->query("SELECT message FROM announcements WHERE is_active = 1 ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
  } catch (PDOException $e) {
    error_log("Announcement error: " . $e->getMessage());
    return [];
  }
}

$schedule = getOperatingSchedule($pdo);
$announcements = getActiveAnnouncements($pdo);
$headerMessages = [];

foreach ($announcements as $announcement) {
  $announcement = trim(preg_replace('/\s+/', ' ', $announcement));

  if ($announcement !== '') {
    $headerMessages[] = 'ANNOUNCEMENT: ' . $announcement;
  }
}

$headerMessages[] = $schedule['text'];
$headerText = implode(' --- ', $headerMessages) . ' --- ';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>MOELCI-II Queue Monitor</title>
  <meta name="color-scheme" content="light">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="../css/monitor_display.css">
  <link rel="icon" type="image/png" href="../assets/imgs/moelci_logo.png">
</head>

<body>
<div id="monitorContent">
  <header class="header" aria-label="Announcements and operating schedule">
    <div class="marquee"><div class="marquee-track">
      <span><?php echo htmlspecialchars($headerText); ?></span>
      <span aria-hidden="true"><?php echo htmlspecialchars($headerText); ?></span>
    </div></div>
  </header>
  <main id="queueContent">
    <div class="main">
      <aside class="side" aria-label="Payment tellers">
      <section class="box<?php echo ($queueData['now_serving'] !== '---' && $tellerData[0]['ticket'] === $queueData['now_serving']) ? ' is-current' : ''; ?>" aria-label="<?php echo htmlspecialchars($tellerData[0]['name']); ?>">
        <h3><?php echo htmlspecialchars($tellerData[0]['name']); ?></h3>
        <div class="ticket-number <?php echo str_starts_with($tellerData[0]['ticket'], 'P') ? 'priority-ticket' : (str_starts_with($tellerData[0]['ticket'], 'R') ? 'regular-ticket' : ''); ?>"><?php echo htmlspecialchars($tellerData[0]['ticket']); ?></div>
        <p>Served today: <strong><?php echo (int) $tellerData[0]['count']; ?></strong></p>
      </section>
      <section class="box<?php echo ($queueData['now_serving'] !== '---' && $tellerData[1]['ticket'] === $queueData['now_serving']) ? ' is-current' : ''; ?>" aria-label="<?php echo htmlspecialchars($tellerData[1]['name']); ?>">
        <h3><?php echo htmlspecialchars($tellerData[1]['name']); ?></h3>
        <div class="ticket-number <?php echo str_starts_with($tellerData[1]['ticket'], 'P') ? 'priority-ticket' : (str_starts_with($tellerData[1]['ticket'], 'R') ? 'regular-ticket' : ''); ?>"><?php echo htmlspecialchars($tellerData[1]['ticket']); ?></div>
        <p>Served today: <strong><?php echo (int) $tellerData[1]['count']; ?></strong></p>
      </section>
      </aside>
      <section class="center<?php echo $queueData['now_serving'] === '---' ? ' is-idle' : ''; ?>" aria-label="Latest called ticket">
        <h1><?php echo $queueData['now_serving'] === '---' ? 'PLEASE WAIT' : 'NOW SERVING'; ?></h1>
        <div id="nowServing" class="main-ticket <?php echo str_starts_with($queueData['now_serving'], 'P') ? 'priority-ticket' : (str_starts_with($queueData['now_serving'], 'R') ? 'regular-ticket' : ''); ?>" data-called-at="<?php echo htmlspecialchars($queueData['serving'][0]['called_at'] ?? ''); ?>"><?php echo htmlspecialchars($queueData['now_serving']); ?></div>
        <div id="nowServiceType" class="service-type"><?php echo $queueData['now_serving_service'] !== '---' ? htmlspecialchars($queueData['now_serving_service']) : 'Your number will appear here when called'; ?></div>
        <h2 id="nowTeller" class="teller-destination" data-teller="<?php echo htmlspecialchars($queueData['now_serving_teller']); ?>"><?php echo $queueData['now_serving'] !== '---' ? 'Proceed to ' . htmlspecialchars($queueData['now_serving_teller']) : 'Please watch this screen'; ?></h2>
      </section>
      <aside class="side" aria-label="Notice of Billing tellers">
      <section class="box<?php echo ($queueData['now_serving'] !== '---' && $tellerData[2]['ticket'] === $queueData['now_serving']) ? ' is-current' : ''; ?>" aria-label="<?php echo htmlspecialchars($tellerData[2]['name']); ?>">
        <h3><?php echo htmlspecialchars($tellerData[2]['name']); ?></h3>
        <div class="ticket-number <?php echo str_starts_with($tellerData[2]['ticket'], 'P') ? 'priority-ticket' : (str_starts_with($tellerData[2]['ticket'], 'R') ? 'regular-ticket' : ''); ?>"><?php echo htmlspecialchars($tellerData[2]['ticket']); ?></div>
        <p>Served today: <strong><?php echo (int) $tellerData[2]['count']; ?></strong></p>
      </section>
      <section class="box<?php echo ($queueData['now_serving'] !== '---' && $tellerData[3]['ticket'] === $queueData['now_serving']) ? ' is-current' : ''; ?>" aria-label="<?php echo htmlspecialchars($tellerData[3]['name']); ?>">
        <h3><?php echo htmlspecialchars($tellerData[3]['name']); ?></h3>
        <div class="ticket-number <?php echo str_starts_with($tellerData[3]['ticket'], 'P') ? 'priority-ticket' : (str_starts_with($tellerData[3]['ticket'], 'R') ? 'regular-ticket' : ''); ?>"><?php echo htmlspecialchars($tellerData[3]['ticket']); ?></div>
        <p>Served today: <strong><?php echo (int) $tellerData[3]['count']; ?></strong></p>
      </section>
      </aside>
    </div>
    <section class="table-container" aria-label="Customers waiting by lane and service">
      <h2 id="waitingTitle" class="waiting-title">Customers waiting</h2>
      <table aria-labelledby="waitingTitle">
        <thead><tr><th scope="col">Queue lane</th><th scope="col">Payment</th><th scope="col">Notice of Billing</th></tr></thead>
        <tbody>
          <tr><th scope="row">Regular</th><td class="waiting-count"><?php echo $waitingRegularPayment; ?></td><td class="waiting-count"><?php echo $waitingRegularBilling; ?></td></tr>
          <tr><th scope="row">Priority</th><td class="waiting-count"><?php echo $waitingSpecialPayment; ?></td><td class="waiting-count"><?php echo $waitingSpecialBilling; ?></td></tr>
        </tbody>
      </table>
    </section>
  </main>
</div>
<div id="connectionStatus" role="status" hidden>Updates paused. Reconnecting…</div>

<script>
let lastCalled = '';
let refreshInProgress = false;

// Keep the ticker speed steady even when announcements become longer.
function sizeMarquee() {
  const track = document.querySelector('.marquee-track');
  if (!track) return;
  const pixelsPerSecond = Math.max(36, window.innerWidth * 0.04);
  track.style.setProperty('--marquee-duration', (track.scrollWidth / 2 / pixelsPerSecond) + 's');
}

function speakNowServing(ticket, teller) {
  if (!('speechSynthesis' in window)) return;
  const utter = new SpeechSynthesisUtterance('Now serving ' + ticket + ' at ' + teller);
  utter.rate = 0.9;
  utter.pitch = 1;
  utter.volume = 1;
  window.speechSynthesis.cancel();
  window.speechSynthesis.speak(utter);
}

function checkAnnouncement() {
  const ticketEl = document.getElementById('nowServing');
  const tellerEl = document.getElementById('nowTeller');
  if (!ticketEl || !tellerEl) return;
  const ticket = ticketEl.textContent.trim();
  if (!ticket || ticket === '---') { lastCalled = ''; return; }
  const teller = tellerEl.dataset.teller;
  const key = ticket + '|' + teller + '|' + (ticketEl.dataset.calledAt || '');
  if (key === lastCalled) return;
  lastCalled = key;
  speakNowServing(ticket, teller);
}

async function refreshMonitor() {
  if (refreshInProgress) return;
  refreshInProgress = true;
  const controller = new AbortController();
  const timeout = window.setTimeout(() => controller.abort(), 10000);
  try {
    const response = await fetch(window.location.href, { cache: 'no-store', signal: controller.signal });
    if (!response.ok) throw new Error('Monitor request failed');
    const html = await response.text();
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const incoming = doc.getElementById('queueContent');
    const current = document.getElementById('queueContent');
    const newMarquee = doc.querySelector('.marquee-track');
    const currentMarquee = document.querySelector('.marquee-track');
    if (!incoming || !current || !newMarquee || !currentMarquee) throw new Error('Incomplete monitor response');
    if (incoming.innerHTML !== current.innerHTML) current.innerHTML = incoming.innerHTML;
    if (newMarquee.textContent.trim() !== currentMarquee.textContent.trim()) {
      currentMarquee.innerHTML = newMarquee.innerHTML;
      sizeMarquee();
    }
    document.getElementById('connectionStatus').hidden = true;
    checkAnnouncement();
  } catch (error) {
    // Preserve the last readable queue while retrying.
    document.getElementById('connectionStatus').hidden = false;
  } finally {
    window.clearTimeout(timeout);
    refreshInProgress = false;
    window.setTimeout(refreshMonitor, 2000);
  }
}

window.addEventListener('resize', sizeMarquee);
sizeMarquee();
checkAnnouncement();
window.setTimeout(refreshMonitor, 2000);
</script>
</body>
</html>
