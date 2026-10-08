<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Admin page head: CSS and third-party chart libraries. -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MOELCI-II Admin Dashboard</title>
  <link rel="stylesheet" href="../css/admin_board.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="../assets/imgs/moelci_logo.png">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
  <style>
    .settings-tab {
      margin-bottom: 20px;
    }

    .settings-section {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .time-inputs {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .holiday-list,
    .break-list {
      max-height: 200px;
      overflow-y: auto;
    }

    .user-actions {
      display: flex;
      gap: 5px;
    }

    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
    }

    .service-checkboxes {
      max-height: 200px;
      overflow-y: auto;
      border: 1px solid #dee2e6;
      padding: 10px;
      border-radius: 5px;
    }

    .service-checkbox-item {
      margin-bottom: 8px;
    }

    .modal-lg {
      max-width: 600px;
    }

    .history-table-wrapper {
    max-height: 375px;      /* adjust as needed */
    overflow-y: auto;
    border: 1px solid #ddd;
}

    .table-pagination {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 8px;
      margin-top: 10px;
      font-size: 0.9rem;
    }

    .table-pagination button {
      min-width: 34px;
      padding: 4px 10px;
    }

  </style>
</head>
