<?php
header("Content-Type: application/json");

require_once("../../_php_common_/env.php");
require_once("../../_php_common_/responders.php");

//req_require_method("GET");

req_require_token();

req_send(true, "Token is valid", 200); // HTTP code 200 : OK
