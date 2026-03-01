<?php
/**
 * YAML File Viewer
 * Renders YAML files with syntax highlighting
 */

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
    <title><?php echo htmlspecialchars($title); ?> - YAML Viewer</title>
    <link rel="stylesheet" href="../ressources/css/style.css">
    <style>
        .yaml-content { padding: 20px; background: #1e1e1e; color: #d4d4d4; min-height: 400px; overflow-x: auto; font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.5; }
        .yaml-key { color: #9cdcfe; }
        .yaml-value { color: #ce9178; }
        .yaml-string { color: #ce9178; }
        .yaml-number { color: #b5cea8; }
        .yaml-comment { color: #6a9955; }
        .yaml-boolean { color: #569cd6; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="title">YAML Viewer</div>
            <div class="subtitle"><?php echo htmlspecialchars($title); ?></div>
            <div class="download-link">
                <a href="<?php echo htmlspecialchars($filePath); ?>" download>Download Original File</a>
                <br><br>
                <a href="../index.php">← Back to Gallery</a>
            </div>
        </div>
        <div class="excel-container">
            <pre class="yaml-content"><?php 
                $lines = explode("\n", $content);
                foreach ($lines as $line) {
                    $line = htmlspecialchars($line);
                    if (preg_match('/^\s*#/', $line)) {
                        $line = '<span class="yaml-comment">' . $line . '</span>';
                    } elseif (preg_match('/^(\s*)([^:]+):(\s*)(.*)$/', $line, $matches)) {
                        $key = $matches[2];
                        $value = $matches[4];
                        $indent = $matches[1];
                        $afterColon = $matches[3];
                        if (in_array(strtolower($value), ['true', 'false', 'yes', 'no', 'null'])) {
                            $value = '<span class="yaml-boolean">' . $value . '</span>';
                        } elseif (is_numeric($value)) {
                            $value = '<span class="yaml-number">' . $value . '</span>';
                        } elseif (!empty($value)) {
                            $value = '<span class="yaml-string">' . $value . '</span>';
                        }
                        $line = $indent . '<span class="yaml-key">' . $key . '</span>:' . $afterColon . $value;
                    }
                    echo $line . "\n";
                }
            ?></pre>
        </div>
        <div class="footer">
            Copyright © 2024 TipTop Transfer. All rights reserved.
        </div>
    </div>
</body>
</html>
