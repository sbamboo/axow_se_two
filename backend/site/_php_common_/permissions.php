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

// Function to get the "permission_digits" from an array of "permission_string"s
function permission_array_to_digits($permissions) {
    // Start zeroed
    $permissions_array = array_fill(0, 11, "0");

    // Loop through the user permissions
    foreach ($permissions as $permission) {
        switch ($permission) {
            // Full Access Permissions (Index 0)
            case "*":
                $permissions_array[0] = "1";
                break;

            // Articles Permissions (Index 1)
            case "articles.*":
                $permissions_array[1] = "1";
                break;
            case "articles.add":
                $permissions_array[1] = "2";
                break;
            case "articles.modify":
                $permissions_array[1] = "3";
                break;
            case "articles.remove":
                $permissions_array[1] = "4";
                break;
            case "articles.add-modify":
                $permissions_array[1] = "5";
                break;
            case "articles.add-remove":
                $permissions_array[1] = "6";
                break;
            case "articles.remove-modify":
                $permissions_array[1] = "7";
                break;

            // Articles Categories Permissions (Index 2)
            case "articles-cat.*":
                $permissions_array[2] = "1";
                break;
            case "articles-cat.add":
                $permissions_array[2] = "2";
                break;
            case "articles-cat.modify":
                $permissions_array[2] = "3";
                break;
            case "articles-cat.remove":
                $permissions_array[2] = "4";
                break;
            case "articles-cat.add-modify":
                $permissions_array[2] = "5";
                break;
            case "articles-cat.add-remove":
                $permissions_array[2] = "6";
                break;
            case "articles-cat.remove-modify":
                $permissions_array[2] = "7";
                break;

            // Articles Subcategories Permissions (Index 3)
            case "articles-subcat.*":
                $permissions_array[3] = "1";
                break;
            case "articles-subcat.add":
                $permissions_array[3] = "2";
                break;
            case "articles-subcat.modify":
                $permissions_array[3] = "3";
                break;
            case "articles-subcat.remove":
                $permissions_array[3] = "4";
                break;
            case "articles-subcat.add-modify":
                $permissions_array[3] = "5";
                break;
            case "articles-subcat.add-remove":
                $permissions_array[3] = "6";
                break;
            case "articles-subcat.remove-modify":
                $permissions_array[3] = "7";
                break;

            // Wiki Permissions (Index 4)
            case "wiki.*":
                $permissions_array[4] = "1";
                break;
            case "wiki.add":
                $permissions_array[4] = "2";
                break;
            case "wiki.modify":
                $permissions_array[4] = "3";
                break;
            case "wiki.remove":
                $permissions_array[4] = "4";
                break;
            case "wiki.add-modify":
                $permissions_array[4] = "5";
                break;
            case "wiki.add-remove":
                $permissions_array[4] = "6";
                break;
            case "wiki.remove-modify":
                $permissions_array[4] = "7";
                break;

            // Wiki Categories Permissions (Index 5)
            case "wiki-cat.*":
                $permissions_array[5] = "1";
                break;
            case "wiki-cat.add":
                $permissions_array[5] = "2";
                break;
            case "wiki-cat.modify":
                $permissions_array[5] = "3";
                break;
            case "wiki-cat.remove":
                $permissions_array[5] = "4";
                break;
            case "wiki-cat.add-modify":
                $permissions_array[5] = "5";
                break;
            case "wiki-cat.add-remove":
                $permissions_array[5] = "6";
                break;
            case "wiki-cat.remove-modify":
                $permissions_array[5] = "7";
                break;

            // Wiki Subcategories Permissions (Index 6)
            case "wiki-subcat.*":
                $permissions_array[6] = "1";
                break;
            case "wiki-subcat.add":
                $permissions_array[6] = "2";
                break;
            case "wiki-subcat.modify":
                $permissions_array[6] = "3";
                break;
            case "wiki-subcat.remove":
                $permissions_array[6] = "4";
                break;
            case "wiki-subcat.add-modify":
                $permissions_array[6] = "5";
                break;
            case "wiki-subcat.add-remove":
                $permissions_array[6] = "6";
                break;
            case "wiki-subcat.remove-modify":
                $permissions_array[6] = "7";
                break;

            // Wiki Home Permissions (Index 7)
            case "wiki-home.*":
                $permissions_array[6] = "1";
                break;
            case "wiki-home.update":
                $permissions_array[6] = "2";
                break;

            // Profiles Permissions (Index 8)
            case "profiles.*":
                $permissions_array[7] = "1";
                break;
            case "profiles.add":
                $permissions_array[7] = "2";
                break;
            case "profiles.modify":
                $permissions_array[7] = "3";
                break;
            case "profiles.remove":
                $permissions_array[7] = "4";
                break;
            case "profiles.add-modify":
                $permissions_array[7] = "5";
                break;
            case "profiles.add-remove":
                $permissions_array[7] = "6";
                break;
            case "profiles.remove-modify":
                $permissions_array[7] = "7";
                break;

            // Profile Restriction Permissions (Index 9)
            case "all-profiles":
                $permissions_array[8] = "1";
                break;
            case "your-profile":
                $permissions_array[8] = "2";
                break;

            // URL Preview Permissions (Index 10)
            case "url-preview.*":
                $permissions_array[8] = "1";
                break;
            case "url-preview.fetch":
                $permissions_array[8] = "2";
                break;
        }
    }

    // Convert the array into a string of digits and return
    return implode("", $permissions_array);
}

