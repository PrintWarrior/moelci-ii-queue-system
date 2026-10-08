<!-- Admin dashboard scripts: navigation, settings tabs, modals, AJAX refresh, and charts. -->
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function showSection(id, btn) {
      document.querySelectorAll('.section').forEach(sec => sec.style.display = "none");
      document.getElementById(id).style.display = "block";
      document.querySelectorAll('.navbar .btn').forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
    }

    function confirmClearTransactions() {
    if (confirm('ARE YOU SURE YOU WANT TO CLEAR ALL TRANSACTIONS?\n\nThis action will permanently delete ALL transaction records and cannot be undone!')) {
        clearAllTransactions();
    }
}

function clearAllTransactions() {
    // Show loading state
    const clearBtn = document.querySelector('button[onclick="confirmClearTransactions()"]');
    const originalText = clearBtn.innerHTML;
    clearBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Clearing...';
    clearBtn.disabled = true;

    fetch('../includes/clear_transactions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('All transactions have been cleared successfully!');
            // Reload the transactions list
            loadData();
        } else {
            alert('Error clearing transactions: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error occurred while clearing transactions');
    })
    .finally(() => {
        // Restore button state
        clearBtn.innerHTML = originalText;
        clearBtn.disabled = false;
    });
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
        const settingsButton = Array.from(document.querySelectorAll('.navbar .btn'))
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

      if (user) {
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

    let serviceChart, hourChart;

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
            let statusClass = (t.status.toLowerCase() === "active") ? "status-active" : "status-idle";
            tellersHtml += `
    <div class="col-md-3">
        <div class="card p-3">
            <h6>${t.teller_name}</h6>
            <p><b>Service:</b> ${t.services || 'None Assigned'}</p>
            <p><b>Status:</b> <span class="status-indicator ${statusClass}">${t.status || 'N/A'}</span></p>
              <p><b>Queue Length:</b> ${t.queue_length || 0}</p>
              <p><b>Next Customer:</b> ${t.next_customer || 'None'}</p>
              <p><b>Average wait time:</b> ${t.avg_wait || 0} mins</p>
            </div>
          </div>`;
          });
          document.getElementById("tellers-container").innerHTML = tellersHtml;

          // Transactions
          let txHtml = "";
          data.transactions.forEach(tx => {
            txHtml += `<tr>
          <td>${tx.ticket_number}</td>
          <td>${tx.customer_name}</td>
          <td>${tx.service_name}</td>
          <td>${tx.teller_name}</td>
          <td>${tx.status}</td>
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
                backgroundColor: '#457b9d'
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
                backgroundColor: '#2a9d8f'
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

          // Settings
          let settingsHtml = `
        <h6 class="section-title">System Settings</h6>
        <p><b>Total Services:</b> ${data.settings.total_services}</p>
        <p><b>Active Tellers:</b> ${data.settings.active_tellers}</p>
        <p><b>Total Customers Today:</b> ${data.settings.customers_today}</p>
        <p><b>System Status:</b> <span class="status-active">Active</span></p>`;
          const settingsPage = document.getElementById("settings-page");
          if (settingsPage) {
            settingsPage.innerHTML = settingsHtml;
          }
        });
    }

    setInterval(loadData, 300000);
    loadData();
  </script>
