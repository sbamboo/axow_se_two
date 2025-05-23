<?php
header("Content-Type: application/json; charset=utf-8");

require_once("../../_php_common_/env.php");
require_once("../../_php_common_/responders.php");
require_once("../../_php_common_/requests.php");
require_once("../../_php_common_/data_nodes.php");

req_require_method("GET");

list($data, $msg) = read_data_node_file("projects", "axo77_server/data.jsonc");

if ($data === null) {
    req_send($false, $msg, 500);
} else {
    http_response_code(200);
    echo format_json_response($data);
    die(); //MARK: Should we exit instead?
}