// Function to get an array of "permission_string"s from a "permission_digits" string
function permission_digits_to_array($digits) {
    // Check if the $digits is a string of 11 digits
    if (!is_string($digits) || strlen($digits) != 11) {
        return null;
    }

    // Initialize an empty array to store the permissions
    $permissions = [];

    // Loop through the digits and add the corresponding permission to the array
    for ($i = 0; $i < strlen($digits); $i++) {
        switch ($i) {
            case 0:
                if ($digits[$i] == "1") {
                    $permissions[] = "*";
                }
                break;
            case 1:
                if ($digits[$i] == "1") {
                    $permissions[] = "articles.*";
                } elseif ($digits[$i] == "2") {
                    $permissions[] = "articles.add";
                } elseif ($digits[$i] == "3") {
                    $permissions[] = "articles.modify";
                } elseif ($digits[$i] == "4") {
                    $permissions[] = "articles.remove";
                } elseif ($digits[$i] == "5") {
                    $permissions[] = "articles.add-modify";
                } elseif ($digits[$i] == "6") {
                    $permissions[] = "articles.add-remove";
                } elseif ($digits[$i] == "7") {
                    $permissions[] = "articles.remove-modify";
                }
                break;
            case 2:
                if ($digits[$i] == "1") {
                    $permissions[] = "articles-cat.*";
                } elseif ($digits[$i] == "2") {
                    $permissions[] = "articles-cat.add";
                } elseif ($digits[$i] == "3") {
                    $permissions[] = "articles-cat.modify";
                } elseif ($digits[$i] == "4") {
                    $permissions[] = "articles-cat.remove";
                } elseif ($digits[$i] == "5") {
                    $permissions[] = "articles-cat.add-modify";
                } elseif ($digits[$i] == "6") {
                    $permissions[] = "articles-cat.add-remove";
                } elseif ($digits[$i] == "7") {
                    $permissions[] = "articles-cat.remove-modify";
                }
                break;
            case 3:
                if ($digits[$i] == "1") {
                    $permissions[] = "articles-subcat.*";
                } elseif ($digits[$i] == "2") {
                    $permissions[] = "articles-subcat.add";
                } elseif ($digits[$i] == "3") {
                    $permissions[] = "articles-subcat.modify";
                } elseif ($digits[$i] == "4") {
                    $permissions[] = "articles-subcat.remove";
                } elseif ($digits[$i] == "5") {
                    $permissions[] = "articles-subcat.add-modify";
                } elseif ($digits[$i] == "6") {
                    $permissions[] = "articles-subcat.add-remove";
                } elseif ($digits[$i] == "7") {
                    $permissions[] = "articles-subcat.remove-modify";
                }
                break;
            case 4:
                if ($digits[$i] == "1") {
                    $permissions[] = "wiki.*";
                } elseif ($digits[$i] == "2") {
                    $permissions[] = "wiki.add";
                } elseif ($digits[$i] == "3") {
                    $permissions[] = "wiki.modify";
                } elseif ($digits[$i] == "4") {
                    $permissions[] = "wiki.remove";
                } elseif ($digits[$i] == "5") {
                    $permissions[] = "wiki.add-modify";
                } elseif ($digits[$i] == "6") {
                    $permissions[] = "wiki.add-remove";
                } elseif ($digits[$i] == "7") {
                    $permissions[] = "wiki.remove-modify";
                }
                break;
            case 5:
                if ($digits[$i] == "1") {
                    $permissions[] = "wiki-cat.*";
                } elseif ($digits[$i] == "2") {
                    $permissions[] = "wiki-cat.add";
                } elseif ($digits[$i] == "3") {
                    $permissions[] = "wiki-cat.modify";
                } elseif ($digits[$i] == "4") {
                    $permissions[] = "wiki-cat.remove";
                } elseif ($digits[$i] == "5") {
                    $permissions[] = "wiki-cat.add-modify";
                } elseif ($digits[$i] == "6") {
                    $permissions[] = "wiki-cat.add-remove";
                } elseif ($digits[$i] == "7") {
                    $permissions[] = "wiki-cat.remove-modify";
                }
                break;
            case 6:
                if ($digits[$i] == "1") {
                    $permissions[] = "wiki-subcat.*";
                } elseif ($digits[$i] == "2") {
                    $permissions[] = "wiki-subcat.add";
                } elseif ($digits[$i] == "3") {
                    $permissions[] = "wiki-subcat.modify";
                } elseif ($digits[$i] == "4") {
                    $permissions[] = "wiki-subcat.remove";
                } elseif ($digits[$i] == "5") {
                    $permissions[] = "wiki-subcat.add-modify";
                } elseif ($digits[$i] == "6") {
                    $permissions[] = "wiki-subcat.add-remove";
                } elseif ($digits[$i] == "7") {
                    $permissions[] = "wiki-subcat.remove-modify";
                }
                break;
            case 7:
                if ($digits[$i] == "1") {
                    $permissions[] = "wiki-home.*";
                } elseif ($digits[$i] == "2") {
                    $permissions[] = "wiki-home.update";
                }
                break;
            case 8:
                if ($digits[$i] == "1") {
                    $permissions[] = "profiles.*";
                } elseif ($digits[$i] == "2") {
                    $permissions[] = "profiles.add";
                } elseif ($digits[$i] == "3") {
                    $permissions[] = "profiles.modify";
                } elseif ($digits[$i] == "4") {
                    $permissions[] = "profiles.remove";
                } elseif ($digits[$i] == "5") {
                    $permissions[] = "profiles.add-modify";
                } elseif ($digits[$i] == "6") {
                    $permissions[] = "profiles.add-remove";
                } elseif ($digits[$i] == "7") {
                    $permissions[] = "profiles.remove-modify";
                }
                break;
            case 9:
                if ($digits[$i] == "1") {
                    $permissions[] = "all-profiles";
                } elseif ($digits[$i] == "2") {
                    $permissions[] = "your-profile";
                }
                break;
            case 10:
                if ($digits[$i] == "1") {
                    $permissions[] = "url-preview.*";
                } elseif ($digits[$i] == "2") {
                    $permissions[] = "url-preview.update";
                }
                break;
        }
    }

    // Return the array of permissions
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

// Function to check if a user has a "permission_string" or a higher-level or joint permission including the "permission_string"
function check_user_permission($userid, $permission) {
    // Check if the $user has the "*" permission, i.e full access
    if (check_user_permission_exact($userid, "*")) {
        return true;
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