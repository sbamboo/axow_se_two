<?php

function get_post_data() {
    $data = json_decode(file_get_contents('php://input'), true);

    // if data is empty use $_POST
    if (empty($data)) {
        $data = $_POST;
    }

    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    return $data;
}

function get_auth_header_token() {
    // Get the token from the request headers
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? '';
    
    if (empty($auth_header)) {
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'failed', 'msg' => 'Authorization header missing']);
        exit;
    }
    
    // if " " in the token, split it
    $type = null;
    $token = null;
    if (strpos($auth_header, ' ') !== false) {
        list($type, $token) = explode(' ', $auth_header, 2);
    } else {
        $token = $auth_header;
    }
    
    if (!$token || $token === null || preg_replace('/\s+/', '', $token) == '') {
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'failed', 'msg' => 'Empty token in Authorization header']);
        exit;
    }

    // return [$type, $token];
    $data = [
        'type' => $type,
        'token' => $token
    ];
    return $data;
}