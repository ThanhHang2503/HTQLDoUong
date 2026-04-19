<?php
$file = 'src/views/bangluong.php';
$content = file_get_contents($file);

// Add data-status to option
$content = str_replace(
    'data-days="<?= $emp[\'working_days\'] ?>">',
    'data-days="<?= $emp[\'working_days\'] ?>" data-status="<?= $emp[\'salary_status\'] ?>">',
    $content
);

// Add ID to notes input
$content = str_replace(
    'name="notes" placeholder="Ghi chú lương tháng này...">',
    'name="notes" id="notesInput" placeholder="Ghi chú lương tháng này...">',
    $content
);

file_put_contents($file, $content);
echo "File updated successfully.\n";
