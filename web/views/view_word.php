<?php
/**
 * Word Document Viewer
 * Renders Word (.docx) files as HTML using PhpWord with improved formatting
 */

// Load PhpWord library
$autoloadPath = __DIR__ . '/../lib/php/vendor/autoload.php';

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    die('PhpWord library not found');
}

if (!class_exists('PhpOffice\PhpWord\IOFactory')) {
    die('PhpWord IOFactory class not found');
}

use PhpOffice\PhpWord\IOFactory;

$file = isset($_GET['file']) ? $_GET['file'] : '';
if (empty($file)) {
    die('No file specified');
}

// Handle various path formats
$filePath = $file;

// If path contains Windows drive letter or full path, try to extract relative path
if (preg_match('/^([A-Za-z]:\\\\|\\\\)/', $filePath)) {
    $filePath = str_replace('\\', '/', $filePath);
    if (preg_match('/\/mobileAppUI\/web\/(.+)$/i', $filePath, $matches)) {
        $filePath = $matches[1];
    }
}

// If path doesn't start with "storage/", add it
if (!preg_match('|^storage/|', $filePath)) {
    $filePath = 'storage/' . ltrim($filePath, '/');
}

// Security check
$realPath = realpath(__DIR__ . '/../' . $filePath);
$storagePath = realpath(__DIR__ . '/../storage');

if (!$realPath || strpos($realPath, $storagePath) !== 0) {
    die('Invalid file path: ' . htmlspecialchars($filePath));
}

if (!file_exists($realPath)) {
    die('File not found');
}

$title = pathinfo($file, PATHINFO_BASENAME);

/**
 * Get inline styles from a font style object
 */
function getFontStyles($fontStyle) {
    $styles = [];
    
    if (!is_object($fontStyle)) {
        return $styles;
    }
    
    // Bold
    if (method_exists($fontStyle, 'isBold') && $fontStyle->isBold()) {
        $styles[] = 'font-weight: bold';
    }
    
    // Italic
    if (method_exists($fontStyle, 'isItalic') && $fontStyle->isItalic()) {
        $styles[] = 'font-style: italic';
    }
    
    // Underline
    if (method_exists($fontStyle, 'getUnderline')) {
        $underline = $fontStyle->getUnderline();
        if ($underline && $underline !== 'none') {
            $styles[] = 'text-decoration: underline';
        }
    }
    
    // Font size
    if (method_exists($fontStyle, 'getSize')) {
        $size = $fontStyle->getSize();
        if ($size) {
            $styles[] = 'font-size: ' . ($size / 10) . 'em';
        }
    }
    
    // Font color
    if (method_exists($fontStyle, 'getColor')) {
        $color = $fontStyle->getColor();
        if ($color && $color !== '000000') {
            $styles[] = 'color: #' . $color;
        }
    }
    
    return $styles;
}

/**
 * Render text with all its formatting
 */
function renderFormattedText($element, &$html) {
    if (!is_object($element)) {
        $html .= htmlspecialchars((string)$element);
        return;
    }
    
    // Get text content
    $text = '';
    if (method_exists($element, 'getText')) {
        $text = $element->getText();
    } elseif (method_exists($element, '__toString')) {
        $text = (string)$element;
    }
    
    if (empty($text)) {
        return;
    }
    
    // Get font styles
    $fontStyle = null;
    if (method_exists($element, 'getFontStyle')) {
        $fontStyle = $element->getFontStyle();
    }
    
    $styles = getFontStyles($fontStyle);
    
    if (!empty($styles)) {
        $styleAttr = 'style="' . implode('; ', $styles) . '"';
        $html .= '<span ' . $styleAttr . '>' . htmlspecialchars($text) . '</span>';
    } else {
        $html .= htmlspecialchars($text);
    }
}

/**
 * Process any element and convert to HTML
 */
function processWordElement($element, &$html) {
    if (!is_object($element)) {
        $html .= htmlspecialchars((string)$element);
        return;
    }
    
    // TextRun - contains formatted text
    if (method_exists($element, 'getElements')) {
        $subElements = $element->getElements();
        if (!empty($subElements)) {
            foreach ($subElements as $subElement) {
                renderFormattedText($subElement, $html);
            }
        }
        return;
    }
    
    // Plain text element
    if (method_exists($element, 'getText')) {
        $text = $element->getText();
        if ($text !== null && $text !== '') {
            renderFormattedText($element, $html);
        }
        return;
    }
    
    // Fallback: try __toString
    if (method_exists($element, '__toString')) {
        $html .= htmlspecialchars((string)$element);
    }
}

