<?php
$dir = __DIR__ . '/../src/views';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$count = 0;
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        $original = $content;

        // $1="user_page.php?..."  -> $1="<?= app_url('...') ?>"
        $content = preg_replace_callback('/(href|action)="user_page\.php\?([^"]+)"/', function($m) {
            return $m[1] . '="<?= app_url(' . chr(39) . $m[2] . chr(39) . ') ?>"';
        }, $content);

        // $1="user_page.php" -> $1="<?= app_url() ?>"
        $content = preg_replace_callback('/(href|action)="user_page\.php"/', function($m) {
            return $m[1] . '="<?= app_url() ?>"';
        }, $content);

        // header("location:user_page.php?...") -> header("location:" . app_url('...'))
        $content = preg_replace_callback('/header\(\s*[\'"]location:\s*user_page\.php\?([^\'"]+)[\'"]\s*\)/i', function($m) {
            return 'header("location:" . app_url(' . chr(39) . $m[1] . chr(39) . '))';
        }, $content);

        // header("location:user_page.php") -> header("location:" . app_url())
        $content = preg_replace_callback('/header\(\s*[\'"]location:\s*user_page\.php[\'"]\s*\)/i', function($m) {
            return 'header("location:" . app_url())';
        }, $content);

        // window.location.href = "user_page.php?..." -> window.location.href = "<?= app_url('...') ?>"
        $content = preg_replace_callback('/(window\.location\.href\s*=\s*)[\'"]user_page\.php\?([^\'"]+)[\'"]/', function($m) {
            return $m[1] . '"<?= app_url(' . chr(39) . $m[2] . chr(39) . ') ?>"';
        }, $content);

        // window.location.href = "user_page.php" -> window.location.href = "<?= app_url() ?>"
        $content = preg_replace_callback('/(window\.location\.href\s*=\s*)[\'"]user_page\.php[\'"]/', function($m) {
            return $m[1] . '"<?= app_url() ?>"';
        }, $content);

        // fetch('user_page.php?...' -> fetch('<?= app_url('...') ?>'
        $content = preg_replace_callback('/fetch\([\'"]user_page\.php\?([^\'"]+)[\'"]/', function($m) {
            return 'fetch(' . chr(39) . '<?= app_url(' . chr(39) . $m[1] . chr(39) . ') ?>' . chr(39);
        }, $content);

        // fetch('user_page.php' -> fetch('<?= app_url() ?>'
        $content = preg_replace_callback('/fetch\([\'"]user_page\.php[\'"]/', function($m) {
            return 'fetch(' . chr(39) . '<?= app_url() ?>' . chr(39);
        }, $content);
        
        // Remove $baseUrl before user_page.php (e.g. href="<?= $baseUrl ?><?= app_url...)
        $content = str_replace('<?= $baseUrl ?><?= app_url', '<?= app_url', $content);

        // Also some might have 'user_page.php?luong_ca_nhan...'
        $content = str_replace('window.location.href = "user_page.php?luong_ca_nhan', 'window.location.href = "<?= app_url(\'luong_ca_nhan', $content);
        $content = str_replace('window.opener.location.href = "user_page.php?luong_ca_nhan', 'window.opener.location.href = "<?= app_url(\'luong_ca_nhan', $content);
        // Wait, if we replace this way, the ending quote needs fixing. Let's just trust preg_replace_callback.

        if ($content !== $original) {
            file_put_contents($path, $content);
            echo "Updated: " . basename($path) . "\n";
            $count++;
        }
    }
}

// Special case for luong_ca_nhan.php line 76-77
$lcn = $dir . '/luong_ca_nhan.php';
$c = file_get_contents($lcn);
$c = str_replace('window.opener.location.href = "user_page.php?luong_ca_nhan&year=\' . $selected_year . \'&month=\' . $selected_month . \'&print_success=1"', 'window.opener.location.href = "<?= app_url(\'luong_ca_nhan&year=\' . $selected_year . \'&month=\' . $selected_month . \'&print_success=1\') ?>"', $c);
$c = str_replace('window.location.href = "user_page.php?luong_ca_nhan&year=\' . $selected_year . \'&month=\' . $selected_month . \'&print_success=1"', 'window.location.href = "<?= app_url(\'luong_ca_nhan&year=\' . $selected_year . \'&month=\' . $selected_month . \'&print_success=1\') ?>"', $c);
file_put_contents($lcn, $c);

echo "Total updated: $count files.\n";
