<?php
/**
 * Word Document Viewer
 * Renders Word (.docx) files as HTML using PhpWord with full paragraph formatting
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
            $styles[] = 'font-size: ' . ($size / 10) . 'pt';
        }
    }
    
    // Font color
    if (method_exists($fontStyle, 'getColor')) {
        $color = $fontStyle->getColor();
        if ($color && $color !== '000000') {
            $styles[] = 'color: #' . $color;
        }
    }
    
    // Strikethrough
    if (method_exists($fontStyle, 'isStrikethrough') && $fontStyle->isStrikethrough()) {
        $styles[] = 'text-decoration: line-through';
    }
    
    return $styles;
}

/**
 * Get paragraph alignment CSS
 */
function getParagraphAlignment($paragraph) {
    if (!is_object($paragraph)) {
        return 'left';
    }
    
    if (method_exists($paragraph, 'getAlignment')) {
        $alignment = $paragraph->getAlignment();
        if ($alignment) {
            $alignmentMap = [
                'left' => 'left',
                'start' => 'left',
                'center' => 'center',
                'right' => 'right',
                'end' => 'right',
                'both' => 'justify',
                'justify' => 'justify',
                'distribute' => 'justify'
            ];
            return isset($alignmentMap[$alignment]) ? $alignmentMap[$alignment] : 'left';
        }
    }
    
    return 'left';
}

/**
 * Get paragraph indentation in points (1 point = 1/72 inch)
 */
function getParagraphIndentation($paragraph) {
    $indent = [
        'left' => 0,
        'right' => 0,
        'firstLine' => 0,
        'hanging' => 0
    ];
    
    if (!is_object($paragraph)) {
        return $indent;
    }
    
    // Left indent (in twips, 1 twip = 1/20 point = 0.05 point)
    if (method_exists($paragraph, 'getIndent')) {
        $leftIndent = $paragraph->getIndent();
        if ($leftIndent !== null && $leftIndent !== false) {
            $indent['left'] = round($leftIndent * 0.05, 1);
        }
    }
    
    // Right indent
    if (method_exists($paragraph, 'getRightIndent')) {
        $rightIndent = $paragraph->getRightIndent();
        if ($rightIndent !== null && $rightIndent !== false) {
            $indent['right'] = round($rightIndent * 0.05, 1);
        }
    }
    
    // First line indent
    if (method_exists($paragraph, 'getFirstLineIndent')) {
        $firstLineIndent = $paragraph->getFirstLineIndent();
        if ($firstLineIndent !== null && $firstLineIndent !== false) {
            $indent['firstLine'] = round($firstLineIndent * 0.05, 1);
        }
    }
    
    // Hanging indent (negative first line = hanging indent)
    if (method_exists($paragraph, 'getHangingIndent')) {
        $hangingIndent = $paragraph->getHangingIndent();
        if ($hangingIndent !== null && $hangingIndent !== false && $hangingIndent > 0) {
            $indent['hanging'] = round($hangingIndent * 0.05, 1);
        }
    }
    
    return $indent;
}

/**
 * Get paragraph spacing (before and after) in points
 */
function getParagraphSpacing($paragraph) {
    $spacing = [
        'before' => 0,
        'after' => 0,
        'line' => 240 // Default Word line spacing (240 = single)
    ];
    
    if (!is_object($paragraph)) {
        return $spacing;
    }
    
    // Space before paragraph (in twips)
    if (method_exists($paragraph, 'getSpaceBefore')) {
        $spaceBefore = $paragraph->getSpaceBefore();
        if ($spaceBefore !== null && $spaceBefore !== false) {
            $spacing['before'] = round($spaceBefore * 0.05, 1);
        }
    }
    
    // Space after paragraph
    if (method_exists($paragraph, 'getSpaceAfter')) {
        $spaceAfter = $paragraph->getSpaceAfter();
        if ($spaceAfter !== null && $spaceAfter !== false) {
            $spacing['after'] = round($spaceAfter * 0.05, 1);
        }
    }
    
    // Line spacing (in twips, 240 = single, 360 = 1.5, 480 = double)
    if (method_exists($paragraph, 'getLineSpacing')) {
        $lineSpacing = $paragraph->getLineSpacing();
        if ($lineSpacing !== null && $lineSpacing !== false && $lineSpacing > 0) {
            $spacing['line'] = $lineSpacing;
        }
    }
    
    return $spacing;
}