try {
    $phpWord = IOFactory::load($realPath);
    
    $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . ' - Word Viewer</title>
    <link rel="stylesheet" href="../ressources/css/style.css">
    <style>
        .word-document {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px;
            background: #fff;
            color: #333;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.8;
            font-size: 14px;
        }
        
        .word-content {
            padding: 30px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            min-height: 500px;
        }
        
        .word-content h1 { 
            font-size: 2em; 
            color: #1c88b8; 
            margin: 0.67em 0;
            padding-bottom: 0.3em;
            border-bottom: 2px solid #1c88b8;
        }
        .word-content h2 { 
            font-size: 1.5em; 
            color: #2c3e50; 
            margin: 0.83em 0;
            padding-bottom: 0.2em;
            border-bottom: 1px solid #ddd;
        }
        .word-content h3 { 
            font-size: 1.17em; 
            color: #34495e; 
            margin: 1em 0 0.5em;
            font-weight: 600;
        }
        .word-content h4, .word-content h5, .word-content h6 { 
            color: #555; 
            margin: 1em 0 0.5em;
            font-weight: 600;
        }
        
        .word-content p { 
            margin: 0 0 1em 0;
            text-align: justify;
        }
        
        .word-content ul, .word-content ol { 
            margin: 0 0 1em 0;
            padding-left: 2em;
        }
        .word-content li { 
            margin-bottom: 0.5em;
        }
        
        .word-content table { 
            border-collapse: collapse; 
            width: 100%; 
            margin: 1em 0;
            font-size: 13px;
        }
        .word-content th, .word-content td { 
            border: 1px solid #ddd; 
            padding: 10px 12px; 
            text-align: left;
        }
        .word-content th { 
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .word-content tr:nth-child(even) {
            background: #fafafa;
        }
        
        .word-content blockquote {
            margin: 1em 0;
            padding: 0.5em 1em;
            border-left: 4px solid #1c88b8;
            background: #f8f9fa;
            color: #555;
        }
        
        .word-content hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 1.5em 0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="title">Word Document Viewer</div>
            <div class="subtitle">' . htmlspecialchars($title) . '</div>
            <div class="download-link">
                <a href="../' . htmlspecialchars($filePath) . '" download>Download Original File</a>
                <br><br>
                <a href="../index.php">← Back to Gallery</a>
            </div>
        </div>
        <div class="excel-container">
            <div class="word-content">
                <div class="word-document">';
    
    // Process each section
    $sections = $phpWord->getSections();
    foreach ($sections as $section) {
        if (!method_exists($section, 'getElements')) {
            continue;
        }
        
        $elements = $section->getElements();
        
        foreach ($elements as $element) {
            if (!is_object($element)) {
                continue;
            }
            
            // Check if it's a table by class name
            $elementClass = get_class($element);
            
            // Simple table detection - might not be perfect but covers most cases
            if (stripos($elementClass, 'Table') !== false) {
                $html .= '<table>';
                
                // Try to iterate using foreach (Table implements Traversable)
                try {
                    foreach ($element as $row) {
                        $html .= '<tr>';
                        
                        // Try to iterate cells
                        foreach ($row as $cell) {
                            $html .= '<td>';
                            
                            if (method_exists($cell, 'getElements')) {
                                $cellElements = $cell->getElements();
                                foreach ($cellElements as $cellElement) {
                                    processWordElement($cellElement, $html);
                                }
                            }
                            
                            $html .= '</td>';
                        }
                        $html .= '</tr>';
                    }
                } catch (Exception $e) {
                    // If iteration fails, skip table
                }
                
                $html .= '</table>';
                continue;
            }
            
            // Handle regular paragraph/text content
            $html .= '<p>';
            
            if (method_exists($element, 'getElements')) {
                $subElements = $element->getElements();
                foreach ($subElements as $subElement) {
                    processWordElement($subElement, $html);
                }
            } elseif (method_exists($element, 'getText')) {
                $text = $element->getText();
                if ($text) {
                    renderFormattedText($element, $html);
                }
            } else {
                processWordElement($element, $html);
            }
            
            $html .= '</p>';
        }
    }
    
    $html .= '                </div>
            </div>
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
