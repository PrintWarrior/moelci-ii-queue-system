<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <!-- Teller page head: CSS and page-specific inline styles. -->
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