/**
 * Get list item properties from ListItemRun
 */
function getListItemProperties($element) {
    $listInfo = [
        'isList' => false,
        'type' => 'bullet',
        'level' => 0,
        'format' => null,
        'numId' => null
    ];
    
    if (!is_object($element)) {
        return $listInfo;
    }
    
    // Check if this is a ListItemRun
    $className = get_class($element);
    if (stripos($className, 'ListItemRun') === false) {
        return $listInfo;
    }
    
    $listInfo['isList'] = true;
    
    // Get the list type (bullet or number)
    if (method_exists($element, 'getDepth')) {
        $depth = $element->getDepth();
        if ($depth !== null) {
            $listInfo['level'] = intval($depth);
        }
    }
    
    // Check for numbering ID - use getParagraphStyle to get details
    if (method_exists($element, 'getParagraphStyle')) {
        try {
            $paraStyle = $element->getParagraphStyle();
            if (is_object($paraStyle)) {
                // Check for NumId
                if (method_exists($paraStyle, 'getNumId')) {
                    $numId = $paraStyle->getNumId();
                    if ($numId !== null && $numId !== 0) {
                        $listInfo['type'] = 'number';
                        $listInfo['numId'] = $numId;
                    }
                }
                
                // Check for list type
                if (method_exists($paraStyle, 'getListType')) {
                    $listType = $paraStyle->getListType();
                    if ($listType) {
                        // 0 = bullet, other values = number
                        $listInfo['type'] = ($listType == 0) ? 'bullet' : 'number';
                    }
                }
            }
        } catch (Exception $e) {
            // Ignore
        }
    }
    
    return $listInfo;
}

/**
 * Build complete paragraph style string
 */
function buildParagraphStyle($paragraph) {
    $styles = [];
    
    // Alignment
    $alignment = getParagraphAlignment($paragraph);
    $styles[] = "text-align: {$alignment}";
    
    // Indentation
    $indent = getParagraphIndentation($paragraph);
    
    // Handle hanging indent specially
    if ($indent['hanging'] > 0) {
        // Hanging indent: negative text-indent + positive padding-left
        $styles[] = "text-indent: -{$indent['hanging']}pt";
        $styles[] = "padding-left: " . ($indent['hanging'] + $indent['left']) . "pt";
    } else {
        // Normal first line indent
        if ($indent['firstLine'] > 0) {
            $styles[] = "text-indent: {$indent['firstLine']}pt";
        }
        if ($indent['left'] > 0) {
            $styles[] = "padding-left: {$indent['left']}pt";
        }
    }
    
    if ($indent['right'] > 0) {
        $styles[] = "padding-right: {$indent['right']}pt";
    }
    
    // Spacing before and after
    $spacing = getParagraphSpacing($paragraph);
    
    if ($spacing['before'] > 0) {
        $styles[] = "margin-top: {$spacing['before']}pt";
    } elseif ($spacing['before'] < 0) {
        $styles[] = "margin-top: 0";
    }
    
    if ($spacing['after'] > 0) {
        $styles[] = "margin-bottom: {$spacing['after']}pt";
    } elseif ($spacing['after'] < 0) {
        $styles[] = "margin-bottom: 0";
    }
    
    // Line spacing - convert twips to CSS line-height
    // 240 twips = 1 line = 1.0 line-height
    if ($spacing['line'] > 0) {
        $lineHeight = round($spacing['line'] / 240, 2);
        if ($lineHeight < 1.15) $lineHeight = 1.15; // Minimum 1.15 for readability
        $styles[] = "line-height: {$lineHeight}";
    } else {
        // Default line height if not specified
        $styles[] = "line-height: 1.15";
    }
    
    return implode('; ', $styles);
}

