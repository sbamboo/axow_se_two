<?php
header('Content-Type: application/json');

require_once('../../_php_common_/auth.php');
require_once('../../_php_common_/request.php');

/*
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'failed', 'msg' => 'The /auth/validate endpoint is only available for GET requests']);
    exit;
}
*/

// Validate token
$decoded = req_auto_validate_token();
if ($decoded === false) { exit; }

// Token is valid
http_response_code(200);
echo json_encode(['status' => 'success', 'msg' => 'Token is valid', 'valid' => true]);
?>
