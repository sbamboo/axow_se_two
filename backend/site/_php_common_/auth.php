<?php
require_once('db.php');
require_once('jwt.php');
require_once('permissions.php');
require_once('request.php');

function get_secure_hash($password) {
    // Hash the password using a secure hashing algorithm
    return password_hash($password, PASSWORD_DEFAULT);
}

function compare_secure_hash($password, $hash) {
    // Compare the password with the hash
    return password_verify($password, $hash);
}

function validate_token($token) {
    // Validate the token (Check if it's valid and not expired)
    $decoded = JwtToken::validateToken($token);

    if (!$decoded) {
        return false;
    }

    return $decoded;
}

function validate_token_permission($token, $permission) {
    $decoded = JwtToken::validateToken($token);

    if (!$decoded) {
        return false;
    }

    if (!JwtToken::checkPermission($decoded, $permission)) {
        return false;
    }

    return true;
}

function validate_decoded_token_permission($decoded_token, $permission) {
    if (!JwtToken::checkPermission($decoded_token, $permission)) {
        return false;
    }
    return true;
}

function invalidate_token($token) {

    $toRet = [
        'http_code' => 200,
        'status' => 'success',
        'msg' => ''
    ];

    $decoded = JwtToken::validateToken($token);

    // Already invalid token
    if (!$decoded) {
        $toRet['http_code'] = 200;
        $toRet['status'] = 'success';
        $toRet['msg'] = 'Token already invalid';
        return $toRet;
    }

    if ($decoded['tt'] == 1 || $decoded['tt'] == 0) { // 0 single-use, 1 single
        // Find the user in the database based on the token
        $db = get_db();
        $stmt = $db->prepare('SELECT * FROM users WHERE valid_token = ?');
        $stmt->bind_param('s', $token);
        if (!$stmt->execute()) {
            $toRet['http_code'] = 500;
            $toRet['status'] = 'failed';
            $toRet['msg'] = 'Database query execution failed';
        }
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Check if any user was found, if not no one had the token so we success already invalid
        if (!$user) {
            $toRet['http_code'] = 200;
            $toRet['status'] = 'success';
            $toRet['msg'] = 'Token already invalid';
            return $toRet;
        }

        // Invalidate the token in the database
        $update = $db->prepare('UPDATE users SET valid_token = NULL, valid_token_type = NULL, valid_refresh_token = NULL WHERE ID = ?');
        $update->bind_param('i', $user['ID']);
        if (!$update->execute()) {
            $toRet['http_code'] = 500;
            $toRet['status'] = 'failed';
            $toRet['msg'] = 'Database update failed';
            return $toRet;
        }
        $result = $update->get_result();
        /*
        if ($result === false) {
            $toRet['http_code'] = 500;
            $toRet['status'] = 'failed';
            $toRet['msg'] = 'Database update failed';
            return $toRet;
        }
        */

        $toRet['msg'] = 'Token invalidated';
        return $toRet;

    } else {
        $toRet['http_code'] = 400;
        $toRet['status'] = 'failed';
        $toRet['msg'] = 'Invalid token type';
        return $toRet;
    }

    //MARK:TODO: Handle other token types like pair
}

function invalidate_single_use_token($token) {

    $toRet = [
        'http_code' => 200,
        'status' => 'success',
        'msg' => ''
    ];

    $decoded = JwtToken::validateToken($token);

    // Already invalid token
    if (!$decoded) {
        $toRet['http_code'] = 200;
        $toRet['status'] = 'success';
        $toRet['msg'] = 'Token already invalid';
        return $toRet;
    }

    if ($decoded['tt'] == 0) { // 0 single-use
        // Find the user in the database based on the token
        $db = get_db();
        $stmt = $db->prepare('SELECT * FROM users WHERE valid_token = ?');
        $stmt->bind_param('s', $token);
        if (!$stmt->execute()) {
            $toRet['http_code'] = 500;
            $toRet['status'] = 'failed';
            $toRet['msg'] = 'Database query execution failed';
        }
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Check if any user was found, if not no one had the token so we success already invalid
        if (!$user) {
            $toRet['http_code'] = 200;
            $toRet['status'] = 'success';
            $toRet['msg'] = 'Token already invalid';
            return $toRet;
        }

        // Invalidate the token in the database
        $update = $db->prepare('UPDATE users SET valid_token = NULL, valid_token_type = NULL, valid_refresh_token = NULL WHERE ID = ?');
        $update->bind_param('i', $user['ID']);
        if (!$update->execute()) {
            $toRet['http_code'] = 500;
            $toRet['status'] = 'failed';
            $toRet['msg'] = 'Database update failed';
            return $toRet;
        }
        $result = $update->get_result();
        /*
        if ($result === false) {
            $toRet['http_code'] = 500;
            $toRet['status'] = 'failed';
            $toRet['msg'] = 'Database update failed';
            return $toRet;
        }
        */

        $toRet['msg'] = 'Token invalidated';
        return $toRet;

    }
}

