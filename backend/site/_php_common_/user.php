<?php

function change_username($userid, $new_username) {
    // Get the database connection
    $db = get_db();

    // Prepare the SQL statement to update the username
    $stmt = $db->prepare("UPDATE users SET username = ? WHERE id = ?");
    $stmt->bind_param("si", $new_username, $userid);
    if ($stmt->execute() === false) {
        return false;
    }
    $stmt->close();
    $db->close();

    return true;
}

function change_password($userid, $new_password) {
    // Get the database connection
    $db = get_db();

    // Prepare the SQL statement to update the password
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->bind_param("si", get_secure_hash($new_password), $userid);
    if ($stmt->execute() === false) {
        return false;
    }
    $stmt->close();
    $db->close();

    return true;
}

function get_current_username($userid) {
    // Get the database connection
    $db = get_db();

    // Prepare the SQL statement to get the current username
    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $userid);
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
    $db = get_db();

    // Prepare the SQL statement to get the current password hash
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->bind_param("i", $userid);
    if ($stmt->execute() === false) {
        return false;
    }
    $stmt->bind_result($password_hash);
    $stmt->fetch();
    $stmt->close();
    $db->close();

    return $password_hash;
}