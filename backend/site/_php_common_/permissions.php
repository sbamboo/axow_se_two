<?php
/**
 * @file permissions.php
 * @brief This file contains functions to handle permissions.
 */

require_once("db.php");

/**
 * Formats:
 *   "permission_digits"       : A string of the digits used to represent permissions inside tokens, each index is a permission category and each value is a specific permission.
 *   "permission_string"       : The string name of a permission. (Used for users)
 *   "user_permissions_string" : A string with a "; " sepparated list of permission_string:s.
 */

// Function to get the permissions of a user as array of "permission_string"s
function get_user_permissions($userid) {
    list($db, $db_success, $db_msg, $db_http_code) = get_db();
    if (!$db_success) {
        return [null, false, $db_msg, $db_http_code];
    }

    $stmt = $db->prepare("SELECT up.string FROM users_to_permissions utp JOIN user_permissions up ON utp.permission_id = up.ID WHERE utp.user_id = ?");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $permissions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $db->close();

    if (!$permissions) {
        return [null, false, "Didn't find matching user", 200]; // HTTP code 200 : OK
    } else {
        $permissions = array_map(function($permission) { return $permission["string"]; }, $permissions);
        return [$permissions, true, "Found matching user", 200]; // HTTP code 200 : OK
    }
}

// Function to get the length of the "permission_digits" string
function get_permissiondigits_length() {
    list($db, $db_success, $db_msg, $db_http_code) = get_db();
    if (!$db_success) {
        return [false, $db_msg, $db_http_code];
    }

    $query = "SELECT MAX(`digit_index`) AS max_index FROM user_permissions";
    $result = $db->query($query);
    if (!$result) {
        return [false, "DB query failed", 500];
    }

    $row = $result->fetch_assoc();
    $max_index = isset($row["max_index"]) ? (int)$row["max_index"] : 0;

    return [$max_index + 1, null, null];
}

// Function to load the permissions mapping from the database
function load_permissions_mapping() {
    list($db, $db_success, $db_msg, $db_http_code) = get_db();
    if (!$db_success) {
        return [false, $db_msg, $db_http_code];
    }

    $query = "SELECT `string`, `digit_index`, `digit` FROM user_permissions";
    $result = $db->query($query);
    if (!$result) {
        return [false, "DB query failed", 500];
    }

    $string_to_index_digit = [];
    $index_digit_to_string = [];

    while ($row = $result->fetch_assoc()) {
        $str = $row["string"];
        $idx = (int)$row["digit_index"];
        $dig = (int)$row["digit"];

        $string_to_index_digit[$str] = ["digit_index" => $idx, "digit" => $dig];
        $index_digit_to_string[$idx][$dig] = $str;
    }

    return [[$string_to_index_digit, $index_digit_to_string], null, null];
}

// Function to convert an array of permission strings to a "permission_digits" string
function permission_array_to_digits($permissions) {
    list($maps, $err_msg, $http_code) = load_permissions_mapping();
    if (!$maps) {
        return [false, $err_msg, $http_code];
    }
    list($string_to_index_digit, $index_digit_to_string) = $maps;

    list($length, $err_msg, $http_code) = get_permissiondigits_length();
    if ($length === false) {
        return [false, $err_msg, $http_code];
    }

    $permissions_array = array_fill(0, $length, "0");

    foreach ($permissions as $perm_str) {
        if (isset($string_to_index_digit[$perm_str])) {
            $idx = $string_to_index_digit[$perm_str]["digit_index"];
            $dig = $string_to_index_digit[$perm_str]["digit"];
            $permissions_array[$idx] = (string)$dig;
        }
    }

    return implode("", $permissions_array);
}

// Function to convert a "permission_digits" string to an array of permission strings
function permission_digits_to_array($digits) {
    list($maps, $err_msg, $http_code) = load_permissions_mapping();
    if (!$maps) {
        return [false, $err_msg, $http_code];
    }
    list($string_to_index_digit, $index_digit_to_string) = $maps;

    $length = strlen($digits);

    $permissions = [];
    for ($i = 0; $i < $length; $i++) {
        $digit = (int)$digits[$i];
        if ($digit === 0) continue;

        if (isset($index_digit_to_string[$i][$digit])) {
            $permissions[] = $index_digit_to_string[$i][$digit];
        }
    }

    return $permissions;
}

// Function to grant a "permission_string" to a user
function grant_user_permission($userid, $permission) {
    list($db, $db_success, $db_msg, $db_http_code) = get_db();
    if (!$db_success) {
        return [false, $db_msg, $db_http_code];
    }

    if (check_user_permission_exact($userid, $permission)) {
        return [false, "User already has the permission", 200]; // HTTP code 200 : OK
    }

    // Add the permission to the user
    //   users_to_permissions : ID, user_id, permission_id
    //   user_permissions     : ID, string

    $stmt = $db->prepare("INSERT INTO users_to_permissions (user_id, permission_id) VALUES (?, (SELECT ID FROM user_permissions WHERE string = ?))");
    $stmt->bind_param("ss", $userid, $permission);
    $stmt->execute();
    $result = $stmt->affected_rows;
    $stmt->close();
    $db->close();

    if ($result == 0) {
        return [false, "Failed to grant permission", 500]; // HTTP code 500 : Internal Server Error
    } else {
        return [true, "Permission granted", 200]; // HTTP code 200 : OK
    }
}

