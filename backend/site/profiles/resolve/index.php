<?php
header("Content-Type: application/json; charset=utf-8");

require_once(__DIR__ . "/../../_php_common_/env.php");
require_once(__DIR__ . "/../../_php_common_/responders.php");
require_once(__DIR__ . "/../../_php_common_/data_nodes.php");

req_require_method("GET");

$req_data = get_request_body();

// "ids" parameter exploded by comma
if (!isset($req_data["ids"])) {
    req_send(false, "Missing required 'ids' parameter", 400); // HTTP code 400 : Bad Request
}
$profiles = isset($req_data["ids"]) ? explode(",", $req_data["ids"]) : [];

// List all files in the "profiles" data node under the "data" directory
list($profiles, $msg) = list_data_node_files("profiles", "data");
if ($profiles === null) {
    req_send(false, $msg, 500); // HTTP code 500 : Internal Server Error
}

$found_names = [];

// Foreach profile check if it exists and read the data to get the "name" field, if not exists or error, error
foreach ($profiles as $profile) {
    // profile_id replace "@" with "" to get the actual profile ID
    $profile_id = str_replace("@", "", $profile);
    $profile_atid = "@" . $profile_id; // Keep the "@" prefix for the response
    
    // Check if the profile exists under the "profiles" data node
    $file = "data/" . $profile_id . "/profile.jsonc";
    $exists = check_data_node_file_exists("profiles", $file);
    if (!$exists) {
        $file = "data/" . $profile_id . "/profile.json";
        $exists = check_data_node_file_exists("profiles", $file);
        if (!$exists) {
            req_send(false, "Profile '" . $profile_id . "' does not exist", 404); // HTTP code 404 : Not Found
        }
    }
    
    // Read the profile data from the "profiles" data node
    list($profile_data, $msg) = read_data_node_json_file("profiles", $file);
    if ($profile_data === null) {
        req_send(false, "Failed to read profile data for '" . $profile_id . "': " . $msg, 500); // HTTP code 500 : Internal Server Error
    }
    
    // Add the name to the found names array
    if (isset($profile_data["name"])) {
        $found_names[$profile_atid] = $profile_data["name"];
    } else {
        req_send(false, "Failed to read profile data for '" . $profile_id . "': " . $msg, 500); // HTTP code 500 : Internal Server Error
    }
}

req_send(true, "Successfully retrieved profile names", 200, ["profiles" => $found_names]); // HTTP code 200 : OK