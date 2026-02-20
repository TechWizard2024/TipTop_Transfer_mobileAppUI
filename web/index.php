<?php
// TipTop Transfer - Image Gallery
// This file uses PHP to dynamically list screenshots
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
<title>TipTop Transfer - Image Gallery</title>
<link rel="stylesheet" href="ressources/css/style.css">
</head>

<body>

<div class="wrapper">

<div class="header">
        <div class="title">TipTop Transfer</div>
        <div class="subtitle">Image Gallery</div>
        <div class="download-link">
            <a href="storage/excel/App_Wave_UI_List.xlsx" download>Download UI List (Excel)</a>
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
