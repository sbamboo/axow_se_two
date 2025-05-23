<?php
header("Content-Type: application/json; charset=utf-8");

require_once("../_php_common_/env.php");
require_once("../_php_common_/requests.php");
require_once("../_php_common_/responders.php");
require_once("../_php_common_/data_nodes.php");

req_require_method("GET");

$req_data = get_request_body();

$repo = isset($req_data["repo"]) ? $req_data["repo"] : null;
$category = isset($req_data["category"]) ? $req_data["category"] : null;
$include_entries = isset($req_data["include_entries"]) ? $req_data["include_entries"] : false;

// Require repo
if ($repo === null) {
    req_send(false, "Missing repo", 400);// HTTP code 400 : Internal Server Error
}

// Read node config
list($node_config, $node_msg) = get_node_config("chibits");

if ($node_config === null) {
    req_send(false, "Failed to read node config: " . $node_msg, 500); // HTTP code 500 : Internal Server Error
}

// Check if repo is key under $node_config["repos"]
if (!isset($node_config["repos"][$repo])) {
    req_send(false, "Invalid repository " . $repo, 400); // HTTP code 400 : Bad Request
}

// Fetch the repo from $node_config["repos"][$repo]["repo"] using get_json_url
list($repo_data, $error) = get_json_url($node_config["repos"][$repo]["repo"]);
if ($repo_data === null) {
    req_send(false, "Failed to fetch repo data: " . $error, 500); // HTTP code 500 : Internal Server Error
}

// $repo_data on $node_config["repos"][$repo]["version"] == "v1" is in the format {"<chibit_uuid>" : "<chibit_json_entry_url>"}
// $repo_data on $node_config["repos"][$repo]["version"] == "v2" is in the format {"<category>": {"<chibit_uuid>" : "url": "<chibit_json_entry_url>"}}

if ($node_config["repos"][$repo]["version"] == "v2") {
    $category = $category ?? $node_config["repos"][$repo]["default_category"];

    if (!isset($repo_data[$category])) {
        if ($category === $node_config["repos"][$repo]["default_category"]) {
            req_send(false, "Invalid given category and the default category was not found", 400); // HTTP code 400 : Bad Request
        } else {
            req_send(false, "Invalid category " . $category, 400); // HTTP code 400 : Bad Request
        }
    }

    $repo_data = $repo_data[$category];
}

// If $include_entries is true, fetch the data for each chibit in the repo_data else just return the repo_data
if ($include_entries === false) {
    http_response_code(200);
    echo format_json_response($repo_data);
    die(); //MARK: Should we exit instead?   
} else {
    $chibits = [];
    foreach ($repo_data as $chibit_uuid => $chibit_json_entry_url) {
        list($chibit_data, $error) = get_json_url($chibit_json_entry_url);
        if ($chibit_data === null) {
            req_send(false, "Failed to fetch chibit data: " . $error, 500); // HTTP code 500 : Internal Server Error
        }
        $chibits[$chibit_uuid] = $chibit_data;
    }

    http_response_code(200);
    echo format_json_response($chibits);
    die(); //MARK: Should we exit instead?
}