<?php
$directories = [
    __DIR__ . '/uploads',
    __DIR__ . '/uploads/payments'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
        echo "Created directory: $dir\n";
    } else {
        echo "Directory already exists: $dir\n";
    }
}
?>
