<?php
header('Content-Type: application/json');

require_once('../../_php_common_/env.php');
require_once('../../_php_common_/responders.php');

req_require_method("POST");

req_send(false, "This endpoint is not implemented yet", 501); // HTTP code 501 : Not Implemented
