<?php
/**
 * Excel File Viewer
 * Renders Excel files as HTML tables using PhpSpreadsheet
 */

require_once __DIR__ . '/../lib/php/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$file = isset($_GET['file']) ? $_GET['file'] : '';
if (empty($file)) {
    die('No file specified');
}

// Handle various path formats
$filePath = $file;

// If path contains Windows drive letter or full path, try to extract relative path
if (preg_match('/^([A-Za-z]:\\\\|\\\\)/', $filePath)) {
    // This is a Windows absolute path - try to extract the relative part
    $filePath = str_replace('\\', '/', $filePath);
    if (preg_match('/\/mobileAppUI\/web\/(.+)$/i', $filePath, $matches)) {
        $filePath = $matches[1];
    }
}

// If path doesn't start with "storage/", add it
if (!preg_match('|^storage/|', $filePath)) {
    $filePath = 'storage/' . ltrim($filePath, '/');
}

// Security: ensure file is within storage directory
$realPath = realpath(__DIR__ . '/../' . $filePath);
$storagePath = realpath(__DIR__ . '/../storage');

if (!$realPath || strpos($realPath, $storagePath) !== 0) {
    die('Invalid file path: ' . htmlspecialchars($filePath));
}

if (!file_exists($realPath)) {
    die('File not found');
}

try {
    $spreadsheet = IOFactory::load($realPath);
    $worksheet = $spreadsheet->getActiveSheet();
    
    $title = pathinfo($file, PATHINFO_BASENAME);
    $highestRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();
    $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
    
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?> - Excel Viewer</title>
        <link rel="stylesheet" href="../ressources/css/excel-style.css">
        <link rel="stylesheet" href="../ressources/css/style.css">
    </head>
    <body>
        <div class="wrapper">
            <div class="header">
                <div class="title">Excel Viewer</div>
                <div class="subtitle"><?php echo htmlspecialchars($title); ?></div>
                <div class="download-link">
                    <a href="../<?php echo htmlspecialchars($filePath); ?>" download>Download Original File</a>
                    <br><br>
                    <a href="../index.php">← Back to Gallery</a>
                </div>
            </div>
            
            <div class="excel-container">
                <table class="excel-table">
                    <?php
                    for ($row = 1; $row <= $highestRow; $row++) {
                        echo '<tr>';
                        for ($col = 1; $col <= $highestColumnIndex; $col++) {
                            $cellCoordinate = Coordinate::stringFromColumnIndex($col) . $row;
                            $cell = $worksheet->getCell($cellCoordinate);
                            $value = $cell->getValue();
                            
                            if ($cell->getDataType() == \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA) {
                                $value = $cell->getCalculatedValue();
                            }
                            
                            $formattedValue = htmlspecialchars($value ?? '');
                            $cellClass = ($row == 1) ? 'header-cell' : 'data-cell';
                            
                            echo '<td class="' . $cellClass . '">' . $formattedValue . '</td>';
                        }
                        echo '</tr>';
                    }
                    ?>
                </table>
            </div>
            
            <div class="footer">
                Copyright © 2024 TipTop Transfer. All rights reserved.
            </div>
        </div>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    echo 'Error loading file: ' . htmlspecialchars($e->getMessage());
}
?>
