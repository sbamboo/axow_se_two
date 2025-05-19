<?php
header("Content-Type: application/json");

require_once("../_php_common_/env.php");
require_once("../_php_common_/responders.php");
require_once("../_php_common_/requests.php");
require_once("../_php_common_/db.php");

$DEFAULT_TTL  = 86400; // 1 day

$req_data = get_request_body();

$request_url = $req_data["url"] ?? null;
if ($request_url === null) {
    req_send(false, "Invalid request, missing url", 400); // HTTP code 400 : Bad Request
}

// if method is GET ensure url is not url-encoded
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $request_url = urldecode($request_url);
}

/*
    This endpoint fetches the OpenGraph/TwitterCard data for a given url.
    And returns:
    {
        "type": "twitter"/"opengraph"/"oembed"/"none",
        "title": "<optional:string>",
        "description": "<optional:string>",
        "image": "<optional:string>",
        "url": "<optional:string>",
        "type": "<optional:string=type/card>"
    }

    (if fields are are not avaliable they are left as null)

    If no preview is avaliable and resource is HTML we make attempt:
        <title>: Used as the headline
        <meta name="description">: Used as a summary
        <link rel="icon"> or similar
        <img>: First large image might be used
    Or finally we look for first visible <h1>/<h2> tag, <p> tag, <link rel="icon"> tag and first <img> tag
    
*/

$use_client_user_agent = $req_data["client-user-agent"] ?? false;
$user_agent = "MetadataFetcher/1.0";
if ($use_client_user_agent && isset($_SERVER["HTTP_USER_AGENT"])) {
    $user_agent = $_SERVER["HTTP_USER_AGENT"];
}

$cache_ttl    = intval($req_data["cache-time"] ?? $DEFAULT_TTL);
$skip_cache   = ($cache_ttl === 0);

list($db, $success, $msg, $http_code) = get_db();
if (!$success) {
    req_send(false, $msg, $http_code);
}

function cache_clean_expired($db) {
    $db->query("DELETE FROM url_metadata_cache WHERE expires_at < NOW()");
}

function cache_check($db, $url) {
    $url_hash = md5($url);
    $stmt = $db->prepare("SELECT metadata,expires_at FROM url_metadata_cache WHERE url_hash = ? AND expires_at > NOW()");
    $stmt->bind_param('s', $url_hash);
    $stmt->execute();
    $stmt->bind_result($metadata_json, $expires_at);
    $stmt->fetch();
    $stmt->close();
    return [$metadata_json, $expires_at];
}

function cache_store($db, $url, $metadata_json, $ttl_seconds) {
    $url_hash = md5($url);
    $expires_at = gmdate('Y-m-d H:i:s', time() + $ttl_seconds);
    $stmt = $db->prepare(
        "INSERT INTO url_metadata_cache (url_hash, url, metadata, expires_at)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
             metadata = VALUES(metadata),
             expires_at = VALUES(expires_at),
             updated_at = CURRENT_TIMESTAMP"
    );
    $stmt->bind_param('ssss', $url_hash, $url, $metadata_json, $expires_at);
    $stmt->execute();
    $stmt->close();
}

function fetch_html($url, $user_agent) {
    $options = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: " . $user_agent . "\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $html = file_get_contents($url, false, $context);

    if ($html === false) {
        return null;
    } else {
        return $html;
    }
}

