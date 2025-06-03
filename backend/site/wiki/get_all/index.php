<?php
header("Content-Type: application/json; charset=utf-8");

require_once(__DIR__ . "/../../_php_common_/env.php");
require_once(__DIR__ . "/../../_php_common_/responders.php");
require_once(__DIR__ . "/../../_php_common_/requests.php");
require_once(__DIR__ . "/../../_php_common_/data_nodes.php");

req_require_method("GET");

$req_data = get_request_body();

list($data, $msg) = get_node_data("wiki");
if ($data === null) {
    req_send(false, "Failed to retrieve wiki data: $msg", 500);  // HTTP code 500 : Internal Server Error
}