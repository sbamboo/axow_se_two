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