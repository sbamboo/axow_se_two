<?php
/**
 * @file responders.php	
 * @brief This file contains wrappers or functions that echo json and die the execution.
 */

require_once("auth.php");
require_once("requests.php");
require_once("user.php");

// Function to send a JSON response and terminate the script
function req_send($success, $msg, $http_code, $data=null) {
    // If incomming $data is not null and is of type "string" json_decode
    if ($data !== null && is_string($data)) {
        $decoded_data = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $msg = "JSON error in response creation: " . json_last_error_msg();
            $success = false;
            $http_code = 500; // HTTP code 500 : Internal Server Error
            $data = null;
        } else {
            $data = $decoded_data;
        }
    }

    http_response_code($http_code);
    $data = array_merge([
        "status" => $success ? "success" : "failed",
        "msg" => $msg,
    ], $data ?? []);
    echo format_json_response($data, isset($_REQUEST["escape_unicode"]) ? true : false);
    die(); //MARK: Should we exit instead?
}

// Function to require a specific HTTP method
function req_require_method($method) {
    if ($_SERVER["REQUEST_METHOD"] !== $method) {
        $endpoint_name = get_endpoint_name();
        req_send(false, "The $endpoint_name endpoint is only available for $method requests", 405); // HTTP code 405 : Method Not Allowed
    }
}

// Function to require one of a list of HTTP methods
function req_require_one_of_methods($methods) {
    $method = $_SERVER["REQUEST_METHOD"];
    if (!in_array($method, $methods)) {
        $endpoint_name = get_endpoint_name();
        req_send(false, "The $endpoint_name endpoint is only available for " . implode(", ", $methods) . " requests", 405); // HTTP code 405 : Method Not Allowed
    }
}

// Function to require a auth header
function req_require_auth_header($type=null) {
    list($type, $token, $success, $msg, $http_code) = get_auth_header();
    
    if (!$success) {
        req_send(false, $msg, $http_code);
    }

    if ($type !== $type) {
        req_send(false, "Invalid token type, $type required.", 401); // HTTP code 401 : Unauthorized
    }

    return [$token, $type];
}

// Function to check if a token is assigned to any user
function req_validate_token_assignment($token) {
    list($has_token, $success, $msg, $http_code) = any_has_token($token);

    if (!$success) {
        req_send(false, $msg, $http_code);
    }

    if (!$has_token) {
        req_send(false, "Invalid or expired token", 401); // HTTP code 401 : Unauthorized
    }

    return $has_token;
}

// Function to use JwtToken to decode and thus validate the token format
function req_validate_token_format($token) {
    list($decoded, $success, $msg, $http_code) = validate_token_format($token);

    if (!$success) {
        req_send(false, $msg, $http_code);
    }

    return $decoded;
}

// Function to require a token
function req_require_token($type=null) {
    // Require the Authorization header
    list($token, $type) = req_require_auth_header($type);

    /*
    // Validate if the token is assigned to a user
    $assigned = req_validate_token_assignment($token);
    if (!$assigned) { return null; }
    */

    // Validate if the token is spec-wise valid
    $decoded_token = req_validate_token_format($token);
    if ($decoded_token === false) {
        req_send(false, "Invalid or expired token", 401); // HTTP code 401 : Unauthorized
    }

    // Validate if the token is assigned to a user and that the tokens "usr" field matches
    list($user, $success, $msg, $http_code) = [null, null, null, null];
    if ($type == "refresh" && $decoded_token["tt"] == 0) {
        list($user, $success, $msg, $http_code) = validate_refresh_token_ownership($decoded_token);
    } else {
        list($user, $success, $msg, $http_code) = validate_token_ownership($decoded_token);
    }
    if (!$success) {
        req_send(false, $msg, $http_code);
    }
    if ($user === false) {
        req_send(false, "Invalid or expired token", 401); // HTTP code 401 : Unauthorized
    }

    // Invalidate single use tokens
    list($success, $msg, $http_code) = invalidate_single_use_token($token, $decoded_token);
    if (!$success) {
        return [null, false, $msg, $http_code]; // HTTP code 500 : Internal Server Error
    }

    // Return
    return $decoded_token;
}

// Function to require a permission for this endpoint or a higher-level or joint permission including the "permission_string" (by user)
//   Takes "userid" optionally so if already has run "req_require_token" it can be passed to avoid double-checking the token ownership
function req_require_user_permission($permission, $decoded_token, $userid=null) {
    if ($userid === null) {
        list($user, $success, $msg, $http_code) = validate_token_ownership($decoded_token);
        if (!$success) {
            req_send(false, $msg, $http_code);
        }
        if ($user === false) {
            req_send(false, "Invalid or expired token", 401); // HTTP code 401 : Unauthorized
        }

        $userid = $user["id"];
    }

    $has = check_user_permission($userid, $permission);
    $endpoint_name = get_endpoint_name();
    if (!$has) {
        req_send(false, "The $endpoint_name endpoint requires the $permission permission (Insufficent user permissions)", 403); // HTTP code 403 : Forbidden
    }
    return $has;
}