function auto_invalidate_single_use_token() {
    list('type' => $type, 'token' => $token) = get_auth_header_token();
    return invalidate_single_use_token($token);
}

function any_has_token($token) {
    // Find the user in the database based on the token
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM users WHERE valid_token = ?');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Check if any user was found, if not no one had the token so we success already invalid
    if (!$user) {
        return false;
    } else {
        return true;
    }
}

//MARK: UserID Functions

function user_has_valid_token($userid) {
    $db = get_db();
    $stmt = $db->prepare('SELECT valid_token, valid_token_type FROM users WHERE id = ?');
    $stmt->bind_param('i', $userid);
    if (!$stmt->execute()) {
        return [false, null];
    }
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || empty($user['valid_token']) || empty($user['valid_token_type'])) {
        return [false, null];
    }

    $decoded = JwtToken::validateToken($user['valid_token']);

    if ($decoded) {
        return [true, $user['valid_token_type']];
    } else {
        user_invalidate_token($userid);
        return [false, null];
    }
}

function user_invalidate_token($userid) {
    $db = get_db();

    $stmt = $db->prepare('SELECT valid_token FROM users WHERE id = ?');
    $stmt->bind_param('i', $userid);
    if (!$stmt->execute()) {
        return false;
    }
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || empty($user['valid_token'])) {
        return true;
    }

    invalidate_token($user['valid_token']);

    return true;
}

function user_invalidate_single_use_token($userid) {
    $db = get_db();

    $stmt = $db->prepare('SELECT valid_token FROM users WHERE id = ?');
    $stmt->bind_param('i', $userid);
    if (!$stmt->execute()) {
        return false;
    }
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || empty($user['valid_token'])) {
        return true;
    }

    invalidate_single_use_token($user['valid_token']);

    return true;
}

function user_grant_permission($userid, $permission, $invalidate_token = true) {
    $db = get_db();

    $stmt = $db->prepare('SELECT permissions, valid_token FROM users WHERE id = ?');
    $stmt->bind_param('i', $userid);
    if (!$stmt->execute()) {
        return false;
    }
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        return false;
    }

    $current_permissions = empty($user['permissions']) ? [] : explode('; ', $user['permissions']);

    if (in_array($permission, $current_permissions)) {
        return true;
    }

    $current_permissions[] = $permission;
    $new_permissions = implode('; ', $current_permissions);

    $update_stmt = $db->prepare('UPDATE users SET permissions = ? WHERE id = ?');
    $update_stmt->bind_param('si', $new_permissions, $userid);
    if (!$update_stmt->execute()) {
        return false;
    }

    if ($invalidate_token && !empty($user['valid_token'])) {
        user_invalidate_token($userid);
    }

    return true;
}

function user_revoke_permission($userid, $permission, $invalidate_token = true) {
    $db = get_db();

    $stmt = $db->prepare('SELECT permissions, valid_token FROM users WHERE id = ?');
    $stmt->bind_param('i', $userid);
    if (!$stmt->execute()) {
        return false;
    }
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        return false;
    }

    $current_permissions = empty($user['permissions']) ? [] : explode('; ', $user['permissions']);

    $key = array_search($permission, $current_permissions);
    if ($key !== false) {
        unset($current_permissions[$key]);
    } else {
        return true;
    }

    $new_permissions = implode('; ', array_values($current_permissions));

    $update_stmt = $db->prepare('UPDATE users SET permissions = ? WHERE id = ?');
    $update_stmt->bind_param('si', $new_permissions, $userid);
    if (!$update_stmt->execute()) {
        return false;
    }

    if ($invalidate_token && !empty($user['valid_token'])) {
        user_invalidate_token($userid);
    }

    return true;
}

