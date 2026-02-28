<?php
/**
 * Word Document Viewer
 * Renders Word (.docx) files as HTML using PhpWord
 */

// Load PhpWord library
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
}
if (!file_exists($autoloadPath)) {
    $autoloadPath = __DIR__ . '/lib/php/vendor/autoload.php';
}

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    die('PhpWord library not found');
}

if (!class_exists('\PhpOffice\PhpWord\IOFactory')) {
    die('PhpWord IOFactory class not found');
}

use PhpOffice\PhpWord\IOFactory;

$file = isset($_GET['file']) ? $_GET['file'] : '';
if (empty($file)) {
    die('No file specified');
}

// Security check
$realPath = realpath(__DIR__ . '/' . $file);
$storagePath = realpath(__DIR__ . '/storage');

if (!$realPath || strpos($realPath, $storagePath) !== 0) {
    die('Invalid file path');
}

if (!file_exists($realPath)) {
    die('File not found');
}

$title = pathinfo($file, PATHINFO_BASENAME);

try {
    if (!class_exists('PhpOffice\PhpWord\IOFactory')) {
        throw new Exception('PhpWord IOFactory class not available');
    }
    $phpWord = IOFactory::load($realPath);
    
    $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . ' - Word Viewer</title>
    <link rel="stylesheet" href="ressources/css/style.css">
    <style>
        .word-content { padding: 20px; background: #fff; color: #000; min-height: 400px; }
        .word-content h1, .word-content h2, .word-content h3 { color: #1c88b8; margin-top: 20px; }
        .word-content p { line-height: 1.6; margin-bottom: 10px; }
        .word-content ul, .word-content ol { margin-bottom: 10px; padding-left: 20px; }
        .word-content li { margin-bottom: 5px; }
        .word-content table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        .word-content th, .word-content td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .word-content th { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="title">Word Document Viewer</div>
            <div class="subtitle">' . htmlspecialchars($title) . '</div>
            <div class="download-link">
                <a href="' . htmlspecialchars($file) . '" download>Download Original File</a>
                <br><br>
                <a href="index.php">← Back to Gallery</a>
            </div>
        </div>
        <div class="excel-container">
            <div class="word-content">';
    
    foreach ($phpWord->getSections() as $section) {
        $elements = $section->getElements();
        foreach ($elements as $element) {
            if (method_exists($element, 'getElements')) {
                foreach ($element->getElements() as $textElement) {
                    if (method_exists($textElement, 'getText')) {
                        $html .= '<p>' . htmlspecialchars($textElement->getText()) . '</p>';
                    }
                }
            } elseif (method_exists($element, 'getText')) {
                $html .= '<p>' . htmlspecialchars($element->getText()) . '</p>';
            }
        }
    }
    
    $html .= '            </div>
        </div>
        <div class="footer">
            Copyright © 2024 TipTop Transfer. All rights reserved.
        </div>
    </div>
</body>
</html>';
    
    echo $html;
    
} catch (Exception $e) {
    echo 'Error loading file: ' . htmlspecialchars($e->getMessage());
}
?>
