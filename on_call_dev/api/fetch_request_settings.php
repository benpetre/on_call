<?php
require_once "../core/auth.php";
require_login();
require_once "../core/db.php";
require_once "../core/settings.php";

header('Content-Type: application/json');

$settings = [
    'request_open_date'          => onecall_get_setting('request_open_date'),
    'request_close_date'         => onecall_get_setting('request_close_date'),
    'block_start_date'           => onecall_get_setting('block_start_date'),
    'block_end_date'             => onecall_get_setting('block_end_date'),
    'max_requests_per_block'     => (int) onecall_get_setting('max_requests_per_block', 30),
    'max_weekend_requests_block' => (int) onecall_get_setting('max_weekend_requests_block', 12),
];

echo json_encode($settings);
