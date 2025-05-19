<?php
/**
 * @file requests.php
 * @brief This file contains helper functions for working with HTTP requests.
 */

// Function to get the body of a POST request, defaults to "php://input" body else $_REQUEST
function get_post_body() {
    $data = json_decode(file_get_contents("php://input"), true);

    // if data is empty use $_REQUEST
    if (empty($data)) {
        $data = $_REQUEST;
    }

    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    return $data;
}

// Function to get the body for any request type
function get_request_body($method=null) {
    if ($method !== null) {
        switch ($method) {
            case "GET":
                return $_GET;
            case "POST":
                return get_post_body();
            case "PUT":
                return get_post_body();
            case "DELETE":
                return get_post_body();
            case "COOKIE":
                return $_COOKIE;
            case "FILES":
                return $_FILES;
            default:
                return null;
        }
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        return get_post_data();
    } else {
        return $_REQUEST;
    }
}

// Function to value of the Authorization header
function get_auth_header() {
    // Get the token from the request headers
    $headers = getallheaders();
    $auth_header = $headers["Authorization"] ?? "";
    
    if (empty($auth_header)) {
        return [null, null, false, "Authorization header not found", 400]; // HTTP code 400 : Bad Request
    }
    
    // if " " in the token, split it
    $type = null;
    $token = null;
    if (strpos($auth_header, " ") !== false) {
        list($type, $token) = explode(" ", $auth_header, 2);
    } else {
        $token = $auth_header;
    }
    
    if (!$token || $token === null || preg_replace("/\s+/", "", $token) == "") {
        return [null, null, false, "Empty token in Authorization header", 401]; // HTTP code 401 : Unauthorized
    }

    return [$type, $token, true, "", 200]; // HTTP code 200 : OK
}

// Function to get the name of the endpoint
function get_endpoint_name() {
    // Get the path of this PHP script
    //   ".../backend/site/_php_common_/request.php"
    $currentScriptPath = realpath(__FILE__);

    // Traverse up one directory to get the base path of the api
    //   ".../backend/site/"
    $basePath = dirname(dirname($currentScriptPath));

    // Get the path of the current executor script (first called)
    //   ".../backend/site/.../index.php"
    $executorScriptPath = realpath($_SERVER["SCRIPT_FILENAME"]);

    // Get the parent folder of the executor script
    //   ".../backend/site/.../"
    $executorFolder = dirname($executorScriptPath);

    // Get the endpoint name by subtracting the base path from the executor folder path
    //   ".../backend/site/.../" - ".../backend/site/" = ".../"
    if (strpos($executorFolder, $basePath) === 0) {
        $endpoint = substr($executorFolder, strlen($basePath));
        $endpoint = ltrim($endpoint, DIRECTORY_SEPARATOR);
        return $endpoint;
    } else {
        return "";
    }
}