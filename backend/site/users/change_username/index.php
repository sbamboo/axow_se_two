<?php
header('Content-Type: application/json');

require_once('../../_php_common_/auth.php');
require_once('../../_php_common_/user.php');
require_once('../../_php_common_/request.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'failed', 'msg' => 'The /users/change_username endpoint is only available for POST requests']);
    exit;
}

// Validate token
$decoded = req_auto_validate_token();
if ($decoded === false) { exit; }

auto_invalidate_single_use_token();

// body of {"new_username": "<string>"}

$body_data = get_post_data();

if ($body_data === null || !isset($body_data['new_username'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'failed', 'msg' => 'Invalid request']);
    exit;
}

// Check if the new username is not empty,null or the same as the current username `get_current_username($decoded["usr"])`
if (empty($body_data['new_username']) || $body_data['new_username'] === get_current_username($decoded["usr"])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'failed', 'msg' => 'Invalid new username']);
    exit;
}



$success = change_username($decoded["usr"], $body_data['new_username']);

if ($success) {
    http_response_code(200); // OK
    echo json_encode(['status' => 'success', 'msg' => 'Username changed successfully']);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'failed', 'msg' => 'Failed to change username']);
}

?>
