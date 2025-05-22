<?php
static $SECRETS = [
    "jwt_signing_key" => "your-secret-key",

    "single_token_expiration" => 3600, // 1 hour
    "single_use_token_expiration" => 1800, // 30 minutes
    "pair_token_expiration" => 300, // 5 minutes
    "refresh_token_expiration" => 86400, // 1 day

    "url_preview_default_ttl" => 86400, // 1 day


    "oembed_urls_per_provider" => [
        "youtube"       => "https://www.youtube.com/oembed?url=%s&format=json",
        "vimeo"         => "https://vimeo.com/api/oembed.json?url=%s",
        "dailymotion"   => "https://www.dailymotion.com/services/oembed?url=%s&format=json",
        "twitter"       => "https://publish.twitter.com/oembed?url=%s",
        "flickr"        => "https://www.flickr.com/services/oembed?url=%s&format=json",
        "tumblr"        => "https://www.tumblr.com/oembed/1.0?url=%s",
        "reddit"        => "https://www.reddit.com/oembed?url=%s",
        "soundcloud"    => "https://soundcloud.com/oembed?format=json&url=%s",
        "imgur"         => "https://api.imgur.com/oembed?url=%s"
    ],

    "oembed_generic_url" => "https://noembed.com/embed?url=%s",
];