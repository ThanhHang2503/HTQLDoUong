$viewsDir = Join-Path $PSScriptRoot "..\src\views"
$files = Get-ChildItem -Path $viewsDir -Filter "*.php" -Recurse
$files += Get-ChildItem -Path (Join-Path $PSScriptRoot "..") -Filter "user_page.php"

foreach ($file in $files) {
    $original = Get-Content -Path $file.FullName -Raw
    $content = $original

    # HTML href/action with query string
    $content = $content -replace '(href|action)="user_page\.php\?([^"]+)"', '$1="<?= app_url(''$2'') ?>"'
    
    # HTML href/action without query string
    $content = $content -replace '(href|action)="user_page\.php"', '$1="<?= app_url() ?>"'

    # JS fetch with query string
    $content = $content -replace 'fetch\([''"]user_page\.php\?([^''"]+)[''"]', 'fetch(''<?= app_url(''$1'') ?>'''
    
    # JS fetch without query string
    $content = $content -replace 'fetch\([''"]user_page\.php[''"]', 'fetch(''<?= app_url() ?>'''

    # JS window.location with query string
    $content = $content -replace 'window\.location\.href\s*=\s*[''"]user_page\.php\?([^''"]+)[''"]', 'window.location.href = "<?= app_url(''$1'') ?>"'
    
    # JS window.location without query string
    $content = $content -replace 'window\.location\.href\s*=\s*[''"]user_page\.php[''"]', 'window.location.href = "<?= app_url() ?>"'

    # PHP header redirect with query string
    $content = $content -replace 'header\(\s*[''"]location:\s*user_page\.php\?([^''"]+)[''"]\s*\)', 'header("location:" . app_url(''$1''))'
    
    # PHP header redirect without query string
    $content = $content -replace 'header\(\s*[''"]location:\s*user_page\.php[''"]\s*\)', 'header("location:" . app_url())'

    # Remove $baseUrl for user_page
    $content = $content -replace '<\?=\s*\$baseUrl\s*\?>\s*<\?=\s*app_url', '<?= app_url'

    # Hardcoded edge cases in luong_ca_nhan
    $content = $content -replace 'window\.opener\.location\.href = "user_page\.php\?luong_ca_nhan&year='' \. \$selected_year \. ''&month='' \. \$selected_month \. ''&print_success=1"', 'window.opener.location.href = "<?= app_url(''luong_ca_nhan&year='' . $selected_year . ''&month='' . $selected_month . ''&print_success=1'') ?>"'
    $content = $content -replace 'window\.location\.href = "user_page\.php\?luong_ca_nhan&year='' \. \$selected_year \. ''&month='' \. \$selected_month \. ''&print_success=1"', 'window.location.href = "<?= app_url(''luong_ca_nhan&year='' . $selected_year . ''&month='' . $selected_month . ''&print_success=1'') ?>"'

    # Fix user_page.php internal forms/fetch
    $content = $content -replace '''user_page\.php\?([^'']+)''', 'app_url(''$1'')'
    $content = $content -replace '"user_page\.php\?([^"]+)"', 'app_url(''$1'')'

    if ($content -cne $original) {
        Set-Content -Path $file.FullName -Value $content -NoNewline
        Write-Host "Updated: $($file.Name)"
    }
}
Write-Host "Done refactoring."
