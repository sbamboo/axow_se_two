<?php
/**
 * @file data_nodes.php
 * @brief This file contains functions to handle the data nodes.
 */

require_once("libs/php-json-comment.php");

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

// Function to listdir all files in a directory, directory is relative to the node_id
function list_data_node_files($node_id, $relative_path) {
    $path = normalize_path_to_api_root("./_data_/$node_id/$relative_path");
    if (!is_dir($path)) {
        return [null, "Directory not found"];
    }

    $files = scandir($path);
    if ($files === false) {
        return [null, "Failed to read directory"];
    }

    // Filter out "." and ".."
    $files = array_diff($files, ['.', '..']);
    
    return [$files, ""];
}

// Function to check if a data node file exists using the node_id and a relative path
function check_data_node_file_exists($node_id, $relative_path) {
    $path = normalize_path_to_api_root("./_data_/$node_id/$relative_path");
    return file_exists($path);
}

// Function to read a data node file using the node_id and a relative path
function read_data_node_file($node_id, $relative_path) {
    $path = normalize_path_to_api_root("./_data_/$node_id/$relative_path");
    if (!file_exists($path)) {
        return [null, "File not found"];
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        return [null, "Failed to read file"];
    }
    return [$raw, ""];
}

// Function to write a data node file, file is relative to the node_id
function write_data_node_file($node_id, $relative_path, $data) {
    $path = normalize_path_to_api_root("./_data_/$node_id/$relative_path");

    // If the data node file already exists, return an error
    if (file_exists($path)) {
        return [false, "File already exists"];
    }
    
    // Ensure the directory exists
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true); // 0755 permissions = read/write/execute for owner, read/execute for group and others
        if (!is_dir($dir)) {
            return [false, "Failed to create directory"];
        }
    }

    $result = file_put_contents($path, $data);
    if ($result === false) {
        return [false, "Failed to write file"];
    }

    return [true, ""];
}

// Function to remove a data node file, file is relative to the node_id
function remove_data_node_file($node_id, $relative_path) {
    $path = normalize_path_to_api_root("./_data_/$node_id/$relative_path");
    
    // If the file does not exist, return an error
    if (!file_exists($path)) {
        return [false, "File does not exist"];
    }

    $result = unlink($path);
    if ($result === false) {
        return [false, "Failed to delete file"];
    }

    return [true, ""];
}

// Function to replace a data node file, file is relative to the node_id
function replace_data_node_file($node_id, $relative_path, $data) {
    // Check if the file exists if so remove it then call write_data_node_file
    $path = normalize_path_to_api_root("./_data_/$node_id/$relative_path");
    if (file_exists($path)) {
        $result = unlink($path);
        if ($result === false) {
            return [false, "Failed to delete existing file"];
        }
    }

    // Write the new file
    return write_data_node_file($node_id, $relative_path, $data);
}

// Function to append to a data node file, file is relative to the node_id
function append_data_node_file($node_id, $relative_path, $data) {
    $path = normalize_path_to_api_root("./_data_/$node_id/$relative_path");

    // If the file does not exist, create it (call write_data_node_file and check for file already exists)
    if (!file_exists($path)) {
        return write_data_node_file($node_id, $relative_path, $data);
    }

    // Read the existing data
    list($existing_data, $error) = read_data_node_file($node_id, $relative_path);
    if ($existing_data === null) {
        return [false, "Failed to read existing file: $error"];
    }

    // Append the new data
    $new_data = $existing_data . $data;

    // Write the new data back to the file overwriting the existing file using replace_data_node_file
    $result = replace_data_node_file($node_id, $relative_path, $new_data);
    if ($result[0] === false) {
        return [false, "Failed to write appended data: " . $result[1]];
    }

    return [true, ""];
}

// Function to prepend to a data node file, file is relative to the node_id
function prepend_data_node_file($node_id, $relative_path, $data) {
    $path = normalize_path_to_api_root("./_data_/$node_id/$relative_path");

    // If the file does not exist, create it (call write_data_node_file and check for file already exists)
    if (!file_exists($path)) {
        return write_data_node_file($node_id, $relative_path, $data);
    }

    // Read the existing data
    list($existing_data, $error) = read_data_node_file($node_id, $relative_path);
    if ($existing_data === null) {
        return [false, "Failed to read existing file: $error"];
    }

    // Prepend the new data
    $new_data = $data . $existing_data;

    // Write the new data back to the file overwriting the existing file using replace_data_node_file
    $result = replace_data_node_file($node_id, $relative_path, $new_data);
    if ($result[0] === false) {
        return [false, "Failed to write prepended data: " . $result[1]];
    }

    return [true, ""];
}

// Function to read a data node file using the node_id and a relative path
function read_data_node_json_file($node_id, $relative_path) {
    $path = normalize_path_to_api_root("./_data_/$node_id/$relative_path");
    return get_json_file($path);
}

// Function to write a JSON file to a given path, file is relative to the node_id
function write_data_node_json_file($node_id, $relative_path, $data) {
    $path = normalize_path_to_api_root("./_data_/$node_id/$relative_path");

    // If the data node file already exists, return an error
    if (file_exists($path)) {
        return [false, "File already exists"];
    }
    
    // Ensure the directory exists
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true); // 0755 permissions = read/write/execute for owner, read/execute for group and others
        if (!is_dir($dir)) {
            return [false, "Failed to create directory"];
        }
    }

    $json = json_encode($data);
    if ($json === false) {
        return [false, "Failed to encode JSON: " . json_last_error_msg()];
    }

    $result = file_put_contents($path, $json);
    if ($result === false) {
        return [false, "Failed to write file"];
    }

    return [true, ""];
}

// Function to update a JSON file, file is relative to the node_id
function update_data_node_json_file($node_id, $relative_path, $data) {
    $path = normalize_path_to_api_root("./_data_/$node_id/$relative_path");
    
    // If file not exists use write_data_node_file
    if (!file_exists($path)) {
        return write_data_node_file($node_id, $relative_path, $data);
    }

    // Read the existing data using read_data_node_file
    list($existing_data, $error) = read_data_node_file($node_id, $relative_path);
    if ($existing_data === null) {
        return [false, "Failed to read existing file: $error"];
    }

    // Merge the existing data with the new data
    $merged_data = array_merge($existing_data, $data);

    // Write the merged data back to the file
    $json = json_encode($merged_data);
    if ($json === false) {
        return [false, "Failed to encode JSON: " . json_last_error_msg()];
    }

    // write overriding
    $result = file_put_contents($path, $json);
    if ($result === false) {
        return [false, "Failed to write file"];
    }
    
    return [true, ""];
}