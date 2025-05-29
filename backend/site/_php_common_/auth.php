<?php
/**
 * @file auth.php
 * @brief This file contains functions needed for handling authentication.
 */

require_once("secret_config.php");
require_once("db.php");
require_once("jwt.php");
require_once("requests.php");
require_once("permissions.php");

function get_secure_hash($password) {
    // Hash the password using a secure hashing algorithm
    return password_hash($password, PASSWORD_DEFAULT);
}

function compare_secure_hash($password, $hash) {
    // Compare the password with the hash
    return password_verify($password, $hash);
}

// Function to check if any user has the token as valid_token field
function any_has_token($token) {
    list($db, $db_success, $db_msg, $db_http_code) = get_db();
    if (!$db_success) {
        return [null, false, $db_msg, $db_http_code];
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE valid_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $db->close();

    if (!$user) {
        return [false, true, "Didn't find matching user", 200]; // HTTP code 200 : OK
    } else {
        return [true, true, "Found matching user", 200]; // HTTP code 200 : OK
    }
}

// Function to check if any user has the token as valid_refresh_token field
function any_has_refresh_token($token) {
    list($db, $db_success, $db_msg, $db_http_code) = get_db();
    if (!$db_success) {
        return [null, false, $db_msg, $db_http_code];
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE valid_refresh_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();    
    $db->close();

    if (!$user) {
        return [false, true, "Didn't find matching user", 200]; // HTTP code 200 : OK
    } else {
        return [true, true, "Found matching user", 200]; // HTTP code 200 : OK
    }
}

// Function to check if any user by an undecoded_token:s "usr" has the token as valid_token field, returning the user
//MARK: Should we really get token from the undecoded_token._jwt_ or is it better to pass as param?
function validate_token_ownership($decoded_token) {
    list($db, $db_success, $db_msg, $db_http_code) = get_db();
    if (!$db_success) {
        return [null, false, $db_msg, $db_http_code];
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE ID = ? AND valid_token = ?");
    $stmt->bind_param("ss", $decoded_token["usr"], $decoded_token["_jwt_"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $db->close();

    if (!$user) {
        return [false, true, "Didn't find matching user", 200]; // HTTP code 200 : OK
    } else {
        return [$user, true, "Found matching user", 200]; // HTTP code 200 : OK
    }
}

// Function to check if any user by an undecoded_token:s "usr" has the token as valid_refresh_token field, returning the user
//MARK: Should we really get token from the undecoded_token._jwt_ or is it better to pass as param?
function validate_refresh_token_ownership($decoded_token) {
    list($db, $db_success, $db_msg, $db_http_code) = get_db();
    if (!$db_success) {
        return [null, false, $db_msg, $db_http_code];
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE ID = ? AND valid_refresh_token = ?");
    $stmt->bind_param("ss", $decoded_token["usr"], $decoded_token["_jwt_"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $db->close();

    if (!$user) {
        return [false, true, "Didn't find matching user", 200]; // HTTP code 200 : OK
    } else {
        return [$user, true, "Found matching user", 200]; // HTTP code 200 : OK
    }
}

// Function that uses JwtToken to decode and thus validate the token format
function validate_token_format($token) {
    $decoded_token = JwtToken::validateToken($token);

    if ($decoded_token === null) {
        return [null, false, "Invalid or expired token", 401]; // HTTP code 401 : Unauthorized
    }

    return [$decoded_token, true, "", 200]; // HTTP code 200 : OK
}

// Function to check if an undecoded_token:s "usr" is a valid userid, returns bool
function validate_token_user($decoded_token) {
    list($db, $db_success, $db_msg, $db_http_code) = get_db();
    if (!$db_success) {
        return [null, false, $db_msg, $db_http_code];
    }

    // Check if any match exists and return boolean
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE id = ?");
    $stmt->bind_param("s", $decoded_token["usr"]);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    $db->close();
    return $count > 0;
}

// Function to validate
function validate_user_credentials($data) {
    // Validate the username and password are not empty or null
    if (!isset($data["username"]) || !isset($data["password"])) {
        return [null, false, "Missing username or password", 401]; // HTTP code 401 : Unauthorized
    }

    // Get the DB
    list($db, $db_success, $db_msg, $db_http_code) = get_db();
    if (!$db_success) {
        return [null, false, $db_msg, $db_http_code];
    }

    // Get the user from the database
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $data["username"]);

    if (!$stmt->execute()) {
        return [null, false, "Database error", 500]; // HTTP code 500 : Internal Server Error
    }

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $db->close();

    // Check if the user exists and password matches
    if ($user && compare_secure_hash($data["password"], $user["password_hash"])) {
        return [$user["ID"], true, "User authenticated", 200]; // HTTP code 200 : OK
    } else {
        return [null, false, "Invalid username or password", 401]; // HTTP code 401 : Unauthorized
    }
}

// Function to generate a new token
function get_new_token($token_type, $userid) {
    /*
     0  single-use  One-time token.
     1  single      Only one active token, no refresh key.
     2  pair        Requires a refresh token to refresh.
     3  refresh     Used to refresh a pair token.
    */
    
    global $SECRETS;

    // Validate the token type input
    if (!isset($token_type)) {
        return [false, "Missing token-type", 401]; // HTTP code 401 : Unauthorized
    }
    $token_type_lower = strtolower($token_type);

    // Get permissions
    $permissiondigit_string = "000000000";
    $permissions_array = [];
    list($permissions_array, $permissions_success, $permissions_msg, $permissions_http_code) = get_user_permissions($userid);
    if (!$permissions_success) {
        return [null, false, $permissions_msg, $permissions_http_code];
    }
    if (!empty($permissions_array)) {
        $permissiondigit_string = permission_array_to_digits($permissions_array);
    }

    list($db, $db_success, $db_msg, $db_http_code) = get_db();
    if (!$db_success) {
        return [null, false, $db_msg, $db_http_code];
    }

    // Generate the token
    if ($token_type_lower === "single") {
        // Configure
        $token = null;
        $expires = time() + $SECRETS["single_token_expiration"];
        $tokenObj = new Single_JwtToken($userid, $expires, $permissiondigit_string);
        $token = $tokenObj->issueToken();

        // Update the database with the generated token and its type
        $update = $db->prepare("UPDATE users SET valid_token = ?, valid_token_type = ? WHERE ID = ?");
        $update->bind_param("sss", $token, $token_type_lower, $userid);
        $update->execute();
        $update->close();
        $db->close();

        // Return success
        return [
            [
                "token_type" => $token_type_lower,
                "expires" => $expires,
                "token" => $token,
                "has_full_access" => in_array("*", $permissions_array)
            ],
            true,
            "Token generated successfully",
            200
        ]; // HTTP code 200 : OK

    } else if ($token_type_lower === "single-use") {
        // Configure
        $token = null;
        $expires = time() + $SECRETS["single_use_token_expiration"];
        $tokenObj = new SingleUse_JwtToken($userid, $expires, $permissiondigit_string);
        $token = $tokenObj->issueToken();

        // Update the database with the generated token and its type
        $update = $db->prepare("UPDATE users SET valid_token = ?, valid_token_type = ? WHERE ID = ?");
        $update->bind_param("sss", $token, $token_type_lower, $userid);
        $update->execute();
        $update->close();
        $db->close();

        // Return success
        return [
            [
                "token_type" => $token_type_lower,
                "expires" => $expires,
                "token" => $token,
                "has_full_access" => in_array("*", $permissions_array)
            ],
            true,
            "Token generated successfully",
            200
        ]; // HTTP code 200 : OK

    } else if ($token_type_lower === "pair") {
        $pair_token = null;
        $pair_expires = time() + $SECRETS["pair_token_expiration"];
        $pair_tokenObj = new Pair_JwtToken($userid, $pair_expires, $permissiondigit_string);
        $pair_token = $pair_tokenObj->issueToken();

        $refresh_token = null;
        $refresh_expires = time() + $SECRETS["refresh_token_expiration"];
        $refresh_tokenObj = new Refresh_JwtToken($userid, $refresh_expires);
        $refresh_token = $refresh_tokenObj->issueToken();

        // Update the database with the generated token and its type
        //   valid_token = $pair_token
        //   valid_token_type = $token_type_lower
        //   valid_refresh_token = $refresh_token
        $update = $db->prepare("UPDATE users SET valid_token = ?, valid_token_type = ?, valid_refresh_token = ? WHERE ID = ?");
        $update->bind_param("ssss", $pair_token, $token_type_lower, $refresh_token, $userid);
        $update->execute();
        $update->close();
        $db->close();

        // Return success
        return [
            [
                "token_type" => $token_type_lower,
                "expires" => $pair_expires,
                "token" => $pair_token,
                "refresh_token" => $refresh_token,
                "refresh_expires" => $refresh_expires,
                "has_full_access" => in_array("*", $permissions_array)
            ],
            true,
            "Token generated successfully",
            200
        ]; // HTTP code 200 : OK

    } else {
        $db->close();
        return [null, false, "Invalid token type", 401]; // HTTP code 401 : Unauthorized
    }
}

// Function to invalidate a token
function invalidate_token($token, $invalidate_all=false) {
    $decoded_token = JwtToken::validateToken($token);
    if (!$decoded_token) {
        return [true, "Token already invalid", 200]; // HTTP code 200 : OK
    }

    list($db, $db_success, $db_msg, $db_http_code) = get_db();
    if (!$db_success) {
        return [false, $db_msg, $db_http_code];
    }

    if ($decoded_token["tt"] == 0 || $decoded_token["tt"] == 1 || $decoded_token["tt"] == 2 || $decoded_token["tt"] == 3) { // 0 single-use, 1 single, 2 pair, 3 refresh
        // Find the user in the database based on the token
        $stmt = null;
        if ($decoded_token["tt"] == 3) {
            $stmt = $db->prepare("SELECT * FROM users WHERE valid_refresh_token = ?");
        } else {
            $stmt = $db->prepare("SELECT * FROM users WHERE valid_token = ?");
        }
        $stmt->bind_param("s", $token);
        if (!$stmt->execute()) {
            return [false, "Database error", 500]; // HTTP code 500 : Internal Server Error
        }
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // Check if any user was found, if not no one had the token so we success already invalid
        if (!$user) {
            return [true, "Token already invalid", 200]; // HTTP code 200 : OK
        }

        // Invalidate the token in the database
        $update = null;
        if ($decoded_token["tt"] == 2 && $invalidate_all == false) {
            $update = $db->prepare("UPDATE users SET valid_token = NULL, valid_token_type = NULL WHERE ID = ?");
        } else {
            $update = $db->prepare("UPDATE users SET valid_token = NULL, valid_token_type = NULL, valid_refresh_token = NULL WHERE ID = ?");
        }
        $update->bind_param("s", $user["ID"]);
        if (!$update->execute()) {
            return [false, "Database error", 500]; // HTTP code 500 : Internal Server Error
        }
        $update->close();
        $db->close();

        return [true, "Token invalidated", 200]; // HTTP code 200 : OK
    } else {
        return [false, "Invalid token type", 401]; // HTTP code 401 : Unauthorized
    }
}

// Function to invalidate single-use tokens
function invalidate_single_use_token($token, $decoded_token=null) {
    if ($decoded_token === null) {
        $decoded_token = JwtToken::validateToken($token);
    }
    if (!$decoded_token) {
        return [true, "Token already invalid", 200]; // HTTP code 200 : OK
    }

    if ($decoded_token["tt"] == 0) { // 0 single-use
        list($db, $db_success, $db_msg, $db_http_code) = get_db();
        if (!$db_success) {
            return [false, $db_msg, $db_http_code];
        }

        // Find the user in the database based on the token
        $stmt = $db->prepare("SELECT * FROM users WHERE valid_token = ?");
        $stmt->bind_param("s", $token);
        if (!$stmt->execute()) {
            return [false, "Database error", 500]; // HTTP code 500 : Internal Server Error
        }
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // Check if any user was found, if not no one had the token so we success already invalid
        if (!$user) {
            return [true, "Token already invalid", 200]; // HTTP code 200 : OK
        }

        // Invalidate the token in the database
        $update = $db->prepare("UPDATE users SET valid_token = NULL, valid_token_type = NULL WHERE ID = ?");
        $update->bind_param("s", $user["ID"]);
        if (!$update->execute()) {
            return [false, "Database error", 500]; // HTTP code 500 : Internal Server Error
        }
        $update->close();
        $db->close();

        return [true, "Token invalidated", 200]; // HTTP code 200 : OK
    }

    return [true, "Invalid token type, skipped", 200];
}