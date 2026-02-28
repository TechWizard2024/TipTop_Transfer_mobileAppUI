<?php
/**
 * Script to dynamically list all files from storage directory
 * Sorted by file extension
 */

// Folders to exclude from the file listing
$excludedFolders = ['screenshot'];

// Files to exclude from the file listing  
$excludedFiles = ['screenshot.zip'];

function isPathExcluded($path, $excludedFolders, $excludedFiles) {
    $path = rtrim($path, '/');
    
    // Check if it's an excluded file
    foreach ($excludedFiles as $excludedFile) {
        if ($path === $excludedFile) {
            return true;
        }
    }
    
    // Check if it's in an excluded folder
    foreach ($excludedFolders as $excluded) {
        $excluded = rtrim($excluded, '/');
        if (strpos($path, $excluded) === 0) {
            $remaining = substr($path, strlen($excluded));
            if (empty($remaining) || $remaining[0] === '/') {
                return true;
            }
        }
    }
    return false;
}

function getDirectoryFiles($dir, $baseUrl = '', $excludedFolders = [], $excludedFiles = [], $storageBasePath = '') {
    $files = [];
    
    if (!is_dir($dir)) {
        return $files;
    }
    
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '.gitkeep') {
            continue;
        }
        
        $fullPath = $dir . '/' . $item;
        
        // Fix: Get relative path from web/storage/ directory
        $relativePath = str_replace($storageBasePath, '', $fullPath);
        
        if (isPathExcluded($relativePath, $excludedFolders, $excludedFiles)) {
            continue;
        }
        
        if (is_dir($fullPath)) {
            $subFiles = getDirectoryFiles($fullPath, $baseUrl, $excludedFolders, $excludedFiles, $storageBasePath);
            $files = array_merge($files, $subFiles);
        } else {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            
            // Skip temporary files
            if (strpos($item, '~$') === 0) {
                continue;
            }
            
            $files[] = [
                'name' => $item,
                'path' => $fullPath,
                'relative_path' => $relativePath,
                'extension' => $ext,
                'url' => $baseUrl . $relativePath
            ];
        }
    }
    
    return $files;
}

// Get all files from storage directory
$storageDir = __DIR__ . '/storage';
$storageBasePath = $storageDir . '/';
$allFiles = getDirectoryFiles($storageDir, 'storage/', $excludedFolders, $excludedFiles, $storageBasePath);

// Sort by extension, then by name
usort($allFiles, function($a, $b) {
    $extCompare = strcmp($a['extension'], $b['extension']);
    if ($extCompare !== 0) {
        return $extCompare;
    }
    return strcmp($a['name'], $b['name']);
});

// Group files by extension
$filesByExtension = [];
foreach ($allFiles as $file) {
    $ext = $file['extension'];
    if (!isset($filesByExtension[$ext])) {
        $filesByExtension[$ext] = [];
    }
    $filesByExtension[$ext][] = $file;
}

// Define viewer URL based on extension
function getViewerUrl($extension, $filePath) {
    $encodedPath = urlencode($filePath);
    
    switch ($extension) {
        case 'xlsx':
        case 'xls':
            return 'view_excel.php?file=' . $encodedPath;
        case 'docx':
            return 'view_word.php?file=' . $encodedPath;
        case 'yaml':
        case 'yml':
            return 'view_yaml.php?file=' . $encodedPath;
        case 'txt':
            return 'view_text.php?file=' . $encodedPath;
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'webp':
            return $encodedPath;
        case 'zip':
            return $encodedPath;
        default:
            return $encodedPath;
    }
}

// Define icon based on extension
function getFileIcon($extension) {
    $icons = [
        'xlsx' => '📊', 'xls' => '📊',
        'docx' => '📄', 'doc' => '📄',
        'yaml' => '⚙️', 'yml' => '⚙️',
        'txt' => '📝',
        'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️', 'webp' => '🖼️',
        'zip' => '📦', 'pdf' => '📕'
    ];
    return isset($icons[$extension]) ? $icons[$extension] : '📁';
}

// Define label for extension
function getExtensionLabel($extension) {
    $labels = [
        'xlsx' => 'Excel', 'xls' => 'Excel',
        'docx' => 'Word', 'doc' => 'Word',
        'yaml' => 'YAML', 'yml' => 'YAML',
        'txt' => 'Text',
        'jpg' => 'Image', 'jpeg' => 'Image', 'png' => 'Image', 'gif' => 'Image', 'webp' => 'Image',
        'zip' => 'Archive'
    ];
    return isset($labels[$extension]) ? $labels[$extension] : strtoupper($extension);
}
?>
