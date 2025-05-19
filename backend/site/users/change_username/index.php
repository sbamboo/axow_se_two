<?php
header("Content-Type: application/json");

require_once("../../_php_common_/env.php");
require_once("../../_php_common_/responders.php");

req_require_method("POST");

$decoded_token = req_require_token();

req_change_username($decoded_token["usr"]);
?>
