<?php
/**
 * @file data_nodes.php
 * @brief This file contains functions to handle the data nodes.
 */

require_once("../_php_common_/secret_config.php");
require_once("../_php_common_/libs/php-json-comment.php");

// Function to normalize a path to "<api_root>/" for "./"
function normalize_path_to_api_root($path) {
    // If path begins with "./", replace it with the base path + "/"
    if (substr($path, 0, 2) === "./") {
        // Get the path of this PHP script
        //   ".../backend/site/_php_common_/data_nodes.php"
        $currentScriptPath = realpath(__FILE__);
    
        // Traverse up one directory to get the base path of the api
        //   ".../backend/site/"
        $basePath = dirname(dirname($currentScriptPath));

        $path = $basePath . substr($path, 1);
        // normalize for "/" path separtor
        $path = str_replace("\\", "/", $path);
    }

    return $path;
}

// Function to get a JSON file from a given path as an associative array
function get_json_file($path) {
    if (!file_exists($path)) {
        return [null, "File not found"];
    }

    $json = file_get_contents($path);
    if ($json === false) {
        return [null, "Failed to read file"];
    }

    $json = filter_json($json);

    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [null, "Invalid JSON format"];
    }

    return [$data, ""];
}

// Function to check if a node exists by checking for "/_data_/<node_id>"
function node_exists($node_id) {
    $path = normalize_path_to_api_root("./_data_/$node_id");
    return file_exists($path);
}

// Function to get the config file for a node (if it exists) "/_data_/<node_id>/config.json"
function get_node_config($node_id) {
    $path = normalize_path_to_api_root("./_data_/$node_id/config.json");
    if (!file_exists($path)) {
        $path = normalize_path_to_api_root("./_data_/$node_id/config.jsonc");
    }
    return get_json_file($path);
}

// Function to get the data file for a node (if it exists) "/_data_/<node_id>/data.json"
function get_node_data($node_id) {
    $path = normalize_path_to_api_root("./_data_/$node_id/data.json");
    if (!file_exists($path)) {
        $path = normalize_path_to_api_root("./_data_/$node_id/data.jsonc");
    }
    return get_json_file($path);
}

// Function to read a data node file using the node_id and a relative path
function read_data_node_file($node_id, $relative_path) {
    $path = normalize_path_to_api_root("./_data_/$node_id/$relative_path");
    return get_json_file($path);
}

// Fetch JSON file from a given url
function get_json_url($url) {
    $json = file_get_contents($url);
    if ($json === false) {
        return [null, "Failed to fetch file"];
    }

    $json = filter_json($json);

    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [null, "Invalid JSON format"];
    }

    return [$data, ""];
}