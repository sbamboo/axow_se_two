<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'failed', 'msg' => 'The /auth/refresh endpoint is only available for POST requests']);
    exit;
}

http_response_code(501); // Not Implemented
echo json_encode(['status' => 'failed', 'msg' => 'This endpoint is not implemented yet']);
