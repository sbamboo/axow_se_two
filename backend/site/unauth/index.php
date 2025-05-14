<?php
header('Content-Type: application/json');

require_once('../_php_common_/auth.php');
require_once('../_php_common_/request.php');

/*
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'failed', 'msg' => 'The /unauth endpoint is only available for POST requests']);
    exit;
}
*/

list('type' => $type, 'token' => $token) = get_auth_header_token();

// Use the invalidate_token function from auth.php
req_invalidate_token($token);
?>
