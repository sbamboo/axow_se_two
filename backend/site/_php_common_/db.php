<?php

// Helper: Function to get database connection with MariaDB (MySQLi)
function get_db() {
    $host = 'localhost'; // Database host
    $user = 'root';      // Database username
    $password = '';      // Database password
    $dbname = 'axow_se'; // Database name

    // Create a new MySQLi connection
    $db = new mysqli($host, $user, $password, $dbname);

    // Check the connection
    if ($db->connect_error) {
        die("Connection failed: " . $db->connect_error);
    }
    return $db;
}

function getUserPermissionsString($userId) {
    $db = get_db();

    $sql = "
        SELECT user_permissions.string
        FROM users_to_permissions
        JOIN user_permissions ON users_to_permissions.permission_id = user_permissions.ID
        WHERE users_to_permissions.user_id = ?
    ";

    // Prepare the statement
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return ["", false, "Prepare failed: " . $db->error];
    }

    // Bind the user ID
    if (!$stmt->bind_param("s", $userId)) {
        return ["", false, "Bind failed: " . $stmt->error];
    }

    // Execute the query
    if (!$stmt->execute()) {
        return ["", false, "Execute failed: " . $stmt->error];
    }

    // Get the result
    $result = $stmt->get_result();
    if (!$result) {
        return ["", false, "Failed to get result: " . $stmt->error];
    }

    // Fetch all permissions into an array
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row['string'];
    }

    $stmt->close();
    $db->close();

    return [implode("; ", $permissions), true, "Permissions retrieved successfully."];
}
