<?php
/**
 * Text File Viewer
 * Renders text files with line numbers
 */

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
$content = file_get_contents($realPath);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - Text Viewer</title>
    <link rel="stylesheet" href="ressources/css/style.css">
    <style>
        .text-content { padding: 20px; background: #1e1e1e; color: #d4d4d4; min-height: 400px; overflow-x: auto; font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.5; white-space: pre-wrap; }
        .line-number { color: #6a9955; padding-right: 20px; user-select: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="title">Text Viewer</div>
            <div class="subtitle"><?php echo htmlspecialchars($title); ?></div>
            <div class="download-link">
                <a href="<?php echo htmlspecialchars($file); ?>" download>Download Original File</a>
                <br><br>
                <a href="index.php">← Back to Gallery</a>
            </div>
        </div>
        <div class="excel-container">
            <pre class="text-content"><?php 
                $lines = explode("\n", $content);
                $lineNum = 1;
                foreach ($lines as $line) {
                    echo '<span class="line-number">' . str_pad($lineNum++, 4, ' ', STR_PAD_LEFT) . '</span>' . htmlspecialchars($line) . "\n";
                }
            ?></pre>
        </div>
        <div class="footer">
            Copyright © 2024 TipTop Transfer. All rights reserved.
        </div>
    </div>
</body>
</html>
