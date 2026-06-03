<?php
header('Content-Type: application/json');
echo json_encode([
    'headers' => getallheaders(),
    'authorization' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
]);
