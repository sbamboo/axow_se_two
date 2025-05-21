<?php
/**
 * @file url_preview/fetch.php
 * @brief This file contains the logic to fetch a URL preview from OpenGraph, TwitterCard or oembed.
 */

/*
    This endpoint fetches the OpenGraph/TwitterCard data for a given url.
    And returns:
    {
        "format": "twitter"/"opengraph"/"oembed"/"none",
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

require_once("../_php_common_/secret_config.php");
require_once("../_php_common_/db.php"); 

#region Cache Actions
// Function to clean expired cache entries
function cache_clean_expired($db) {
    $query = "DELETE FROM url_previewdata_cache WHERE expires_at < NOW()";
    $stmt = $db->prepare($query);
    if ($stmt === false) {
        return false;
    }
    $stmt->execute();
    return true;
}

// Function to check if a URL is in the cache and if so return its previewdata and expiration time
function cache_check($db, $url) {
    $url_hash = md5($url);
    $stmt = $db->prepare("SELECT previewdata,expires_at FROM url_previewdata_cache WHERE url_hash = ? AND expires_at > NOW()");
    $stmt->bind_param('s', $url_hash);
    $stmt->execute();
    $stmt->bind_result($previewdata_json, $expires_at);
    $stmt->fetch();
    $stmt->close();
    if ($previewdata_json) {
        $previewdata = json_decode($previewdata_json, true);
        return [$previewdata, $expires_at];
    } else {
        return [null, null];
    }
}

// Function to store previewdata for a URL in the cache with a specified TTL
function cache_store($db, $url, $previewdata, $ttl_seconds) {
    $url_hash = md5($url);
    $expires_at = gmdate('Y-m-d H:i:s', time() + $ttl_seconds);
    $stmt = $db->prepare(
        "INSERT INTO url_previewdata_cache (url_hash, url, previewdata, expires_at)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
             previewdata = VALUES(previewdata),
             expires_at = VALUES(expires_at),
             updated_at = CURRENT_TIMESTAMP"
    );
    if ($stmt === false) {
        return false;
    }
    $previewdata_json = json_encode($previewdata);
    $stmt->bind_param('ssss', $url_hash, $url, $previewdata_json, $expires_at);
    $stmt->execute();
    $stmt->close();
    return true;
}

#endregion

#region Functions
// Function to fetch the HTML of a url
function fetch_html($url, $user_agent) {
    $options = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: " . $user_agent . "\r\n"
        ]
    ];
    $context = stream_context_create($options);

    $html = @file_get_contents($url, false, $context);

    // get_last_error() and returns [null, <msg>]
    if ($html === false) {
        $error = error_get_last();
        if ($error !== null) {
            return [null, rtrim(preg_replace('/^file_get_contents\(.*?\):\s*/', '', $error["message"] ?? "Unknown error"), "\r\n")];
        } else {
            return [null, "Unknown error occurred while fetching the URL"];
        }
    }
    
    if ($html === false) {
        return [null,null];
    } else {
        return [$html,null];
    }
}

// Function to resolve relative URLs to absolute URLs
function resolve_url($relative, $base) {
    $parsed = parse_url($relative);
    if ($parsed !== false && isset($parsed["scheme"])) {
        return $relative;
    } else {
        return rtrim($base, "/") . "/" . ltrim($relative, "/");
    }
}

// Main HTML parser function
function parse_html_for_preview($html, $base_url) {
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $loaded = $doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors(false);

    if (!$loaded) {
        return null;
    }

    $xpath = new DOMXPath($doc);

    $preview = [];
    $preview["format"] = "none";
    $preview["title"] = null;
    $preview["description"] = null;
    $preview["image"] = null;
    $preview["url"] = $base_url;
    $preview["type"] = null;

    // OpenGraph
    $og_tags = $xpath->query("//meta[starts-with(@property, 'og:')]");
    if ($og_tags && $og_tags->length > 0) {
        $preview["format"] = "opengraph";
        foreach ($og_tags as $tag) {
            $prop = $tag->getAttribute("property");
            $content = $tag->getAttribute("content");
            if ($prop === "og:title") {
                $preview["title"] = $content;
            } else if ($prop === "og:description") {
                $preview["description"] = $content;
            } else if ($prop === "og:image") {
                $preview["image"] = resolve_url($content, $base_url);
            } else if ($prop === "og:url") {
                $preview["url"] = $content;
            } else if ($prop === "og:type") {
                $preview["type"] = $content;
            }
        }
        return $preview;
    }

    // Twitter Card
    $tw_tags = $xpath->query("//meta[starts-with(@name, 'twitter:')]");
    if ($tw_tags && $tw_tags->length > 0) {
        $preview["format"] = "twitter";
        foreach ($tw_tags as $tag) {
            $name = $tag->getAttribute("name");
            $content = $tag->getAttribute("content");
            if ($name === "twitter:title") {
                $preview["title"] = $content;
            } else if ($name === "twitter:description") {
                $preview["description"] = $content;
            } else if ($name === "twitter:image") {
                $preview["image"] = resolve_url($content, $base_url);
            } else if ($name === "twitter:card") {
                $preview["type"] = $content;
            }
        }
        return $preview;
    }

    // oEmbed
    $oembed_tags = $xpath->query("//link[@type='application/json+oembed' or @type='text/json+oembed']");
    if ($oembed_tags && $oembed_tags->length > 0) {
        $href = $oembed_tags[0]->getAttribute("href");
        $json = file_get_contents($href);

        if ($json !== false) {
            $data = json_decode($json, true);
            $preview["format"] = "oembed";

            if (isset($data["title"])) {
                $preview["title"] = $data["title"];
            }

            if (isset($data["description"])) {
                $preview["description"] = $data["description"];
            }

            if (isset($data["thumbnail_url"])) {
                $preview["image"] = resolve_url($data["thumbnail_url"], $base_url);
            }

            if (isset($data["type"])) {
                $preview["type"] = $data["type"];
            }

            return $preview;
        }
    }

    // Fallbacks
    $titles = $doc->getElementsByTagName("title");
    if ($titles->length > 0) {
        $preview["title"] = trim($titles[0]->textContent);
    }

    $desc_preview = $xpath->query("//meta[@name='description']");
    if ($desc_preview->length > 0) {
        $preview["description"] = $desc_preview[0]->getAttribute("content");
    }

    // Further fallback for description: first <p>, <b>, <i>
    if ($preview["description"] === null || empty($preview["description"])) {
        $desc_fallback_nodes = $xpath->query("//p | //b | //i");
        if ($desc_fallback_nodes->length > 0) {
            foreach ($desc_fallback_nodes as $node) {
                $text = trim($node->textContent);
                if ($text !== '') {
                    $preview["description"] = $text;
                    break;  // Use the first non-empty found
                }
            }
        }
    }

    $images = $doc->getElementsByTagName("img");
    for ($i = 0; $i < $images->length; $i++) {
        $src = $images->item($i)->getAttribute("src");
        if (!empty($src)) {
            $preview["image"] = resolve_url($src, $base_url);
            break;
        }
    }

    if ($preview["title"] === null) {
        $alts = $xpath->query("//h1 | //h2 | //p");
        if ($alts->length > 0) {
            $preview["title"] = trim($alts[0]->textContent);
        }
    }

    return $preview;
}

