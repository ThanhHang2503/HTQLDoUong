$files = "profile.php", "nhansu.php", "khachhang.php", "chucvu.php", "baocao_kinhdoanh.php", "baocao_kho.php"
foreach ($f in $files) {
    $path = "src\views\$f"
    if (Test-Path $path) {
        $content = Get-Content $path -Raw
        $original = $content
        $content = [regex]::Replace($content, '([''"`])api/', '$1<?= $baseUrl ?>api/')
        if ($content -cne $original) {
            Set-Content -Path $path -Value $content -NoNewline
            Write-Host "Fixed: $f"
        }
    }
}
Write-Host "Done fixing APIs."
