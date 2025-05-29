<?php
header("Content-Type: application/json; charset=utf-8");

require_once(__DIR__ . "/../../../_php_common_/env.php");
require_once(__DIR__ . "/../../../_php_common_/responders.php");

req_require_method("POST");

$decoded_token = req_require_token();

req_require_permission("wiki-subcat.remove", $decoded_token, $decoded_token["usr"]);