// Function to require a permission for this endpoint or a higher-level or joint permission including the "permission_string" (by token)
function req_require_token_permission($permission, $decoded_token) {
    $has = check_digits_permission($decoded_token["perm"], $permission);
    $endpoint_name = get_endpoint_name();
    if (!$has) {
        req_send(false, "The $endpoint_name endpoint requires the $permission permission (Insufficent token permissions)", 403); // HTTP code 403 : Forbidden
    }
    return $has;
}

// Function to require a permission for this endpoint or a higher-level or joint permission including the "permission_string" (by user and token)
function req_require_permission($permission, $decoded_token, $userid=null) {
    $has = req_require_token_permission($permission, $decoded_token);
    if ($has) {
        req_require_user_permission($permission, $decoded_token, $userid);
    }
}

// Function to generate a new token based on $username and $password credentials
function req_get_new_token($token_type, $data, $is_refresh=false) {
    if (!$data) {
        req_send(false, "Invalid request", 400); // HTTP code 400 : Bad Request
    }

    list($userid, $validate_uc_success, $validate_uc_msg, $validate_uc_http_code) = validate_user_credentials($data);
    if (!$validate_uc_success) {
        req_send(false, $validate_uc_msg, $validate_uc_http_code);
    }

    list($token_data, $success, $msg, $http_code) = get_new_token($token_type, $userid);
    if (!$success) {
        req_send(false, $msg, $http_code);
    }

    req_send($success, $msg, 200, $token_data);
}

// Function to invalidate a token
function req_invalidate_token($token) {
    list($success, $msg, $http_code) = invalidate_token($token);
    req_send($success, $msg, $http_code);
}

// Function to automatically invalidate all tokens for a user 
function req_unauth($type=null) {
    // Require the Authorization header
    list($token, $type) = req_require_auth_header($type);

    // Invalidate the token
    list($success, $msg, $http_code) = invalidate_token($token, true); // 'true' is to also always invalidate the refresh-token
    req_send($success, $msg, $http_code);
}

// Function to refresh a pair token using a refresh token
function req_refresh_pair_token($refresh_token) {
    // Validate the format of `refresh_token` using `req_validate_token_format($refresh_token)`
    $decoded_refresh_token = req_validate_token_format($refresh_token);
    if ($decoded_refresh_token === false) {
        req_send(false, "Invalid or expired token", 401); // HTTP code 401 : Unauthorized
    }

    // Validate that `refresh_token` is assigned to the tokens "usr"
    list($user, $success, $msg, $http_code) = validate_refresh_token_ownership($decoded_refresh_token);
    if (!$success) {
        req_send(false, $msg, $http_code);
    }
    if ($user === false) {
        req_send(false, "Invalid or expired token", 401); // HTTP code 401 : Unauthorized
    }
    
    // Call `get_new_token("pair")` to generate new tokens and return to user
    list($token_data, $success, $msg, $http_code) = get_new_token("pair", $user["ID"]);
    if (!$success) {
        req_send(false, $msg, $http_code);
    }

    // Return the new tokens to the user
    req_send($success, $msg, 200, $token_data);
}

// Function to change a user username
function req_change_username($userid) {
    $body_data = get_post_body();

    if ($body_data === null || !isset($body_data["new_username"])) {
        req_send(false, "Invalid request", 400); // HTTP code 400 : Bad Request
    }

    if (empty($body_data["new_username"])) {
        req_send(false, "Invalid new username", 400); // HTTP code 400 : Bad Request
    } else if ($body_data["new_username"] === get_current_username($userid)) {
        req_send(false, "Cannot change to current username", 400); // HTTP code 400 : Bad Request
    }

    $success = change_username($userid, $body_data["new_username"]);

    if (!$success) {
        req_send(false, "Failed to change username", 500); // HTTP code 500 : Internal Server Error
    }
    req_send(true, "Username changed successfully", 200); // HTTP code 200 : OK
}

// Function to change a user password
function req_change_password($userid) {
    $body_data = get_post_body();

    if ($body_data === null || !isset($body_data["new_password"])) {
        req_send(false, "Invalid request", 400); // HTTP code 400 : Bad Request
    }

    if (!isset($body_data["old_password"])) {
        req_send(false, "Old password is required", 400); // HTTP code 400 : Bad Request
    }

    if ($body_data["old_password"] === $body_data["new_password"]) {
        req_send(false, "Values of old_password and new_password can not be the same", 400); // HTTP code 400 : Bad Request
    }

    $current_password_hash = get_current_password($userid);

    if (empty($body_data["new_password"])) {
        req_send(false, "Invalid new password", 400); // HTTP code 400 : Bad Request
    } else if ($body_data["new_password"] === $current_password_hash) {
        req_send(false, "Cannot change to previously used password", 400); // HTTP code 400 : Bad Request
    }

    if (!compare_secure_hash($body_data["old_password"], $current_password_hash)) {
        req_send(false, "Incorrect old password", 403); // HTTP code 403 : Forbidden
    }

    $success = change_password($userid, $body_data["new_password"]);

    if (!$success) {
        req_send(false, "Failed to change password", 500); // HTTP code 500 : Internal Server Error
    }
    req_send(true, "Password changed successfully", 200); // HTTP code 200 : OK
}