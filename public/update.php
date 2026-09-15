<?php

if (!isset($_GET['secret']) || $_GET['secret'] !== getenv('DEPLOY_SECRET')) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

// Clear cache
array_map('unlink', glob(__DIR__ . '/../cache/*'));

// Log update completion
file_put_contents(__DIR__ . '/../deploy.log', date('Y-m-d H:i:s') . " - Update completed\n", FILE_APPEND);

echo 'Update completed';