<?php

require_once('permissions.php');

class JwtToken {
    protected static $secretKey = 'your-secret-key';

    protected $usr;  // User ID
    protected $iat;  // Issued At time (timestamp)
    protected $exp;  // Expiration time (timestamp)
    protected $tt;   // Token Type
    protected $perm; // Permissions string

    // Constructor to initialize the token attributes
    public function __construct($usr, $exp, $tt, $perm) {
        $this->usr = $usr;
        $this->iat = time();
        $this->exp = $exp;
        $this->tt = $tt;
        $this->perm = $perm;
    }

    // Method to issue the JWT
    public function issueToken() {
        $payload = [
            'usr'  => $this->usr,
            'iat'  => $this->iat,
            'exp'  => $this->exp,
            'tt'   => $this->tt,
            'perm' => $this->perm
        ];

        return $this->encodeJWT($payload);
    }

    // Static method to validate the token
    public static function validateToken($token) {
        $decoded = self::decodeJWT($token);

        if (!$decoded || $decoded['exp'] < time()) {
            return false; // Token is either invalid or expired
        }

        // If the tokentype is single-use (tt = 0), invalidate it
        /*
        if ($decoded['tt'] == 0) {
            invalidate_token($token);
        } 
        */   

        return $decoded;
    }

    public static function checkPermission($token, $required_permission) {
        // Decode the token
        $decoded = self::validateToken($token);
        if (!$decoded) {
            return false;
        }

        return self::checkDecodedPermission($decoded_token, $required_permission);
    }

    // Static check if a permission is valid
    public static function checkDecodedPermission($decoded_token, $permission) {
        return permissionInPermissionDigits($decoded_token['perm'], $permission);
    }

    // Encode the JWT using the header, payload, and signature
    protected function encodeJWT($payload) {
        // Header part
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));

        // Payload part
        $payload = base64_encode(json_encode($payload));

        // Signature part
        $signature = hash_hmac('sha256', "$header.$payload", self::$secretKey, true);
        $signature = base64_encode($signature);

        // Return the complete JWT token ("<header>.<payload>.<signature>")
        return "$header.$payload.$signature";
    }

    // Decode the JWT and verify signature
    protected static function decodeJWT($jwt) {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) return false; // Invalid JWT format

        list($header, $payload, $signature) = $parts;

        // Decode base64 and decode JSON payload
        $header = json_decode(base64_decode($header), true);
        $payload = json_decode(base64_decode($payload), true);

        // Generate the expected signature and compare with the provided signature
        $validSignature = base64_encode(hash_hmac('sha256', "$parts[0].$parts[1]", self::$secretKey, true));

        if ($validSignature === $signature) {
            return $payload;
        } else {
            return false;
        }
    }
}

// Token type `single` (extends JwtToken class)
class Single_JwtToken extends JwtToken {
    public function __construct($usr, $exp, $perm) {
        parent::__construct($usr, $exp, 1, $perm); // tt = 1 for type `single`
    }

    public static function validateToken($token) {
        return parent::validateToken($token);
    }
}

// Token type `single-use` (extends JwtToken class)
class SingleUse_JwtToken extends JwtToken {
    public function __construct($usr, $exp, $perm) {
        parent::__construct($usr, $exp, 0, $perm); // tt = 0 for type `single-use`
    }

    public static function validateToken($token) {
        return parent::validateToken($token);
    }
}

//MARK:TODO: Add classes for `pair`, and `refresh` token types