function user_query_permissions($userid) {
    $db = get_db();

    $stmt = $db->prepare('SELECT permissions FROM users WHERE id = ?');
    $stmt->bind_param('i', $userid);
    if (!$stmt->execute()) {
        return false;
    }
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        return false;
    }

    if (empty($user['permissions'])) {
        return [];
    }

    return explode('; ', $user['permissions']);
}

function user_has_permission($userid, $permission) {
    $permissions = user_query_permissions($userid);

    if ($permissions === false) {
        return false;
    }

    return in_array($permission, $permissions);
}

function user_get_new_token($token_type, $userid) {
    /*
     0  single-use  One-time token.
     1  single      Only one active token, no refresh key.
     2  pair        Requires a refresh token to refresh.
     3  refresh     Used to refresh a pair token.
    */
    $toRet = [
        'http_code' => 200,
        'status' => 'success',
        'msg' => ''
    ];

    if (!isset($token_type)) {
        $toRet['http_code'] = 400;
        $toRet['status'] = 'failed';
        $toRet['msg'] = 'Missing token_type';
        return $toRet;
    }

    // Validate required fields
    if (!isset($data['username'], $data['password'])) {
        http_response_code(400);
        echo json_encode(['status' => 'failed', 'msg' => 'Missing username or password']);
        return;
    }

    // Get the user from the database
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM users WHERE ID = ?');
    $stmt->bind_param('i', $userid);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['status' => 'failed', 'msg' => 'Database query execution failed']);
        return;
    }
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if (!$user) {
        http_response_code(404);
        echo json_encode(['status' => 'failed', 'msg' => 'User not found']);
        return;
    }

    // Check if the user exists and password matches
    if (!$user || compare_secure_hash($data['password'], $user['password_hash'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'failed', 'msg' => 'Invalid credentials']);
        return;
    }

    // Get permissions
    $permissiondigit_string = "000000000";
    if (!empty($user['permissions'])) {
        $permissiondigit_string = permissionsStringToPermissionDigits($user['permissions']);
    }

    $token_type_lower = strtolower($token_type);

    if ($token_type_lower === 'single') {
        // Configure
        $token = null;
        $expires = time() + 3600; // 1h
        $tokenObj = new Single_JwtToken($user['ID'], $expires, $permissiondigit_string);
        $token = $tokenObj->issueToken();

        // Update the database with the generated token and its type
        $update = $db->prepare('UPDATE users SET valid_token = ?, valid_token_type = ? WHERE username = ?');
        $update->bind_param('sss', $token, $token_type_lower, $user['username']);
        $update->execute();

        $toRet['token_type'] = $token_type_lower;
        $toRet['expires'] = $expires;
        $toRet['token'] = $token;
        $toRet['msg'] = 'Token generated successfully';
        $toRet['has_full_access'] = in_array('*', explode('; ', $user['permissions']));

    } else if ($token_type_lower === 'single-use') {
        // Configure
        $token = null;
        $expires = time() + 3600; // 1h
        $tokenObj = new SingleUse_JwtToken($user['ID'], $expires, $permissiondigit_string);
        $token = $tokenObj->issueToken();

        // Update the database with the generated token and its type
        $update = $db->prepare('UPDATE users SET valid_token = ?, valid_token_type = ? WHERE username = ?');
        $update->bind_param('sss', $token, $token_type_lower, $user['username']);
        $update->execute();

        $toRet['token_type'] = $token_type_lower;
        $toRet['expires'] = $expires;
        $toRet['token'] = $token;
        $toRet['msg'] = 'Token generated successfully';
        $toRet['has_full_access'] = in_array('*', explode('; ', $user['permissions']));

    } else {
        $toRet['http_code'] = 400;
        $toRet['status'] = 'failed';
        $toRet['msg'] = 'Invalid token type';
        return $toRet;
    }

    //MARK:TODO: Add other token types handling pair

    return $toRet;
}

