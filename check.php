<?php
echo "images folder exists: ";
var_dump(is_dir(__DIR__ . '/images'));
echo "<pre>";
print_r(glob(__DIR__ . '/images/*'));
echo "</pre>";