function parse_metadata($html, $base_url) {
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $loaded = $doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors(false);

    if (!$loaded) {
        return null;
    }

    $xpath = new DOMXPath($doc);

    $meta = [];
    $meta["type"] = "none";
    $meta["title"] = null;
    $meta["description"] = null;
    $meta["image"] = null;
    $meta["url"] = $base_url;
    $meta["card_type"] = null;

    // OpenGraph
    $og_tags = $xpath->query("//meta[starts-with(@property, 'og:')]");
    if ($og_tags && $og_tags->length > 0) {
        $meta["type"] = "opengraph";
        foreach ($og_tags as $tag) {
            $prop = $tag->getAttribute("property");
            $content = $tag->getAttribute("content");
            if ($prop === "og:title") {
                $meta["title"] = $content;
            } else if ($prop === "og:description") {
                $meta["description"] = $content;
            } else if ($prop === "og:image") {
                $meta["image"] = resolve_url($content, $base_url);
            } else if ($prop === "og:url") {
                $meta["url"] = $content;
            } else if ($prop === "og:type") {
                $meta["card_type"] = $content;
            }
        }
        return $meta;
    }

    // Twitter Card
    $tw_tags = $xpath->query("//meta[starts-with(@name, 'twitter:')]");
    if ($tw_tags && $tw_tags->length > 0) {
        $meta["type"] = "twitter";
        foreach ($tw_tags as $tag) {
            $name = $tag->getAttribute("name");
            $content = $tag->getAttribute("content");
            if ($name === "twitter:title") {
                $meta["title"] = $content;
            } else if ($name === "twitter:description") {
                $meta["description"] = $content;
            } else if ($name === "twitter:image") {
                $meta["image"] = resolve_url($content, $base_url);
            } else if ($name === "twitter:card") {
                $meta["card_type"] = $content;
            }
        }
        return $meta;
    }

    // oEmbed
    $oembed_tags = $xpath->query("//link[@type='application/json+oembed' or @type='text/json+oembed']");
    if ($oembed_tags && $oembed_tags->length > 0) {
        $href = $oembed_tags[0]->getAttribute("href");
        $json = file_get_contents($href);

        if ($json !== false) {
            $data = json_decode($json, true);
            $meta["type"] = "oembed";

            if (isset($data["title"])) {
                $meta["title"] = $data["title"];
            }

            if (isset($data["description"])) {
                $meta["description"] = $data["description"];
            }

            if (isset($data["thumbnail_url"])) {
                $meta["image"] = resolve_url($data["thumbnail_url"], $base_url);
            }

            if (isset($data["type"])) {
                $meta["card_type"] = $data["type"];
            }

            return $meta;
        }
    }

    // Fallbacks
    $titles = $doc->getElementsByTagName("title");
    if ($titles->length > 0) {
        $meta["title"] = trim($titles[0]->textContent);
    }

    $desc_meta = $xpath->query("//meta[@name='description']");
    if ($desc_meta->length > 0) {
        $meta["description"] = $desc_meta[0]->getAttribute("content");
    }

    $images = $doc->getElementsByTagName("img");
    for ($i = 0; $i < $images->length; $i++) {
        $src = $images->item($i)->getAttribute("src");
        if (!empty($src)) {
            $meta["image"] = resolve_url($src, $base_url);
            break;
        }
    }

    if ($meta["title"] === null) {
        $alts = $xpath->query("//h1 | //h2 | //p");
        if ($alts->length > 0) {
            $meta["title"] = trim($alts[0]->textContent);
        }
    }

    return $meta;
}

function resolve_url($relative, $base) {
    $parsed = parse_url($relative);
    if ($parsed !== false && isset($parsed["scheme"])) {
        return $relative;
    } else {
        return rtrim($base, "/") . "/" . ltrim($relative, "/");
    }
}


cache_clean_expired($db);

$metadata = null;

if (!$skip_cache) {
    list($cached, $stored_expires_at) = cache_check($db, $request_url);
    if ($cached !== null) {
        $metadata = json_decode($cached, true);
        $metadata["was_cached"] = true;
        $metadata["cache_expiry"] = $stored_expires_at !== null ? strtotime($stored_expires_at) : null;
    }
}

if ($metadata === null) {
    $html = fetch_html($request_url, $user_agent);
    if ($html === null) {
        req_send(false, "Unable to fetch the provided URL", 500); // HTTP code 500 : Internal Server Error
    }

    $metadata = parse_metadata($html, $request_url);
    if ($metadata === null) {
        req_send(false, "Failed to parse metadata from content", 500); // HTTP code 500 : Internal Server Error
    }
    
    if (!$skip_cache) {
        cache_store($db, $request_url, json_encode($metadata), $cache_ttl);
    }

    $metadata["was_cached"] = false;
    $metadata["cache_expiry"] = null;
}

http_response_code(200); // HTTP code 200 : OK
$metadata["status"] = "success";
$metadata["msg"] = "Metadata fetched successfully";
echo json_encode($metadata);
exit;
