<!-- Transactions tab: table body is refreshed by loadData() in scripts.php. -->
  <!-- Transactions Section -->
<div id="transactions" class="section" style="display:none;">
    <div class="card p-3">
        <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-3">
            <h6 class="section-title mb-0">All Transactions</h6>
            <button type="button" id="archiveTransactionsButton" class="btn btn-warning btn-sm" onclick="archiveTransactions(this)">
                <svg class="icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M4 8h16v13H4zM3 3h18v5H3zM9 12h6"/></svg>
                Move All to History
            </button>
        </div>
        <p class="text-muted small">Manual reset: moves every current transaction, including waiting and in-progress tickets, to History and clears the current queue. Use after service has ended.</p>
        <div id="archiveTransactionsFeedback" role="status" hidden></div>
        <div class="table-scroll">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Ticket No</th>
                        <th>Customer Name</th>
                        <th>Service</th>
                        <th>Teller</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Completed</th>
                    </tr>
                </thead>
                <tbody id="transactions-list-page"></tbody>
            </table>
        </div>
        <div class="table-pagination" id="transactionsPagination"></div>
    </div>
</div>
