<?php
header("Content-Type: application/json");

require_once("../_php_common_/env.php");
require_once("../_php_common_/requests.php");
require_once("../_php_common_/responders.php");
require_once("../_php_common_/url_preview/fetch.php");

$req_data = get_request_body();

req_require_token();

req_fetch_url_preview($req_data);