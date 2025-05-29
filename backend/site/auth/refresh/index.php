<?php
header("Content-Type: application/json; charset=utf-8");

require_once(__DIR__ . "/../../_php_common_/env.php");
require_once(__DIR__ . "/../../_php_common_/responders.php");
require_once(__DIR__ . "/../../_php_common_/requests.php");

req_require_method("POST");

//req_send(false, "This endpoint is not implemented yet", 501); // HTTP code 501 : Not Implemented

// Get `refresh_token` using `get_post_body()`
$body_data = get_post_body();
if ($body_data === null || !isset($body_data["refresh_token"])) {
    req_send(false, "Invalid request", 400); // HTTP code 400 : Bad Request
}

req_refresh_pair_token($body_data["refresh_token"]);