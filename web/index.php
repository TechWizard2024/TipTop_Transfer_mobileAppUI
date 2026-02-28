<?php
// TipTop Transfer - Image Gallery
// This file uses PHP to dynamically list screenshots and files
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
<title>TipTop Transfer - Image Gallery</title>
<link rel="stylesheet" href="ressources/css/style.css">
<link rel="stylesheet" href="ressources/css/file-grid.css">
</head>

<body>

<div class="wrapper">

<div class="header">
        <div class="title">TipTop Transfer</div>
        <div class="subtitle">Image Gallery</div>
        <div class="download-link">
            <?php
            include 'list_files.php';
            ?>
            <div class="file-grid">
            <?php
            // Add a section for view files
            echo '<div class="file-section">';
            echo '<h3>🔧 Viewers</h3>';
            echo '<ul class="file-list">';
            echo '<li><a href="views/view_excel.php">Excel Viewer</a></li>';
            echo '<li><a href="views/view_word.php">Word Viewer</a></li>';
            echo '<li><a href="views/view_yaml.php">YAML Viewer</a></li>';
            echo '<li><a href="views/view_text.php">Text Viewer</a></li>';
            echo '</ul>';
            echo '</div>';
            
            foreach ($filesByExtension as $extension => $files) {
                $label = getExtensionLabel($extension);
                echo '<div class="file-section">';
                echo '<h3>' . getFileIcon($extension) . ' ' . $label . ' Files</h3>';
                echo '<ul class="file-list">';
                foreach ($files as $file) {
                    $viewerUrl = getViewerUrl($extension, $file['relative_path']);
                    $fileName = htmlspecialchars($file['name']);
                    echo '<li><a href="' . $viewerUrl . '">' . $fileName . '</a></li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            ?>
            <br>
            <a href="feuil4-unified_build.php">View Unified Build</a>
        </div>
    </div>

    <div class="topbar"></div>

    <!-- Gallery -->
    <div class="gallery-container" id="gallery">
        <div class="no-images">Loading images...</div>
    </div>

    <div class="footer">
        Copyright © 2024 TipTop Transfer. All rights reserved.
    </div>

</div>

<!-- Lightbox Modal -->
<div id="lightbox" class="lightbox">
    <span class="lightbox-close">&times;</span>
    <img id="lightbox-img" src="" alt="">
    <div id="lightbox-caption" class="lightbox-caption"></div>
</div>

<script src="ressources/js/script.js"></script>

</body>
</html>
