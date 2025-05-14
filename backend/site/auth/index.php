<?php
header('Content-Type: application/json');

require_once('../_php_common_/auth.php');
require_once('../_php_common_/request.php');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'failed', 'msg' => 'The /auth endpoint is only available for GET requests']);
    exit;
}

$token_type = $_GET['token_type'] ?? '';

/*
 0  single-use  One-time token.
 1  single      Only one active token, no refresh key.
 2  pair        Requires a refresh token to refresh.
 3  refresh     Used to refresh a pair token.
*/
$allowed_token_types = ['single', 'single-use'];
// $allowed_token_types = array_merge($allowed_token_types, ['pair']); //MARK:TODO: implement pair tokens

if (!in_array($token_type, $allowed_token_types)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'failed', 'msg' => 'Invalid token type']);
    exit;
}

// assemble body data by GET["username"] and GET["password"]
if ($_REQUEST === null || !isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'failed', 'msg' => 'Invalid request']);
    exit;
}
$body_data = [
    'username' => $_REQUEST['username'],
    'password' => $_REQUEST['password'],
];

req_get_new_token($token_type, $body_data);