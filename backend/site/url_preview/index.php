<?php
//header("Content-Type: application/json; charset=utf-8");
header("Content-Type: application/json; charset=utf-8");

require_once("../_php_common_/env.php");
require_once("../_php_common_/requests.php");
require_once("../_php_common_/responders.php");
require_once("../_php_common_/url_preview/fetch.php");

req_require_one_of_methods(["GET", "POST"]);

$req_data = get_request_body();

$decoded_token = req_require_token();

req_require_permission("url-preview.fetch", $decoded_token, $decoded_token["usr"]);

req_fetch_url_preview($req_data);