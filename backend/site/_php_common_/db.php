<?php
/**
 * @file db.php
 * @brief This file contains functions to connect to the database.
 */

// Get database connection with MariaDB (MySQLi)
function get_db() {
    $host = "localhost"; // Database host
    $user = "root";      // Database username
    $password = "";      // Database password
    $dbname = "axow_se"; // Database name

    // Create a new MySQLi connection
    $db = new mysqli($host, $user, $password, $dbname);

    // Check the connection
    if ($db->connect_error) {
        return [null, false, "Connection failed: " . $db->connect_error, 500]; // HTTP code 500 : Internal Server Error
    }
    return [$db, true, "", 200]; // HTTP code 200 : OK
}

