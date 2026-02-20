<?php
// API endpoint to get list of screenshots
// Scans the web/storage/screenshot/ directory and returns JSON data

header('Content-Type: application/json');

// Base directory for screenshots
$baseDir = __DIR__ . '/../storage/screenshot/';

// Check if directory exists
if (!is_dir($baseDir)) {
    echo json_encode([
        'success' => false,
        'error' => 'Screenshot directory not found'
    ]);
    exit;
}

// Get list of folders
$folders = scandir($baseDir);

$imageData = [];

foreach ($folders as $folder) {
    // Skip . and .. and hidden files
    if ($folder === '.' || $folder === '..' || substr($folder, 0, 1) === '.') {
        continue;
    }
    
    $folderPath = $baseDir . $folder;
    
    // Only process directories
    if (!is_dir($folderPath)) {
        continue;
    }
    
    // Get list of images in this folder
    $images = [];
    $files = scandir($folderPath);
    
    foreach ($files as $file) {
        // Skip . and .. and hidden files
        if ($file === '.' || $file === '..' || substr($file, 0, 1) === '.') {
            continue;
        }
        
        // Only include image files
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'JPG', 'JPEG', 'PNG', 'GIF', 'WEBP'];
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        
        if (in_array($extension, $allowedExtensions)) {
            $images[] = $file;
        }
    }
    
    // Sort images alphabetically
    sort($images);
    
    $imageData[] = [
        'folder' => $folder,
        'images' => $images
    ];
}

// Sort folders alphabetically
usort($imageData, function($a, $b) {
    return strcmp($a['folder'], $b['folder']);
});

// Return JSON response
echo json_encode([
    'success' => true,
    'data' => $imageData
]);
