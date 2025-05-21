<?php
static $SECRETS = [
    "jwt_signing_key" => "your-secret-key",

    "single_token_expiration" => 3600, // 1 hour
    "single_use_token_expiration" => 1800, // 30 minutes
    "pair_token_expiration" => 300, // 5 minutes
    "refresh_token_expiration" => 86400, // 1 day

    "url_preview_default_ttl" => 86400, // 1 day
];