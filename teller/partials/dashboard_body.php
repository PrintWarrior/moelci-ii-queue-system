<!-- Teller dashboard body: stats, current ticket, next ticket, action buttons, and logout. -->
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
                    <!--    Announce Current Customer -->
                    <!--/button -->
                    <!--button class="speech-btn" onclick="testSpeech()" -->
                    <!--    Test Sound -->
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
                <button type="submit" name="call_next" class="btn btn-call" style="background-color: #28a745; color: black;" <?php echo (!$next && !$current) ? 'disabled' : ''; ?>> 
                    Call Next Customer 
                </button> 
                <button type="submit" name="mark_served" class="btn btn-served" style="background-color: #007bff; color: black;" <?php echo !$current ? 'disabled' : ''; ?>> 
                    Mark as Served 
                </button> 
                <button type="submit" name="callback_current" class="btn btn-cancel" style="background-color: #ffc107; color: black;" <?php echo !$current ? 'disabled' : ''; ?>> 
                    Call Back Customer 
                </button> 
            </form> 

            <!-- Logout Button --> 
            <div class="text-center mt-4"> 
                <a href="../includes/logout.php" class="btn btn-outline-danger btn-sm">Logout</a> 
            </div> 
        </div> 
    </div> 

