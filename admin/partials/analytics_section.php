<!-- Analytics tab: summary and charts are refreshed by loadData() in scripts.php. -->
  <!-- Analytics Section -->
  <div id="analytics" class="section" style="display:none;">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="section-title mb-0">Queue Analytics</h6>
        <div class="d-flex align-items-center gap-2">
          <input type="date" id="analyticsStartDate" class="form-control form-control-sm">
          <span>to</span>
          <input type="date" id="analyticsEndDate" class="form-control form-control-sm">
          <button type="button" class="btn btn-sm btn-primary" onclick="loadData()">Apply</button>
        </div>
      </div>

      <div id="analytics-summary" class="row g-3 mb-3"></div>

      <div class="row g-4">
        <div class="col-lg-6">
          <h6 class="section-title">Service Volume</h6>
          <canvas id="serviceChart" height="180"></canvas>
        </div>
        <div class="col-lg-6">
          <h6 class="section-title">Busiest Hours</h6>
          <canvas id="hourChart" height="180"></canvas>
        </div>
      </div>

      <div class="mt-4">
        <h6 class="section-title">Teller Performance</h6>
        <div class="table-responsive">
          <table class="table table-striped table-sm">
            <thead>
              <tr>
                <th>Teller</th>
                <th>Served</th>
                <th>Skipped</th>
                <th>Avg Wait</th>
                <th>Avg Service</th>
              </tr>
            </thead>
            <tbody id="tellerPerformanceBody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
