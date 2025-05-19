<?php
header("Content-Type: application/json");

require_once("./_php_common_/env.php");
require_once("./_php_common_/responders.php");

req_send(true, "This API is in early developement, but visit www.axow.se!", 200); // HTTP code 200 : OK
?>
