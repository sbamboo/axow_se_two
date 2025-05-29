<?php
header("Content-Type: application/json; charset=utf-8");

require_once(__DIR__ . "/../_php_common_/env.php");
require_once(__DIR__ . "/../_php_common_/requests.php");
require_once(__DIR__ . "/../_php_common_/responders.php");
require_once(__DIR__ . "/../_php_common_/data_nodes.php");

req_require_method("GET");

$req_data = get_request_body();

$repo = isset($req_data["repo"]) ? $req_data["repo"] : null;
$category = isset($req_data["category"]) ? $req_data["category"] : null;
$include_entries = isset($req_data["include_entries"]) ? $req_data["include_entries"] : false;
$entry = isset($req_data["entry"]) ? $req_data["entry"] : null;

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

// Fetch the repo from $node_config["repos"][$repo]["repo"] using get_json_url unless it starts with "file://" in which we treat it as a relative path to the data node and use read_data_node_json_file
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
} else {
    if ($category !== null && $category !== $node_config["repos"][$repo]["default_category"]) {
        req_send(false, "Invalid category " . $category, 400); // HTTP code 400 : Bad Request
    }
    $repo_data = [$node_config["repos"][$repo]["default_category"] => $repo_data];
}

// Following if $includes_categories is true $repo_data is {"<category>": {"<chibit_uuid>" : "url": "<chibit_json_entry_url>"}} else {"<chibit_uuid>" : "<chibit_json_entry_url>"}

// If $entry is set, check if it exists as a key, if it does filter for only that entry, else return error
if ($entry !== null) {
    // Iterate each category and check if the entry exists in any of them
    $found = false;
    foreach ($repo_data as $cat => $chibits) {
        if (isset($chibits[$entry])) {
            // Filter so this category only contains the entry (keep the other categories)
            $repo_data[$cat] = [$entry => $chibits[$entry]];
            $found = true;
        }
    }
    if (!$found) {
        req_send(false, "Entry filter " . $entry . " not found in any category", 404); // HTTP code 404 : Not Found
    }
}

// If $include_entries is true, fetch the data for each chibit in the repo_data else just return the repo_data
if ($include_entries === false) {
    req_send(true, "", 200, ["repos" => [$repo => $repo_data]]); // HTTP code 200 : OK
} else {
    $chibits = [];
    foreach ($repo_data as $cat => $chibit_entries) {
        $chibits[$cat] = [];
        foreach ($chibit_entries as $chibit_uuid => $chibit_json_entry_url) {
            list($chibit_data, $error) = get_json_url($chibit_json_entry_url);
            if ($chibit_data === null) {
                req_send(false, "Failed to fetch chibit data: " . $error, 500); // HTTP code 500 : Internal Server Error
            }
            $chibits[$cat][$chibit_uuid] = $chibit_data;
        }
    }
    req_send(true, "", 200, ["repos" => [$repo => $chibits]]); // HTTP code 200 : OK
}