//MARK: Request Functions

function req_get_new_token($token_type, $data) {

    /*
     0  single-use  One-time token.
     1  single      Only one active token, no refresh key.
     2  pair        Requires a refresh token to refresh.
     3  refresh     Used to refresh a pair token.
    */

    // Validate the token type input
    if (!isset($token_type)) {
        http_response_code(400);
        echo json_encode(['status' => 'failed', 'msg' => 'Missing token_type']);
        return;
    }

    // Validate required fields
    if (!isset($data['username'], $data['password'])) {
        http_response_code(400);
        echo json_encode(['status' => 'failed', 'msg' => 'Missing username or password']);
        return;
    }

    // Get the user from the database
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->bind_param('s', $data['username']);

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['status' => 'failed', 'msg' => 'Database query execution failed']);
        return;
    }

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Check if the user exists and password matches
    if (!$user || compare_secure_hash($data['password'], $user['password_hash']) === false) {
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'failed', 'msg' => 'Invalid credentials']);
        return;
    }

    // Get permissions
    $permissiondigit_string = "000000000";
    if (!empty($user['permissions'])) {
        $permissiondigit_string = permissionsStringToPermissionDigits($user['permissions']);
    }

    $token_type_lower = strtolower($token_type);
    if ($token_type_lower === 'single') {
        // Configure
        $token = null;
        $expires = time() + 3600; // 1h
        $tokenObj = new Single_JwtToken($user['ID'], $expires, $permissiondigit_string);
        $token = $tokenObj->issueToken();

        // Update the database with the generated token and its type
        $update = $db->prepare('UPDATE users SET valid_token = ?, valid_token_type = ? WHERE username = ?');
        $update->bind_param('sss', $token, $token_type_lower, $user['username']);
        $update->execute();

        // Return success response with the token and expiration time
        echo json_encode([
            'status' => 'success',
            'token_type' => $token_type_lower,
            'expires' => $expires,
            'token' => $token,
            'msg' => 'Token generated successfully',
            'has_full_access' => in_array('*', explode('; ', $user['permissions']))
        ]);
        return;

    } else if ($token_type_lower === 'single-use') {
        // Configure
        $token = null;
        $expires = time() + 3600; // 1h
        $tokenObj = new SingleUse_JwtToken($user['ID'], $expires, $permissiondigit_string);
        $token = $tokenObj->issueToken();

        // Update the database with the generated token and its type
        $update = $db->prepare('UPDATE users SET valid_token = ?, valid_token_type = ? WHERE username = ?');
        $update->bind_param('sss', $token, $token_type_lower, $user['username']);
        $update->execute();

        // Return success response with the token and expiration time
        echo json_encode([
            'status' => 'success',
            'token_type' => $token_type_lower,
            'expires' => $expires,
            'token' => $token,
            'msg' => 'Token generated successfully',
            'has_full_access' => in_array('*', explode('; ', $user['permissions']))
        ]);
        return;

    } else {
        http_response_code(400);
        echo json_encode(['status' => 'failed', 'msg' => 'Invalid token type']);
        return;
    }

    //MARK:TODO: Add other token types handling pair
}

function req_validate_token($token) {
    $decoded = validate_token($token);

    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['status' => 'failed', 'msg' => 'Invalid or expired token']);
        return false;
    }

    return $decoded;
}

function req_validate_token_permission($token, $permission) {
    $decoded = JwtToken::validateToken($token);

    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['status' => 'failed', 'msg' => 'Invalid or expired token']);
        return false;
    }

    if (!JwtToken::checkPermission($decoded, $permission)) {
        http_response_code(403);
        echo json_encode(['status' => 'success', 'msg' => 'Token does not have that permission', 'has_permission' => false]);
        return false;
    }

    http_response_code(200);
    echo json_encode(['status' => 'success', 'msg' => 'Token has that permission', 'has_permission' => true]);
    return true;
}

