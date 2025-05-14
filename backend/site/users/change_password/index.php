<?php
header('Content-Type: application/json');

require_once('../../_php_common_/auth.php');
require_once('../../_php_common_/user.php');
require_once('../../_php_common_/request.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'failed', 'msg' => 'The /users/change_password endpoint is only available for POST requests']);
    exit;
}

// Validate token
$decoded = req_auto_validate_token();
if ($decoded === false) { exit; }

auto_invalidate_single_use_token();

// body of {"new_password": "<string>", "old_password": "<string>"}

$body_data = get_post_data();

if ($body_data === null || !isset($body_data['new_password'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'failed', 'msg' => 'Invalid request']);
    exit;
}

if (!isset($body_data['old_password'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'failed', 'msg' => 'Old password is required']);
    exit;
}

// Check if the new password is not empty,null or the same as the current password `get_current_password($decoded["usr"])`
if (empty($body_data['new_password']) || $body_data['new_password'] === get_current_password($decoded["usr"])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'failed', 'msg' => 'Invalid new password']);
    exit;
}

// Calculate hash for the old password and match it with the current password hash
$incomming_old_password_hash = get_secure_hash($body_data['old_password']);
$current_password_hash = get_current_password($decoded["usr"]);

if ($incomming_old_password_hash !== $current_password_hash) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'failed', 'msg' => 'Incorrect old password']);
    exit;
}

$success = change_password($decoded["usr"], $body_data['new_password']);

if ($success) {
    http_response_code(200); // OK
    echo json_encode(['status' => 'success', 'msg' => 'Password changed successfully']);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'failed', 'msg' => 'Failed to change password']);
}

?>
