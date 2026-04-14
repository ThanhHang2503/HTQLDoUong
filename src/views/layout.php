<?php

if (!function_exists('renderAppLayoutStart')) {
    function renderAppLayoutStart(string $user_name, string $role = ''): void
    {
        $GLOBALS['user_name'] = $user_name;
        if ($role === 'admin') {
            require __DIR__ . '/layouts/admin_layout_start.php';
        } else {
            require __DIR__ . '/layouts/user_layout_start.php';
        }
    }
}

if (!function_exists('renderAppLayoutEnd')) {
    function renderAppLayoutEnd(string $role = ''): void
    {
        if ($role === 'admin') {
            require __DIR__ . '/layouts/admin_layout_end.php';
        } else {
            require __DIR__ . '/layouts/user_layout_end.php';
        }
    }
}
