$viewsDir = Join-Path $PSScriptRoot "..\src\views"
$files = Get-ChildItem -Path $viewsDir -Filter "*.php" -Recurse

foreach ($file in $files) {
    $original = Get-Content -Path $file.FullName -Raw
    $content = $original

    # We want to find: <?= app_url('...<?= ... ?>...') ?>
    # and replace with: <?= app_url() ?>?...<?= ... ?>...
    
    # In PowerShell, regex is: <\?= app_url\('(.*?)'\) \?>
    # But wait, earlier I wrote `app_url(''$1'')` which literally inserted app_url('something')
    # So the string in the file is literally: <?= app_url('something') ?>
    
    $content = [regex]::Replace($content, '<\?= app_url\(''(.*?)''\) \?>', '<?= app_url() ?>?$1')

    if ($content -cne $original) {
        Set-Content -Path $file.FullName -Value $content -NoNewline
        Write-Host "Fixed: $($file.Name)"
    }
}
Write-Host "Done fixing."
