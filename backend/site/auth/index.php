<?php
header('Content-Type: application/json');

require_once('../common/auth.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'failed', 'msg' => 'The /auth endpoint is only available for POST requests']);
    exit;
}

$token_type = $_GET['token_type'] ?? '';

/*
 0  single_use       One-time token.
 1  single           Only one active token, no refresh key.
 2  refresh_main     Requires a Refresh:Refresh token to refresh.
 3  refresh_refresh  Used to refresh another token.
*/
$allowed_token_types = ['single'];
// $allowed_token_types = array_merge($allowed_token_types, ['single_use', 'refresh_main', 'refresh_refresh']); //MARK:TODO: implement single_use and refresh tokens

if (!in_array($token_type, $allowed_token_types)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'failed', 'msg' => 'Invalid token type']);
    exit;
}

$body_data = json_decode(file_get_contents('php://input'), true);

handle_auth_request($token_type, $body_data);