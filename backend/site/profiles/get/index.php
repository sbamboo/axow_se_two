<?php
header("Content-Type: application/json; charset=utf-8");

require_once("../../_php_common_/env.php");
require_once("../../_php_common_/requests.php");
require_once("../../_php_common_/responders.php");
require_once("../../_php_common_/data_nodes.php");

req_require_method("GET");

$req_data = get_request_body();

if (!isset($req_data["id"])) {
    req_send(false, "Missing required 'id' parameter", 400); // HTTP code 400 : Bad Request
}

// Check if the profile exists under the "profiles" data node
$file = "data/" . $req_data["id"] . ".jsonc";
$exists = check_data_node_file_exists("profiles", $file);
if (!$exists) {
    $file = "data/" . $req_data["id"] . ".json";
    $exists = check_data_node_file_exists("profiles", $file);
    if (!$exists) {
        req_send(false, "Profile '" . $req_data["id"] . "' does not exist", 404); // HTTP code 404 : Not Found
    }
}

// Read the profile data from the "profiles" data node
list($profile_data, $msg) = read_data_node_json_file("profiles", $file);
if ($profile_data === null) {
    req_send(false, "Failed to read profile data: " . $msg, 500); // HTTP code 500 : Internal Server Error
}

// Return the data
req_send(true, "Successfully retrieved profile data", 200, ["profile" => $profile_data]); // HTTP code 200 : OK