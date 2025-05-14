<?php
require_once('jwt.php');

// Function to get database connection with MariaDB (MySQLi)
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

function handle_auth_request($token_type, $data) {

    /*
     0  single_use       One-time token.
     1  single           Only one active token, no refresh key.
     2  refresh_main     Requires a Refresh:Refresh token to refresh.
     3  refresh_refresh  Used to refresh another token.
    */

    switch (expression) {
        case 'single':
            if (!isset($data['username'], $data['password_hash'])) {
                http_response_code(400);
                echo json_encode(['status' => 'failed', 'msg' => 'Missing username or password_hash']);
                return;
            }

             // Get the user from the database by username
            $db = get_db();
            $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
            $stmt->bindValue(1, $data['username']);
            $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

            // Check if the user exists and password matches
            if (!$user || $user['password_hash'] !== $data['password_hash']) {
                http_response_code(401); // Unauthorized
                echo json_encode(['status' => 'failed', 'msg' => 'Invalid credentials']);
                return;
            }

            //MARK:TODO: Handle other token types like 'single_use' and 'refresh' in the future

            // We will only handle 'single' token type for now
            if (strtolower($token_type) !== 'single') {
                http_response_code(400);
                echo json_encode(['status' => 'failed', 'msg' => 'Invalid token_type, only "single" supported']);
                return;
            }

            // Single token type configuration
            $token = null;
            $expires = time() + 3600; // Set expiration time to 1 hour from now
            $tokenObj = new Single_JwtToken($user['username'], $expires, $user['perm']); // Single token class
            $token = $tokenObj->issueToken();

            // Update the database with the generated token and its type
            $update = $db->prepare('UPDATE users SET valid_token = ?, valid_token_type = ? WHERE username = ?');
            $update->bindValue(1, $token);
            $update->bindValue(2, 'single'); // Only 'single' for now
            $update->bindValue(3, $user['username']);
            $update->execute();

            // Return success response with the token and expiration time
            echo json_encode([
                'status' => 'success',
                'token_type' => 'single',
                'expires' => $expires,
                'token' => $token
            ]);
            return;

            break;

        default:
            http_response_code(400);
            echo json_encode(['status' => 'failed', 'msg' => 'Invalid token type']);
            return;
    }
}