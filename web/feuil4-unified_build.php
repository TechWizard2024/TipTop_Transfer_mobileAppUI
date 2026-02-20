<?php
// Feuil4-Unified_Build Excel Viewer
// This file reads and displays the content of Feuil4-Unified_Build.xlsx

// Load PhpSpreadsheet library
require_once __DIR__ . '/lib/php/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$excelFile = __DIR__ . '/storage/excel/Feuil4-Unified_Build.xlsx';
$pageTitle = 'Feuil4 - Unified Build';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TipTop Transfer - <?php echo htmlspecialchars($pageTitle); ?></title>
<link rel="stylesheet" href="ressources/css/style.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://code.jquery.com/jquery-ui-1.13.2.min.css">
<style>
/* Excel Table Container */
.excel-container {
    background-color: #1a1a1a;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

/* Controls Section */
.excel-controls {
    margin-bottom: 20px;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.control-group {
    display: flex;
    gap: 10px;
    align-items: center;
}

.search-box {
    padding: 10px 15px;
    border: 2px solid #4CAF50;
    border-radius: 4px;
    background-color: #2a2a2a;
    color: white;
    font-size: 14px;
    width: 250px;
    transition: all 0.3s ease;
}

.search-box:focus {
    outline: none;
    box-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
    border-color: #45a049;
}

.stats-box {
    background-color: #2a2a2a;
    padding: 10px 15px;
    border-radius: 4px;
    color: #4CAF50;
    font-weight: bold;
    border-left: 4px solid #4CAF50;
}

.action-buttons {
    display: flex;
    gap: 10px;
}

.action-btn {
    padding: 10px 15px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s ease;
}

.action-btn:hover {
    background-color: #45a049;
}

/* Excel Table Styling */
.excel-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
    color: white;
    background-color: #2a2a2a;
}

.excel-table thead tr {
    background-color: #4CAF50;
    color: white;
    position: sticky;
    top: 0;
    z-index: 100;
}

.excel-table th {
    padding: 12px 8px;
    text-align: left;
    font-weight: bold;
    border: 1px solid #555;
    white-space: pre-wrap;
    word-wrap: break-word;
    overflow-wrap: break-word;
    cursor: pointer;
    user-select: none;
}

.excel-table th::after {
    content: " ↕";
    color: rgba(255, 255, 255, 0.5);
    font-size: 12px;
}

.excel-table th.sorted-asc::after {
    content: " ↑";
    color: white;
}

.excel-table th.sorted-desc::after {
    content: " ↓";
    color: white;
}

.excel-table tbody tr {
    border-bottom: 1px solid #444;
    transition: background-color 0.2s ease;
}

.excel-table tbody tr:nth-child(odd) {
    background-color: #252525;
}

.excel-table tbody tr:nth-child(even) {
    background-color: #2a2a2a;
}

.excel-table tbody tr:hover {
    background-color: #3a3a3a;
    box-shadow: inset 0 0 10px rgba(76, 175, 80, 0.1);
}

.excel-table td {
    padding: 10px 8px;
    border: 1px solid #444;
    white-space: pre-wrap;
    word-wrap: break-word;
    overflow-wrap: break-word;
    max-width: 400px;
    vertical-align: top;
}

.excel-table td:empty::before {
    content: "-";
    color: #666;
    font-style: italic;
}

/* DataTables Pagination */
.dataTables_wrapper .dataTables_paginate {
    margin-top: 20px;
    text-align: right;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 8px 12px;
    margin: 0 4px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background-color: #45a049;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background-color: #2196F3;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
    background-color: #666;
    cursor: not-allowed;
    opacity: 0.5;
}

/* Info Section */
.dataTables_wrapper .dataTables_info {
    color: #aaa;
    margin-top: 15px;
    font-size: 13px;
}

. back-link {
    margin: 20px 0;
}

.back-link a {
    color: #4CAF50;
    text-decoration: none;
    font-weight: bold;
}

.back-link a:hover {
    text-decoration: underline;
}

.error-message {
    background-color: #ffdddd;
    border-left: 6px solid #f44336;
    margin: 20px 0;
    padding: 10px;
}

.no-results {
    padding: 20px;
    text-align: center;
    color: #aaa;
    font-style: italic;
}

</style>
</head>

<body>

<div class="wrapper">

<div class="header">
        <div class="title">TipTop Transfer</div>
        <div class="subtitle"><?php echo htmlspecialchars($pageTitle); ?></div>
    </div>

    <div class="topbar"></div>

    <div class="back-link">
        <a href="index.php">&larr; Back to Gallery</a>
    </div>

<?php

// Check if file exists
if (!file_exists($excelFile)) {
    echo '<div class="error-message">Error: Excel file not found at ' . htmlspecialchars($excelFile) . '</div>';
} else {
    // Try to read Excel file
    // First, check if PhpSpreadsheet is available
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        // PhpSpreadsheet not available - show simple message
        echo '<div class="error-message">';
        echo '<p><strong>Note:</strong> PhpSpreadsheet library is not installed.</p>';
        echo '<p>The Excel file exists but cannot be displayed without the PhpSpreadsheet library.</p>';
        echo '<p>You can:</p>';
        echo '<ul>';
        echo '<li>Download the Excel file directly: <a href="storage/excel/Feuil4-Unified_Build.xlsx">Feuil4-Unified_Build.xlsx</a></li>';
        echo '<li>Install PhpSpreadsheet using: composer require phpoffice/phpspreadsheet</li>';
        echo '</ul>';
        echo '</div>';
    } else {
        // PhpSpreadsheet is available - read and display Excel
        try {
            $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($excelFile);
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
            $spreadsheet = $reader->load($excelFile);
            $worksheet = $spreadsheet->getActiveSheet();
            
            echo '<table class="excel-table">';
            
            // Get highest row and column
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            
            // Display rows
            for ($row = 1; $row <= $highestRow; $row++) {
                echo '<tr>';
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $cell = $worksheet->getCell($columnLetter . $row);
                    $value = $cell->getValue();
                    
                    if ($row === 1) {
                        // Header row
                        echo '<th>' . nl2br(htmlspecialchars($value ?? '')) . '</th>';
                    } else {
                        echo '<td>' . nl2br(htmlspecialchars($value ?? '')) . '</td>';
                    }
                }
                echo '</tr>';
            }
            
            echo '</table>';
            
            echo '<p style="margin-top: 20px;">Total rows: ' . ($highestRow - 1) . '</p>';
            
        } catch (Exception $e) {
            echo '<div class="error-message">Error reading Excel file: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}
?>

    <div class="footer">
        Copyright © 2024 TipTop Transfer. All rights reserved.
    </div>

</div>

</body>
</html>
