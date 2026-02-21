# Chemin du dossier principal
$basePath = "C:\xampp\htdocs\mobileAppUI\web\storage\screenshot"

# Fichier de sortie
$outputFile = "$basePath\list_ui.txt"

# Supprimer le fichier s'il existe déjà
if (Test-Path $outputFile) {
    Remove-Item $outputFile
}

# Parcourir chaque sous-dossier
Get-ChildItem -Path $basePath -Directory | ForEach-Object {

    $subFolderName = $_.Name

    # Parcourir les fichiers du sous-dossier
    Get-ChildItem -Path $_.FullName -File | ForEach-Object {

        $fileName = $_.Name

        # Format demandé
        "$subFolderName|$fileName" | Out-File -FilePath $outputFile -Append -Encoding UTF8
    }
}

Write-Host "Fichier généré : $outputFile"