/**
 * Build list item style
 */
function buildListItemStyle($paragraph, $listInfo) {
    $styles = [];
    
    // Get indentation based on level (each level = 36pt = 0.5 inch)
    $level = isset($listInfo['level']) ? $listInfo['level'] : 0;
    $indentPt = ($level + 1) * 36;
    
    // For list items, use margin-left
    $styles[] = "margin-left: {$indentPt}pt";
    
    // Get paragraph indentation
    $indent = getParagraphIndentation($paragraph);
    if ($indent['left'] > 0) {
        $styles[] = "margin-left: " . ($indentPt + $indent['left']) . "pt";
    }
    
    // Line spacing for list items
    $spacing = getParagraphSpacing($paragraph);
    if ($spacing['line'] > 0) {
        $lineHeight = round($spacing['line'] / 240, 2);
        if ($lineHeight < 1) $lineHeight = 1;
        $styles[] = "line-height: {$lineHeight}";
    }
    
    return implode('; ', $styles);
}

/**
 * Render text with all its formatting
 */
function renderFormattedText($element, &$html) {
    if (!is_object($element)) {
        $html .= html_entity_decode((string)$element, ENT_QUOTES, 'UTF-8');
        return;
    }
    
    // Get text content
    $text = '';
    if (method_exists($element, 'getText')) {
        $text = $element->getText();
    } elseif (method_exists($element, '__toString')) {
        $text = (string)$element;
    }
    
    if ($text === null || $text === '') {
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
        $html .= '<span ' . $styleAttr . '>' . html_entity_decode($text, ENT_QUOTES, 'UTF-8') . '</span>';
    } else {
        $html .= html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Process any element and convert to HTML
 */
function processWordElement($element, &$html) {
    if (!is_object($element)) {
        $html .= html_entity_decode((string)$element, ENT_QUOTES, 'UTF-8');
        return;
    }
    
    // TextRun - contains formatted text
    if (method_exists($element, 'getElements')) {
        try {
            $subElements = $element->getElements();
            if (!empty($subElements)) {
                foreach ($subElements as $subElement) {
                    renderFormattedText($subElement, $html);
                }
            }
        } catch (Exception $e) {
            // Fallback to getText
            if (method_exists($element, 'getText')) {
                $text = $element->getText();
                if ($text) {
                    renderFormattedText($element, $html);
                }
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
    
    // Fallback
    if (method_exists($element, '__toString')) {
        $html .= html_entity_decode((string)$element, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Process a TextRun element
 */
function processTextRun($element, &$html) {
    if (!is_object($element)) {
        return;
    }
    
    // Get paragraph style
    $paraStyle = buildParagraphStyle($element);
    
    // Regular paragraph with margin for spacing
    $paraStyleWithMargin = $paraStyle;
    if (!empty($paraStyleWithMargin)) {
        $paraStyleWithMargin .= '; margin-bottom: 8pt';
    } else {
        $paraStyleWithMargin = 'margin-bottom: 8pt';
    }
    
    $styleAttr = !empty($paraStyleWithMargin) ? ' style="' . $paraStyleWithMargin . '"' : '';
    $html .= '<p' . $styleAttr . '>';
    
    // Process text content
    if (method_exists($element, 'getElements')) {
        try {
            $subElements = $element->getElements();
            if (!empty($subElements)) {
                foreach ($subElements as $subElement) {
                    processWordElement($subElement, $html);
                }
            }
        } catch (Exception $e) {
            if (method_exists($element, 'getText')) {
                $text = $element->getText();
                if ($text) {
                    renderFormattedText($element, $html);
                }
            }
        }
    } elseif (method_exists($element, 'getText')) {
        $text = $element->getText();
        if ($text) {
            renderFormattedText($element, $html);
        }
    }
    
    $html .= '</p>';
}

/**
 * Process a ListItemRun element
 */
function processListItem($element, &$html, &$openList, &$lastListType, &$lastNumId) {
    if (!is_object($element)) {
        return;
    }
    
    // Get list properties
    $listInfo = getListItemProperties($element);
    
    // Get paragraph style
    $paraStyle = buildParagraphStyle($element);
    
    // Handle list transitions
    if ($listInfo['isList']) {
        // Need to start a new list if type changes or numId changes
        $needsNewList = false;
        
        if ($openList === null) {
            $needsNewList = true;
        } elseif ($lastListType !== $listInfo['type']) {
            $needsNewList = true;
        } elseif ($listInfo['type'] === 'number' && $lastNumId !== $listInfo['numId']) {
            // Different numbering group
            $needsNewList = true;
        }
        
        if ($needsNewList) {
            // Close previous list
            if ($openList !== null) {
                $html .= '</' . $openList . '>';
                $openList = null;
            }
            
            // Start new list
            if ($listInfo['type'] === 'number') {
                $html .= '<ol class="word-list">';
                $openList = 'ol';
            } else {
                $html .= '<ul class="word-list">';
                $openList = 'ul';
            }
            $lastListType = $listInfo['type'];
            $lastNumId = $listInfo['numId'];
        }
        
        // Build list item style with indentation
        $listStyle = buildListItemStyle($element, $listInfo);
        $fullStyle = $paraStyle;
        if (!empty($listStyle)) {
            // Merge styles
            $fullStyle = $listStyle;
            if (!empty($paraStyle)) {
                $fullStyle = $listStyle . '; ' . $paraStyle;
            }
        }
        
        $styleAttr = !empty($fullStyle) ? ' style="' . $fullStyle . '"' : '';
        $html .= '<li' . $styleAttr . '>';
        
    } else {
        // Not a list item - close any open list
        if ($openList !== null) {
            $html .= '</' . $openList . '>';
            $openList = null;
            $lastListType = null;
            $lastNumId = null;
        }
        
        // Regular paragraph
        $paraStyleWithMargin = $paraStyle;
        if (!empty($paraStyleWithMargin)) {
            $paraStyleWithMargin .= '; margin-bottom: 8pt';
        } else {
            $paraStyleWithMargin = 'margin-bottom: 8pt';
        }
        
        $styleAttr = !empty($paraStyleWithMargin) ? ' style="' . $paraStyleWithMargin . '"' : '';
        $html .= '<p' . $styleAttr . '>';
    }
    
    // Process text content
    if (method_exists($element, 'getElements')) {
        try {
            $subElements = $element->getElements();
            if (!empty($subElements)) {
                foreach ($subElements as $subElement) {
                    processWordElement($subElement, $html);
                }
            }
        } catch (Exception $e) {
            if (method_exists($element, 'getText')) {
                $text = $element->getText();
                if ($text) {
                    renderFormattedText($element, $html);
                }
            }
        }
    } elseif (method_exists($element, 'getText')) {
        $text = $element->getText();
        if ($text) {
            renderFormattedText($element, $html);
        }
    }
    
    // Close element
    if ($listInfo['isList']) {
        $html .= '</li>';
    } else {
        $html .= '</p>';
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
            background: #1a1a1a;
            color: #fff;
            font-family: "Calibri", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11pt;
            line-height: 1.15;
        }
        
        .word-content {
            padding: 30px;
            background: black;
            color: white;
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
        
        /* Paragraph styling with proper default line height */
        .word-content p { 
            margin: 0;
            padding: 0;
            line-height: 1.15;
            min-height: 1em;
        }
        
        /* List styling - more prominent */
        .word-content ul.word-list, 
        .word-content ol.word-list {
            margin: 6pt 0;
            padding-left: 0;
            list-style-position: outside;
            line-height: 1.15;
        }
        
        .word-content ul.word-list {
            list-style-type: disc;
        }
        
        .word-content ol.word-list {
            list-style-type: decimal;
            padding-left: 18pt;
        }
        
        .word-content li {
            margin: 2pt 0;
            padding: 0;
            display: list-item;
            line-height: 1.15;
        }
        
        /* Table styling */
        .word-content table { 
            border-collapse: collapse; 
            width: 100%; 
            margin: 1em 0;
            font-size: 11pt;
        }
        .word-content th, .word-content td { 
            border: 1px solid #000; 
            padding: 5pt 8pt; 
            text-align: left;
        }
        .word-content th { 
            background: #f0f0f0;
            font-weight: bold;
            color: #000;
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
    
    $openList = null;
    $lastListType = null;
    $lastNumId = null;
    
    foreach ($sections as $section) {
        if (!method_exists($section, 'getElements')) {
            continue;
        }
        
        $elements = $section->getElements();
        
        foreach ($elements as $element) {
            if (!is_object($element)) {
                continue;
            }
            
            // Check element type
            $elementClass = get_class($element);
            
            // Close any open list before table
            if ($openList !== null && stripos($elementClass, 'Table') !== false) {
                $html .= '</' . $openList . '>';
                $openList = null;
                $lastListType = null;
                $lastNumId = null;
            }
            
            // Handle tables
            if (stripos($elementClass, 'Table') !== false) {
                // Close any open list
                if ($openList !== null) {
                    $html .= '</' . $openList . '>';
                    $openList = null;
                }
                
                $html .= '<table>';
                
                try {
                    foreach ($element as $row) {
                        $html .= '<tr>';
                        
                        foreach ($row as $cell) {
                            $html .= '<td>';
                            
                            if (method_exists($cell, 'getElements')) {
                                $cellElements = $cell->getElements();
                                foreach ($cellElements as $cellElement) {
                                    if (method_exists($cellElement, 'getElements')) {
                                        foreach ($cellElement->getElements() as $textElement) {
                                            processWordElement($textElement, $html);
                                        }
                                    } else {
                                        processWordElement($cellElement, $html);
                                    }
                                }
                            }
                            
                            $html .= '</td>';
                        }
                        $html .= '</tr>';
                    }
                } catch (Exception $e) {
                    // Skip table on error
                }
                
                $html .= '</table>';
                continue;
            }
            
            // Handle ListItemRun - check class name
            if (stripos($elementClass, 'ListItemRun') !== false) {
                processListItem($element, $html, $openList, $lastListType, $lastNumId);
            }
            // Handle TextRun - check class name
            elseif (stripos($elementClass, 'TextRun') !== false) {
                // Close any open list before TextRun (unless it's continuing from a list)
                // Actually, we should NOT close the list here - some TextRuns might be between list items
                // The processListItem function will handle the transition
                
                // Check if this is part of a list by checking paragraph style
                $listInfo = getListItemProperties($element);
                if (!$listInfo['isList'] && $openList !== null) {
                    // Check if this looks like continuation of text
                    // For now, let's close any list if we encounter a TextRun
                    // This might need refinement
                    try {
                        $hasSubElements = method_exists($element, 'getElements') && !empty($element->getElements());
                        if (!$hasSubElements) {
                            $html .= '</' . $openList . '>';
                            $openList = null;
                            $lastListType = null;
                            $lastNumId = null;
                        }
                    } catch (Exception $e) {}
                }
                
                processTextRun($element, $html);
            }
            // Handle other elements (Text, etc.)
            else {
                // Close any open list
                if ($openList !== null) {
                    $html .= '</' . $openList . '>';
                    $openList = null;
                    $lastListType = null;
                    $lastNumId = null;
                }
                
                // Process as text
                if (method_exists($element, 'getText')) {
                    $text = $element->getText();
                    if ($text) {
                        $html .= '<p style="margin-bottom: 8pt; line-height: 1.15">' . html_entity_decode($text, ENT_QUOTES, 'UTF-8') . '</p>';
                    }
                }
            }
        }
    }
    
    // Close any remaining open list
    if ($openList !== null) {
        $html .= '</' . $openList . '>';
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
