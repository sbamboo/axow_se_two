<?php
header("Content-Type: application/json; charset=utf-8");

require_once(__DIR__ . "/../../_php_common_/env.php");
require_once(__DIR__ . "/../../_php_common_/responders.php");
require_once(__DIR__ . "/../../_php_common_/data_nodes.php");

req_require_method("GET");

$req_data = get_request_body();

