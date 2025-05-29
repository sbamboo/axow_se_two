<?php
header("Content-Type: application/json; charset=utf-8");

require_once(__DIR__ . "/../../_php_common_/env.php");
require_once(__DIR__ . "/../../_php_common_/requests.php");
require_once(__DIR__ . "/../../_php_common_/responders.php");
require_once(__DIR__ . "/../../_php_common_/data_nodes.php");

req_require_method("GET");

$req_data = get_request_body();

// Read node config
list($node_config, $node_msg) = get_node_config("chibits");

if ($node_config === null) {
    req_send(false, "Failed to read node config: " . $node_msg, 500); // HTTP code 500 : Internal Server Error
}

// Check if $node_config["repos"]
if (!isset($node_config["repos"])) {
    req_send(false, "No repositories configured", 500); // HTTP code 500 : Internal Server Error
}

// Return a list of all the keys in $node_config["repos"]
$repos = array_keys($node_config["repos"]);
$mapped_repos = [];
// For each key map it to {"version": "v1"/"v2", "categories": ["<category>",...]} (For categories fetch the keys of the value in $node_config["repos"][$repo]["repo"]; then if v2 get keys else return the $node_config["repos"][$repo]["default_category"])
foreach ($repos as $repo) {
    if (!isset($node_config["repos"][$repo]["version"])) {
        req_send(false, "Repository " . $repo . " does not have a version defined", 500); // HTTP code 500 : Internal Server Error
    }
    
    $version = $node_config["repos"][$repo]["version"];
    $categories = [];

    if ($version === "v2") {
        // If v2, get the categories from the repo data
        // If "repo" field begins with "file://", strip it and use read_data_node_json_file, else use get_json_url
        if (strpos($node_config["repos"][$repo]["repo"], "file://") === 0) {
            // Remove "file://" prefix
            $file_path = substr($node_config["repos"][$repo]["repo"], 7);
            // Read the file as a JSON
            list($repo_data, $error) = read_data_node_json_file("chibits", $file_path);
            if ($repo_data === null) {
                req_send(false, "Failed to read repo data from file: " . $error, 500); // HTTP code 500 : Internal Server Error
            }
        } else {
            // Fetch the repo data from the URL
            list($repo_data, $error) = get_json_url($node_config["repos"][$repo]["repo"]);
            if ($repo_data === null) {
                req_send(false, "Failed to fetch repo data: " . $error, 500); // HTTP code 500 : Internal Server Error
            }
        }
        // Now list the keys of the repo data as categories
        if (!is_array($repo_data)) {
            req_send(false, "Invalid repo data format for repository " . $repo, 500); // HTTP code 500 : Internal Server Error
        }
        $categories = array_keys($repo_data);
    } else {
        // If v1, just return the default category
        $categories[] = $node_config["repos"][$repo]["default_category"];
    }

    $mapped_repos[$repo] = [
        "version" => $version,
        "categories" => $categories
    ];
}

req_send(true, "", 200, array(
    "repos" => $mapped_repos
));