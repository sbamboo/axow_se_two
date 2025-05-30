<?php
header("Content-Type: application/json; charset=utf-8");

require_once(__DIR__ . "/../../_php_common_/env.php");
require_once(__DIR__ . "/../../_php_common_/requests.php");
require_once(__DIR__ . "/../../_php_common_/responders.php");
require_once(__DIR__ . "/../../_php_common_/data_nodes.php");

req_require_method("GET");

$req_data = get_request_body();

if (!isset($req_data["id"])) {
    req_send(false, "Missing required 'id' parameter", 400); // HTTP code 400 : Bad Request
}

// profile_id replace "@" with "" to get the actual profile ID
$profile_id = str_replace("@", "", $req_data["id"]);

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
    req_send(false, "Failed to read profile data: " . $msg, 500); // HTTP code 500 : Internal Server Error
}

// If $profile_data["image"] is set and is not a URL, check if the file exists as <data_node>, <profile_id>/<image>
if (isset($profile_data["image"]) && !filter_var($profile_data["image"], FILTER_VALIDATE_URL)) {
    $exists = check_data_node_file_exists("profiles", "data/" . $profile_id . "/" . $profile_data["image"]);
    if ($exists) {
        // Load the file and set the value to base64
        list($image, $msg) = read_data_node_file("profiles", "data/" . $profile_id . "/" . $profile_data["image"]);
        if ($image === null) {
            req_send(false, "Failed to read profile image: " . $msg, 500); // HTTP code 500 : Internal Server Error
        }
        $profile_data["image"] = "data:image/png;base64," . base64_encode($image);
    }
}

// Return the data
req_send(true, "Successfully retrieved profile data", 200, ["profile" => $profile_data]); // HTTP code 200 : OK