// Function to revoke a "permission_string" from a user
function revoke_user_permission($userid, $permission) {
    list($db, $db_success, $db_msg, $db_http_code) = get_db();
    if (!$db_success) {
        return [false, $db_msg, $db_http_code];
    }

    if (!check_user_permission_exact($userid, $permission)) {
        return [false, "User doesn't have the permission", 200]; // HTTP code 200 : OK
    }

    // Remove the permission from the user
    //   users_to_permissions : ID, user_id, permission_id
    //   user_permissions     : ID, string

    $stmt = $db->prepare("DELETE FROM users_to_permissions WHERE user_id = ? AND permission_id = (SELECT ID FROM user_permissions WHERE string = ?)");
    $stmt->bind_param("ss", $userid, $permission);
    $stmt->execute();
    $result = $stmt->affected_rows;
    $stmt->close();
    $db->close();

    if ($result == 0) {
        return [false, "Failed to revoke permission", 500]; // HTTP code 500 : Internal Server Error
    } else {
        return [true, "Permission revoked", 200]; // HTTP code 200 : OK
    }
}

// Function to check if a user has a "permission_string"
function check_user_permission_exact($userid, $permission) {
    list($permissions, $success, $msg, $http_code) = get_user_permissions($userid);
    if (!$success) {
        return false;
    }

    if (in_array($permission, $permissions)) {
        return true;
    }
    return false;
}

// Function to check if a $permission_string has is_property to 1 (its TINYINT(1))
function is_property_permission($permission) {
    list($db, $db_success, $db_msg, $db_http_code) = get_db();
    if (!$db_success) {
        return [null, false, $db_msg, $db_http_code];
    }

    $stmt = $db->prepare("SELECT is_property FROM user_permissions WHERE string = ?");
    $stmt->bind_param("s", $permission);
    if (!$stmt) {
        $db->close();
        return [null, false, "Database error: " . $db->error, 500]; // HTTP code 500 : Internal Server Error
    }
    $stmt->execute();
    if ($stmt->error) {
        $stmt->close();
        $db->close();
        return [null, false, "Database error: " . $stmt->error, 500]; // HTTP code 500 : Internal Server Error
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    $db->close();
    if (!$row) {
        return [null, false, "Permission not found", 404]; // HTTP code 404 : Not Found
    }
    return [$row['is_property'] == 1, true, "", 200]; // HTTP code 200 : OK
}

// Function to check if a user has a "permission_string" or a higher-level or joint permission including the "permission_string"
function check_user_permission($userid, $permission) {
    // Check if the $user has the "*" permission, i.e full access
    if (check_user_permission_exact($userid, "*")) {
        return true;
        // If the $permission is a property this does not count
        if (!is_property_permission($permission)) {
            return true;
        }
    }

    // Get the root of $permission (i.e articles.add -> articles)
    //   Check for ".", if found split by "." and $permission_root is first ad $permission_action is latter
    if (strpos($permission, ".") !== false) {
        list($permission_root, $permission_action) = explode(".", $permission, 2);
    } else {
        $permission_root = $permission;
        $permission_action = null;
    }

    // Check if the $user has the "$permission_root.*" permission, i.e access to all actions
    if (check_user_permission_exact($userid, "$permission_root.*")) {
        return true;
    }

    // Check if the $user has any joint permission, i.e "articles.add-remove" -> "articles.add", "articles.remove"
    //   Check if "-" in the $permission_action, if found split by "-" and check for $permission
    if (strpos($permission_action, "-") !== false) {
        // Get array of permissions from $permission_action by splitting by "-"
        $permission_actions = explode("-", $permission_action);

        // Check if any of the $permission_actions is equal to $permission
        foreach ($permission_actions as $action) {
            if (check_user_permission_exact($userid, "$permission_root.$action")) {
                return true;
            }
        }
    }

    // Check if the $user has the $permission
    if (check_user_permission_exact($userid, $permission)) {
        return true;
    }

    return false;
}

// Function to check if a "permission_digits" contains a "permission_string"
function check_digits_permission_exact($digits, $permission) {
    $permission_array = permission_digits_to_array($digits);

    // Check if the $permission is in the $permission_array
    if (in_array($permission, $permission_array)) {
        return true;
    }
    return false;
}

// Function to check if a "permission_digits" contains a "permission_string" or a higher-level or joint permission including the "permission_string"
function check_digits_permission($digits, $permission) {
    $permission_array = permission_digits_to_array($digits);

    // Check if the $user has the "*" permission, i.e full access
    if (in_array("*", $permission_array)) {
        return true;
        // If the $permission is a property this does not count
        if (!is_property_permission($permission)) {
            return true;
        }
    }

    // Get the root of $permission (i.e articles.add -> articles)
    //   Check for ".", if found split by "." and $permission_root is first ad $permission_action is latter
    if (strpos($permission, ".") !== false) {
        list($permission_root, $permission_action) = explode(".", $permission, 2);
    } else {
        $permission_root = $permission;
        $permission_action = null;
    }

    // Check if the $user has the "$permission_root.*" permission, i.e access to all actions
    if (in_array("$permission_root.*", $permission_array)) {
        return true;
    }

    // Check if the $user has any joint permission, i.e "articles.add-remove" -> "articles.add", "articles.remove"
    //   Check if "-" in the $permission_action, if found split by "-" and check for $permission
    if (strpos($permission_action, "-") !== false) {
        // Get array of permissions from $permission_action by splitting by "-"
        $permission_actions = explode("-", $permission_action);

        // Check if any of the $permission_actions is equal to $permission
        foreach ($permission_actions as $action) {
            if (in_array("$permission_root.$action", $permission_array)) {
                return true;
            }
        }
    }

    // Check if the $user has the $permission
    if (in_array($permission, $permission_array)) {
        return true;
    }

    return false;
}