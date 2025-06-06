<?php
static $GENERAL_CONFIG = [
    "url_preview_default_ttl" => 86400, // 1 day
    "url_preview_max_ttl" => 604800, // 7 days
    "url_preview_multi_delim" => ";",

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

    "oembed_generic_url" => "https://noembed.com/embed?url=%s"
];