// Function to get the url preview data
function fetch_url_preview($url, $user_agent, $ttl_seconds) {
    list($db, $success, $error_message, $http_code) = get_db();
    if (!$success) {
        return [null, $success, $error_message, $http_code];
    }

    $skip_cache = false;
    if ($ttl_seconds <= 0) {
        $skip_cache = true;
    }

    if (!$skip_cache) {
        $cache_cleaned = cache_clean_expired($db);
        if (!$cache_cleaned) {
            return [null, false, "Failed to clean cache", 500]; // HTTP code 500 : Internal Server Error
        }

        list($cached_preview, $stored_expires_at) = cache_check($db, $url);
        if ($cached_preview) {
            $cached_preview["was_cached"] = true;
            $cached_preview["cache_expiry"] = $stored_expires_at !== null ? strtotime($stored_expires_at) : null;
            return [$cached_preview, true, "Preview fetched successfully", 200]; // HTTP code 200 : OK
        }
    }

    try {
        list($html, $error_message) = fetch_html($url, $user_agent);
        if ($html === null) {
            return [null, false, $error_message, 500]; // HTTP code 500 : Internal Server Error
        }
    } catch (Exception $e) {
        return [null, false, $e->getMessage(), 500]; // HTTP code 500 : Internal Server Error
    }

    $preview = parse_html_for_preview($html, $url);
    if ($preview === null) {
        return [null, false, "Failed to parse preview from content", 500]; // HTTP code 500 : Internal Server Error
    }

    if (!$skip_cache) {
        $cache_stored = cache_store($db, $url, $preview, $ttl_seconds);
        if (!$cache_stored) {
            return [null, false, "Failed to store preview in cache", 500]; // HTTP code 500 : Internal Server Error
        }
    }

    $preview["was_cached"] = false;
    $preview["cache_expiry"] = null;

    return [$preview, true, "Preview fetched successfully", 200]; // HTTP code 200 : OK
}

#endregion

#region Responders
function req_fetch_url_preview($req_data) {
    global $SECRETS;

    $unescape_json = isset($req_data["unescape"]) ? true : false;
    $unescaped_unicode_json = isset($req_data["unescaped_unicode"]) ? true : false;

    $request_url = $req_data["url"] ?? null;
    if ($request_url === null) {
        http_response_code(400); // HTTP code 400 : Bad Request
        echo json_encode([
            "status" => "failed",
            "msg" => "Invalid request, missing url"
        ]);
        die(); //MARK: Should we exit instead?
    }

    // if method is GET ensure url is not url-encoded
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        $request_url = urldecode($request_url);
    }

    $use_client_user_agent = $req_data["client-user-agent"] ?? false;
    $user_agent = "MetadataFetcher/1.0";
    if ($use_client_user_agent && isset($_SERVER["HTTP_USER_AGENT"])) {
        $user_agent = $_SERVER["HTTP_USER_AGENT"];
    }

    $ttl_seconds = $SECRETS["url_preview_default_ttl"];
    if (isset($req_data["cache-ttl"])) {
        $ttl_seconds = intval($req_data["cache-ttl"]);
    }
    list($preview, $success, $error_message, $http_code) = fetch_url_preview($request_url, $user_agent, $ttl_seconds);
    
    $toret = [
        "status" => $success ? "success" : "failed",
        "msg" => $success ? "Preview fetched successfully" : $error_message
    ] + $preview;
    http_response_code($http_code);

    // echo json_encode($toret);
    // echo json_encode($toret, JSON_UNESCAPED_SLASHES);
    // echo json_encode($toret, JSON_UNESCAPED_UNICODE);
    // echo json_encode($toret, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($unescape_json == true && $unescaped_unicode_json == true) {
        echo json_encode($toret, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } else if ($unescape_json == true) {
        echo json_encode($toret, JSON_UNESCAPED_SLASHES);
    } else if ($unescaped_unicode_json == true) {
        echo json_encode($toret, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode($toret);
    }

    die(); //MARK: Should we exit instead?
}
#endregion