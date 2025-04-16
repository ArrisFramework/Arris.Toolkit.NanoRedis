<?php

declare(strict_types=1);

echo "\n";

if (!is_file(__DIR__.'/../config.php') || !is_readable(__DIR__.'/../config.php')) {
    echo 'Missing config.php, setting Travis default redis connection parameters';
    include __DIR__.'/../config.example.php';
} else {
    include __DIR__.'/../config.php';
}

echo 'Please wait... These tests will spend less than one minute...';

echo "\n";
echo "\n";
