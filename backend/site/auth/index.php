<?php
header("Content-Type: application/json; charset=utf-8");

require_once(__DIR__ . "/../_php_common_/env.php");
require_once(__DIR__ . "/../_php_common_/responders.php");

req_require_method("GET");

$token_type = $_REQUEST["token_type"] ?? "";

/*
 0  single-use  One-time token.
 1  single      Only one active token, no refresh key.
 2  pair        Requires a refresh token to refresh.
 3  refresh     Used to refresh a pair token.
*/
$allowed_token_types = ["single", "single-use", "pair"];

if (!in_array($token_type, $allowed_token_types)) {
    req_send(false, "Invalid token type", 401); // HTTP code 401 : Unauthorized
}

req_get_new_token($token_type, $_REQUEST);