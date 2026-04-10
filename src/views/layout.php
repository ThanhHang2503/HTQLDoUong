<?php

if (!function_exists('renderAppLayoutStart')) {
    function renderAppLayoutStart(string $user_name): void
    {
        $GLOBALS['user_name'] = $user_name;
        require __DIR__ . '/header.php';
    }
}

if (!function_exists('renderAppLayoutEnd')) {
    function renderAppLayoutEnd(): void
    {
        require __DIR__ . '/footer.php';
        echo "</body>\n</html>";
    }
}