function req_validate_decoded_token_permission($decoded_token, $permission) {
    if (!JwtToken::checkPermission($decoded_token, $permission)) {
        http_response_code(403);
        echo json_encode(['status' => 'success', 'msg' => 'Token does not have that permission', 'has_permission' => false]);
        return false;
    }

    http_response_code(200);
    echo json_encode(['status' => 'success', 'msg' => 'Token has that permission', 'has_permission' => true]);
    return true;
}

function req_invalidate_token($token) {

    $decoded = JwtToken::validateToken($token);

    // Already invalid token
    if (!$decoded) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'msg' => 'Token already invalid']);
        return;
    }

    if ($decoded['tt'] == 1 || $decoded['tt'] == 0) { // 0 single-use, 1 single
        // Find the user in the database based on the token
        $db = get_db();
        $stmt = $db->prepare('SELECT * FROM users WHERE valid_token = ?');
        $stmt->bind_param('s', $token);
        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(['status' => 'failed', 'msg' => 'Database query execution failed']);
            return;
        }
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Check if any user was found, if not no one had the token so we success already invalid
        if (!$user) {
            http_response_code(200);
            echo json_encode(['status' => 'success', 'msg' => 'Token already invalid']);
            return;
        }

        // Invalidate the token in the database
        $update = $db->prepare('UPDATE users SET valid_token = NULL, valid_token_type = NULL, valid_refresh_token = NULL WHERE ID = ?');
        $update->bind_param('i', $user['ID']);
        if (!$update->execute()) {
            http_response_code(500);
            echo json_encode(['status' => 'failed', 'msg' => 'Database update failed']);
            return;
        }
        $result = $update->get_result();
        /*
        if ($result === false) {
            http_response_code(500);
            echo json_encode(['status' => 'failed', 'msg' => 'Database update failed']);
            return;
        }
        */

        http_response_code(200);
        echo json_encode(['status' => 'success', 'msg' => 'Token invalidated']);
        return;

    } else {
        http_response_code(400);
        echo json_encode(['status' => 'failed', 'msg' => 'Invalid token type']);
        return;
    }

    //MARK:TODO: Handle other token types like pair
}

function req_invalidate_single_use_token($token) {

    $decoded = JwtToken::validateToken($token);

    // Already invalid token
    if (!$decoded) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'msg' => 'Token already invalid']);
        return;
    }

    if ($decoded['tt'] == 0) { // single-use
        // Find the user in the database based on the token
        $db = get_db();
        $stmt = $db->prepare('SELECT * FROM users WHERE valid_token = ?');
        $stmt->bind_param('s', $token);
        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(['status' => 'failed', 'msg' => 'Database query execution failed']);
            return;
        }
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Check if any user was found, if not no one had the token so we success already invalid
        if (!$user) {
            http_response_code(200);
            echo json_encode(['status' => 'success', 'msg' => 'Token already invalid']);
            return;
        }

        // Invalidate the token in the database
        $update = $db->prepare('UPDATE users SET valid_token = NULL, valid_token_type = NULL, valid_refresh_token = NULL WHERE ID = ?');
        $update->bind_param('i', $user['ID']);
        if (!$update->execute()) {
            http_response_code(500);
            echo json_encode(['status' => 'failed', 'msg' => 'Database update failed']);
            return;
        }
        $result = $update->get_result();
        /*
        if ($result === false) {
            http_response_code(500);
            echo json_encode(['status' => 'failed', 'msg' => 'Database update failed']);
            return;
        }
        */

        http_response_code(200);
        echo json_encode(['status' => 'success', 'msg' => 'Token invalidated']);
        return;

    }
}

function req_auto_validate_token() {
    list('type' => $type, 'token' => $token) = get_auth_header_token();

    // Validate the token (assignment wise)
    if (any_has_token($token) === false) {
        http_response_code(200); // OK, becase this is validation endpoint
        echo json_encode(['status' => 'failed', 'msg' => 'Invalid or expired token', 'valid' => false]);
        return false;
    }

    // Validate the token (spec wise)
    $decoded = JwtToken::validateToken($token);
    if (!$decoded) {
        http_response_code(200); // OK, becase this is validation endpoint
        echo json_encode(['status' => 'failed', 'msg' => 'Invalid or expired token', 'valid' => false]);
        return false;
    }

    return $decoded;
}