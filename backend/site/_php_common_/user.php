<?php
/**
 * @file user.php
 * @brief This file contains helper functions for working with users.
 */

function change_username($userid, $new_username) {
    // Get the database connection
    list($db, $db_success, $db_msg, $db_http_code) = get_db();
    if (!$db_success) {
        return false;
    }

    // Prepare the SQL statement to update the username
    $stmt = $db->prepare("UPDATE users SET username = ? WHERE id = ?");
    $stmt->bind_param("ss", $new_username, $userid);
    if ($stmt->execute() === false) {
        return false;
    }
    $stmt->close();
    $db->close();

    return true;
}

function change_password($userid, $new_password) {
    // Get the database connection
    list($db, $db_success, $db_msg, $db_http_code) = get_db();
    if (!$db_success) {
        return false;
    }

    // Prepare the SQL statement to update the password
    $new_password_hash = get_secure_hash($new_password);
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->bind_param("ss", $new_password_hash, $userid);
    if ($stmt->execute() === false) {
        return false;
    }
    $stmt->close();
    $db->close();

    return true;
}

function get_current_username($userid) {
    // Get the database connection
    list($db, $db_success, $db_msg, $db_http_code) = get_db();
    if (!$db_success) {
        return false;
    }

    // Prepare the SQL statement to get the current username
    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("s", $userid);
    if ($stmt->execute() === false) {
        return false;
    }
    $stmt->bind_result($username);
    $stmt->fetch();
    $stmt->close();
    $db->close();

    return $username;
}

function get_current_password($userid) {
    // Get the database connection
    list($db, $db_success, $db_msg, $db_http_code) = get_db();
    if (!$db_success) {
        return false;
    }

    // Prepare the SQL statement to get the current password hash
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->bind_param("s", $userid);
    if ($stmt->execute() === false) {
        return false;
    }
    $stmt->bind_result($password_hash);
    $stmt->fetch();
    $stmt->close();
    $db->close();

    return $password_hash;
}