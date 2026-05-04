$files = "profile.php", "nhansu.php", "khachhang.php", "chucvu.php", "baocao_kinhdoanh.php", "baocao_kho.php"
foreach ($f in $files) {
    $path = "src\views\$f"
    if (Test-Path $path) {
        $content = Get-Content $path -Raw
        $original = $content
        
        # Replace the broken <?= $baseUrl ?>api/ with <?= getBaseUrl() ?>/api/
        $content = [regex]::Replace($content, '<\?= \$baseUrl \?>api/', '<?= getBaseUrl() ?>/api/')
        
        if ($content -cne $original) {
            Set-Content -Path $path -Value $content -NoNewline
            Write-Host "Fixed: $f"
        }
    }
}
Write-Host "Done fixing getBaseUrl."
