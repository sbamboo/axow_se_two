<?php
header("Content-Type: application/json; charset=utf-8");

require_once(__DIR__ . "/../../_php_common_/env.php");
require_once(__DIR__ . "/../../_php_common_/responders.php");

req_require_method("GET");

if (!isset($req_data["article"]) && is_string($req_data["article"])) {
    req_send(false, "Missing 'article' parameter", 400); // HTTP code 400 : Bad Request
}
$article = $article = $req_data["article"];
