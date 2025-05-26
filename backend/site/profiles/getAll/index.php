<?php
header("Content-Type: application/json; charset=utf-8");

require_once("../../_php_common_/env.php");
require_once("../../_php_common_/responders.php");
require_once("../../_php_common_/data_nodes.php");

req_require_method("GET");

// List all files in the "profiles" data node under the "data" directory
list($profiles, $msg) = list_data_node_files("profiles", "data");
if ($profiles === null) {
    req_send(false, $msg, 500); // HTTP code 500 : Internal Server Error
}

// Filter for .json / .jsonc / .json5 files only
$filtered_profiles = array_filter($profiles, function($file) {
    return preg_match('/\.(json|jsonc|json5)$/', $file);
});

// Return the name of each profile file prepended with the "@" prefix
$profile_names = array_map(function($file) {
    return "@" . basename($file, pathinfo($file, PATHINFO_EXTENSION));
}, $filtered_profiles);

// Respond with the list of profile names
req_send(true, "Successfully retrieved profile names", 200, ["profiles" => $profile_names]); // HTTP code 200 : OK
