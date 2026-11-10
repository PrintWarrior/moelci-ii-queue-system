<!-- Admin dashboard scripts: navigation, settings tabs, modals, AJAX refresh, and charts. -->
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    const newUserPassword = document.getElementById('newUserPassword');
    const newUserPasswordToggle = document.getElementById('newUserPasswordToggle');
    newUserPasswordToggle.hidden = false;
    newUserPasswordToggle.addEventListener('click', () => {
      const showPassword = newUserPassword.type === 'password';
      newUserPassword.type = showPassword ? 'text' : 'password';
      newUserPasswordToggle.textContent = showPassword ? 'Hide' : 'Show';
      newUserPasswordToggle.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
      newUserPasswordToggle.setAttribute('aria-pressed', String(showPassword));
    });

    const sectionInfo = {
      dashboard: ['Dashboard', "An overview of your tellers and today's queue."],
      transactions: ['Transactions', 'Review customer tickets and service activity.'],
      analytics: ['Queue analytics', 'Explore service volume, wait times, and teller performance.'],
      settings: ['Settings', 'Manage users, operating hours, announcements, and history.']
    };

    function setSidebarOpen(open) {
      document.body.classList.toggle('sidebar-open', open);
      document.getElementById('sidebar-toggle').setAttribute('aria-expanded', String(open));
      document.querySelector('.sidebar-backdrop').hidden = !open;
    }
    document.getElementById('sidebar-toggle').addEventListener('click', function () {
      setSidebarOpen(this.getAttribute('aria-expanded') !== 'true');
    });
    document.querySelector('.sidebar-backdrop').addEventListener('click', () => setSidebarOpen(false));
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
        setSidebarOpen(false);
        document.getElementById('sidebar-toggle').focus();
      }
    });
    window.matchMedia('(min-width: 992px)').addEventListener('change', event => {
      if (event.matches) setSidebarOpen(false);
    });

    function showSection(id, btn) {
      if (!sectionInfo[id]) return;
      document.querySelectorAll('.section').forEach(sec => sec.style.display = 'none');
      document.getElementById(id).style.display = 'block';
      document.querySelectorAll('.sidebar-link').forEach(button => {
        button.classList.remove('active');
        button.removeAttribute('aria-current');
      });
      btn.classList.add('active');
      btn.setAttribute('aria-current', 'page');
      document.getElementById('page-title').textContent = sectionInfo[id][0];
      document.getElementById('page-description').textContent = sectionInfo[id][1];
      document.title = sectionInfo[id][0] + ' | MOELCI-II Admin';
      const wasSidebarOpen = document.body.classList.contains('sidebar-open');
      setSidebarOpen(false);
      if (wasSidebarOpen) document.getElementById('sidebar-toggle').focus();
      if (id === 'analytics') {
        requestAnimationFrame(() => { serviceChart?.resize(); hourChart?.resize(); });
      }
    }

    function escapeHtml(value) {
      return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
      }[char]));
    }

    async function archiveTransactions(button) {
      if (button.disabled) return;
      if (!confirm('Move ALL current transactions to History and clear the queue? Waiting and in-progress customers will also be removed from the current queue.')) return;
      const originalLabel = button.innerHTML;
      const feedback = document.getElementById('archiveTransactionsFeedback');
      button.disabled = true;
      button.textContent = 'Moving to History...';
      feedback.hidden = true;
      try {
        const response = await fetch('../includes/clear_transactions.php', {
          method: 'POST',
          body: new URLSearchParams({
            csrf_token: <?php echo json_encode($_SESSION['availability_csrf']); ?>
          })
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.error || 'Unable to move transactions to history.');
        feedback.className = 'alert alert-success';
        feedback.textContent = result.archived_count
          ? result.archived_count + ' transaction(s) moved to History. The current queue is cleared. Open Settings → History to view the updated records.'
          : 'There are no current transactions to move.';
        if (result.archived_count > 0) {
          alert(result.archived_count + ' transaction(s) moved to History.');
          window.location.assign('admin_dashboard.php#history');
        } else {
          loadData();
        }
      } catch (error) {
        feedback.className = 'alert alert-danger';
        feedback.textContent = error.message || 'Unable to reach the server. Check your connection and try again.';
      } finally {
        feedback.hidden = false;
        button.disabled = false;
        button.innerHTML = originalLabel;
      }
    }

    // Settings tabs functionality
    document.addEventListener('DOMContentLoaded', function() {
      const tabLinks = document.querySelectorAll('#settingsTabs .nav-link');
      tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          const targetId = this.getAttribute('href').substring(1);

          document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
          });

          document.getElementById(targetId).classList.add('active');

          tabLinks.forEach(tab => tab.classList.remove('active'));
          this.classList.add('active');
        });
      });

      const hashTabLink = document.querySelector(`#settingsTabs .nav-link[href="${window.location.hash}"]`);
      if (hashTabLink) {
        const settingsButton = Array.from(document.querySelectorAll('.sidebar-link'))
          .find(button => (button.getAttribute('onclick') || '').includes("'settings'"));

        if (settingsButton) {
          showSection('settings', settingsButton);
        }

        hashTabLink.click();
      }

      setDefaultAnalyticsDates();
      paginateUsersTable(1);
      applyFilters();
    });

    function setDefaultAnalyticsDates() {
      const today = new Date().toISOString().slice(0, 10);
      const startInput = document.getElementById('analyticsStartDate');
      const endInput = document.getElementById('analyticsEndDate');

      if (startInput && !startInput.value) startInput.value = today;
      if (endInput && !endInput.value) endInput.value = today;
    }

    const tablePageSize = 10;

    function renderPagination(containerId, totalRows, currentPage, onPageClick) {
      const container = document.getElementById(containerId);
      if (!container) return;

      const totalPages = Math.max(1, Math.ceil(totalRows / tablePageSize));
      if (totalRows <= tablePageSize) {
        container.innerHTML = totalRows ? `Showing ${totalRows} record(s)` : 'No records found';
        return;
      }

      let html = `<span>Page ${currentPage} of ${totalPages}</span>`;
      html += `<button type="button" class="btn btn-sm btn-outline-secondary" ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}">Prev</button>`;

      for (let page = 1; page <= totalPages; page++) {
        if (page === 1 || page === totalPages || Math.abs(page - currentPage) <= 1) {
          html += `<button type="button" class="btn btn-sm ${page === currentPage ? 'btn-primary' : 'btn-outline-secondary'}" data-page="${page}">${page}</button>`;
        } else if (Math.abs(page - currentPage) === 2) {
          html += `<span>...</span>`;
        }
      }

      html += `<button type="button" class="btn btn-sm btn-outline-secondary" ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}">Next</button>`;
      container.innerHTML = html;

      container.querySelectorAll('button[data-page]').forEach(button => {
        button.addEventListener('click', function() {
          onPageClick(parseInt(this.dataset.page, 10));
        });
      });
    }

    function paginateRows(rows, currentPage, containerId, onPageClick) {
      const totalRows = rows.length;
      const totalPages = Math.max(1, Math.ceil(totalRows / tablePageSize));
      const safePage = Math.min(Math.max(currentPage, 1), totalPages);
      const start = (safePage - 1) * tablePageSize;
      const end = start + tablePageSize;

      rows.forEach((row, index) => {
        row.style.display = index >= start && index < end ? "" : "none";
      });

      renderPagination(containerId, totalRows, safePage, onPageClick);
    }

    function paginateUsersTable(page = 1) {
      const rows = Array.from(document.querySelectorAll('#usersTableBody tr'));
      paginateRows(rows, page, 'usersPagination', paginateUsersTable);
    }

    function paginateHistoryTable(page = 1) {
      const rows = Array.from(document.querySelectorAll('#historyTable tbody tr'))
        .filter(row => row.dataset.filterMatch !== "0");

      document.querySelectorAll('#historyTable tbody tr').forEach(row => {
        row.style.display = "none";
      });

      paginateRows(rows, page, 'historyPagination', paginateHistoryTable);
    }

    function paginateTransactionsTable(page = 1) {
      const rows = Array.from(document.querySelectorAll('#transactions-list-page tr'));
      paginateRows(rows, page, 'transactionsPagination', paginateTransactionsTable);
    }

    // Toggle service assignment for new user form
    function toggleServiceAssignment() {
      const roleSelect = document.getElementById('roleSelect');
      const serviceAssignment = document.getElementById('serviceAssignment');

      if (roleSelect.value == '2') { // Teller role ID
        serviceAssignment.style.display = 'block';
      } else {
        serviceAssignment.style.display = 'none';
        // Uncheck all service checkboxes
        document.querySelectorAll('input[name="services[]"]').forEach(checkbox => {
          checkbox.checked = false;
        });
      }
    }

    // Toggle service assignment for edit modal
    function toggleEditServiceAssignment() {
      const roleSelect = document.getElementById('editRoleId');
      const serviceAssignment = document.getElementById('editServiceAssignment');

      if (roleSelect.value == '2') { // Teller role ID
        serviceAssignment.style.display = 'block';
      } else {
        serviceAssignment.style.display = 'none';
        // Uncheck all service checkboxes
        document.querySelectorAll('.service-checkbox').forEach(checkbox => {
          checkbox.checked = false;
        });
      }
    }

    // Open edit modal with user data
    function openEditModal(userId) {
      // Fetch user data (in a real app, you'd fetch from server via AJAX)
      // For now, we'll use the data from PHP
      const user = <?php echo json_encode($users); ?>.find(u => u.user_id == userId);
      const tellerServices = <?php echo json_encode($teller_services); ?>;

      if (user && !['Admin', 'Customer'].includes(user.role_name)) {
        document.getElementById('editUserId').value = user.user_id;
        document.getElementById('editUsername').value = user.username;
        document.getElementById('editEmail').value = user.email || '';
        document.getElementById('editFirstName').value = user.first_name;
        document.getElementById('editLastName').value = user.last_name;
        document.getElementById('editRoleId').value = user.role_id;
        document.getElementById('editIsActive').checked = user.is_active == 1;

        // Toggle service assignment based on role
        toggleEditServiceAssignment();

        // Set assigned services for tellers
        if (user.role_id == 2 && tellerServices[user.user_id]) {
          document.querySelectorAll('.service-checkbox').forEach(checkbox => {
            checkbox.checked = tellerServices[user.user_id].includes(parseInt(checkbox.value));
          });
        } else {
          document.querySelectorAll('.service-checkbox').forEach(checkbox => {
            checkbox.checked = false;
          });
        }

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
        modal.show();
      }
    }

    Chart.defaults.font.family = '"Segoe UI", Arial, sans-serif';
    Chart.defaults.font.size = 12;
    Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('--admin-muted').trim();
    Chart.defaults.borderColor = getComputedStyle(document.documentElement).getPropertyValue('--admin-border').trim();
    let serviceChart, hourChart;
    document.addEventListener('admin-theme-change', () => {
      const styles = getComputedStyle(document.documentElement);
      const textColor = styles.getPropertyValue('--admin-muted').trim();
      const gridColor = styles.getPropertyValue('--admin-border').trim();
      Chart.defaults.color = textColor;
      Chart.defaults.borderColor = gridColor;
      [serviceChart, hourChart].forEach(chart => {
        if (!chart) return;
        chart.options.plugins.legend.labels.color = textColor;
        Object.values(chart.options.scales).forEach(scale => {
          scale.ticks.color = textColor;
          scale.grid.color = gridColor;
          scale.border.color = gridColor;
        });
        chart.update('none');
      });
    });

    async function setTellerAvailability(userId, available, button) {
      button.disabled = true;
      try {
        const response = await fetch('admin_data.php', {
          method: 'POST',
          body: new URLSearchParams({
            user_id: String(userId),
            is_available: available ? '1' : '0',
            csrf_token: <?php echo json_encode($_SESSION['availability_csrf']); ?>
          })
        });
        const result = await response.json();
        if (!response.ok || result.error) throw new Error(result.error || 'Unable to update availability.');
        loadData();
      } catch (error) {
        alert(error.message);
      } finally {
        button.disabled = false;
      }
    }

    function loadData() {
      setDefaultAnalyticsDates();

      const params = new URLSearchParams();
      const analyticsStart = document.getElementById('analyticsStartDate')?.value;
      const analyticsEnd = document.getElementById('analyticsEndDate')?.value;

      if (analyticsStart) params.set('analytics_start', analyticsStart);
      if (analyticsEnd) params.set('analytics_end', analyticsEnd);

      fetch(`admin_data.php?${params.toString()}`)
        .then(res => res.json())
        .then(data => {
          // Dashboard -> Tellers
          let tellersHtml = "";
          data.tellers.forEach(t => {
            const status = String(t.status || 'Offline').toLowerCase();
            const statusClass = ['active', 'idle', 'offline', 'unavailable'].includes(status) ? status : 'offline';
            const avgWait = Number(t.avg_wait);
            const available = Number(t.is_available) === 1;
            tellersHtml += `
              <div class="col-12 col-md-6 col-xl-6 col-xxl-3">
                <article class="card teller-card h-100">
                  <div class="teller-card-heading"><span class="teller-avatar"><svg class="icon" aria-hidden="true"><use href="#icon-user"></use></svg></span><span class="status-indicator status-${statusClass}">${escapeHtml(t.status || 'Offline')}</span></div>
                  <h3>${escapeHtml(t.teller_name)}</h3>
                  <p class="teller-service">${escapeHtml(t.services || 'None assigned')}</p>
                  <dl class="teller-details">
                    <div><dt>Queue length</dt><dd>${escapeHtml(t.queue_length || 0)}</dd></div>
                    <div><dt>Next customer</dt><dd>${escapeHtml(t.next_customer || 'None')}</dd></div>
                    <div><dt>Average wait</dt><dd>${Number.isFinite(avgWait) ? avgWait.toFixed(1) : '0.0'} <span>min</span></dd></div>
                  </dl>
                  ${t.pwd_fallback ? `<p class="coverage-note">PWD coverage: ${escapeHtml(t.priority_teller_name)} is unavailable.</p>` : ''}
                  <button type="button" class="btn btn-sm ${available ? 'btn-outline-warning' : 'btn-outline-success'} availability-button"
                    data-confirm-click="${available ? 'Mark this teller unavailable?' : 'Mark this teller available?'}"
                    onclick="setTellerAvailability(${Number(t.user_id)}, ${!available}, this)">
                    ${available ? 'Mark unavailable' : 'Mark available'}
                  </button>
                </article>
              </div>`;
          });
          document.getElementById("tellers-container").innerHTML = tellersHtml;

          // Transactions
          let txHtml = "";
          const transactionStatusColors = new Map([
            ['completed', 'success'],
            ['waiting', 'warning text-dark'],
            ['in_progress', 'primary'],
            ['skipped', 'secondary'],
            ['cancelled', 'danger']
          ]);
          data.transactions.forEach(tx => {
            const status = String(tx.status ?? '').trim().toLowerCase();
            const statusClass = transactionStatusColors.get(status) || 'dark';
            const statusLabel = status.replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase()) || 'N/A';
            txHtml += `<tr>
          <td>${tx.ticket_number}</td>
          <td>${tx.customer_name}</td>
          <td>${tx.service_name}</td>
          <td>${tx.teller_name}</td>
          <td><span class="badge bg-${statusClass}">${escapeHtml(statusLabel)}</span></td>
          <td>${tx.created_at}</td>
          <td>${tx.completed_at || 'N/A'}</td>
        </tr>`;
          });
          document.getElementById("transactions-list-page").innerHTML = txHtml;
          paginateTransactionsTable(1);

          // Analytics Summary
          let summaryCards = [
            ['Total Tickets', data.analytics.total_tickets],
            ['Served', data.analytics.total_served],
            ['Waiting', data.analytics.currently_waiting],
            ['Skipped', data.analytics.skipped_count],
            ['Avg Wait', `${data.analytics.avg_wait} mins`],
            ['Avg Service', `${data.analytics.avg_service} mins`],
            ['Busiest Service', data.analytics.busiest_service],
            ['Top Teller', data.analytics.most_active_teller]
          ];

          let summaryHtml = summaryCards.map(card => `
            <div class="col-md-3">
              <div class="card p-3 h-100">
                <small class="text-muted">${card[0]}</small>
                <strong>${card[1] ?? 'N/A'}</strong>
              </div>
            </div>
          `).join('');
          document.getElementById("analytics-summary").innerHTML = summaryHtml;

          // Charts
          let serviceLabels = data.service_stats.map(s => s.service_name);
          let serviceCounts = data.service_stats.map(s => s.ticket_count);
          if (serviceChart) serviceChart.destroy();
          serviceChart = new Chart(document.getElementById("serviceChart"), {
            type: 'bar',
            data: {
              labels: serviceLabels,
              datasets: [{
                label: 'Tickets',
                data: serviceCounts,
                backgroundColor: '#47664d', borderRadius: 5
              }]
            },
            options: {
              responsive: true,
              scales: {
                y: {
                  beginAtZero: true,
                  ticks: {
                    precision: 0
                  }
                }
              }
            }
          });

          let hourLabels = data.hour_stats.map(h => h.hour_label);
          let hourCounts = data.hour_stats.map(h => h.ticket_count);
          if (hourChart) hourChart.destroy();
          hourChart = new Chart(document.getElementById("hourChart"), {
            type: 'bar',
            data: {
              labels: hourLabels,
              datasets: [{
                label: 'Tickets by Hour',
                data: hourCounts,
                backgroundColor: '#d4af43', borderRadius: 5
              }]
            },
            options: {
              responsive: true,
              scales: {
                y: {
                  beginAtZero: true,
                  ticks: {
                    precision: 0
                  }
                }
              }
            }
          });

          let tellerPerformanceHtml = "";
          data.teller_performance.forEach(teller => {
            tellerPerformanceHtml += `
              <tr>
                <td>${teller.teller_name}</td>
                <td>${teller.served_count || 0}</td>
                <td>${teller.skipped_count || 0}</td>
                <td>${teller.avg_wait || 0} mins</td>
                <td>${teller.avg_service || 0} mins</td>
              </tr>`;
          });

          document.getElementById("tellerPerformanceBody").innerHTML = tellerPerformanceHtml || `
            <tr>
              <td colspan="5" class="text-center text-muted">No teller activity for this date range.</td>
            </tr>`;


        });
    }

    setInterval(loadData, 300000);
    loadData();
  </script>
