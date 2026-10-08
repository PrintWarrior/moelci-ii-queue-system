<!-- Transactions tab: table body is refreshed by loadData() in scripts.php. -->
  <!-- Transactions Section -->
<div id="transactions" class="section" style="display:none;">
    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="section-title mb-0">All Transactions</h6>
           <!-- <button class="btn btn-danger btn-sm" onclick="confirmClearTransactions()">
                Clear All Transactions
            </button> -->
        </div>
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
