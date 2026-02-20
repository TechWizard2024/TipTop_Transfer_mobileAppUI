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
<link rel="stylesheet" href="ressources/css/excel-style.css">
<link rel="stylesheet" href="ressources/css/style.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://code.jquery.com/jquery-ui-1.13.2.min.css">
<style>

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
            $displayedRows = 0;
            for ($row = 1; $row <= $highestRow; $row++) {
                // Check if row is empty (skip empty rows except header)
                $isEmptyRow = true;
                if ($row !== 1) {
                    for ($col = 1; $col <= $highestColumnIndex; $col++) {
                        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                        $cell = $worksheet->getCell($columnLetter . $row);
                        $value = $cell->getValue();
                        if (!empty($value)) {
                            $isEmptyRow = false;
                            break;
                        }
                    }
                }
                
                // Display row only if it's not empty
                if (!$isEmptyRow || $row === 1) {
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
                    if ($row !== 1) {
                        $displayedRows++;
                    }
                }
            }
            
            echo '</table>';
            
            echo '<p style="margin-top: 20px;">Total rows: ' . $displayedRows . '</p>';
            
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
