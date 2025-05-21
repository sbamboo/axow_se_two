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
        "favicon": "<optional:string>",
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
function parse_html_for_preview($html, $url) {
    libxml_use_internal_errors(true);                                   // Disable displaying errors from libxml (Used by DOMDocument)
    $doc = new DOMDocument();                                           // Create a new DOMDocument object
    $loaded = $doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING); // Parse the HTML into the DOMDocument, suppressing errors
    libxml_clear_errors();                                              // Clear any errors that were generated during HTML parsing
    libxml_use_internal_errors(false);                                  // Re-enable error reporting for libxml

    // Get $base_url
    $base_url = parse_url($url, PHP_URL_SCHEME) . "://" . parse_url($url, PHP_URL_HOST);
    
    // If the HTML could not be loaded, return null
    if (!$loaded) {
        return null;
    }

    // Define defaults
    $preview = [];
    $preview["format"] = "none";
    $preview["title"] = $url;
    $preview["description"] = null;
    $preview["image"] = null;
    $preview["favicon"] = null;
    $preview["url"] = $url;
    $preview["type"] = null;

    // OpenGraph
    $meta_tags = $doc->getElementsByTagName("meta");
    if ($meta_tags->length > 0) {
        foreach ($meta_tags as $tag) {
            if ($tag->hasAttribute("property")) {
                $prop = $tag->getAttribute("property");
                if (strpos($prop, "og:") === 0) {
                    $preview["format"] = "opengraph";
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
            }
        }
        // If OpenGraph data was found, return early
        if ($preview["format"] === "opengraph") {
            return $preview;
        }
    }

    // Twitter Card
    if ($meta_tags->length > 0) {
        foreach ($meta_tags as $tag) {
            if ($tag->hasAttribute("name")) {
                $name = $tag->getAttribute("name");
                if (strpos($name, "twitter:") === 0) {
                        $preview["format"] = "twitter";
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
            }
        }
        // If TwitterCard data was found, return early
        if ($preview["format"] === "twitter") {
            return $preview;
        }
    }

    // oEmbed
    $link_tags = $doc->getElementsByTagName("link");
    if ($link_tags->length > 0) {
        foreach ($link_tags as $tag) {
            if ($tag->hasAttribute("type") && $tag->hasAttribute("href")) {
                $type = $tag->getAttribute("type");
                if ($type === 'application/json+oembed' || $type === 'text/json+oembed') {
                    $href = $tag->getAttribute("href");
                    $json = @file_get_contents($href); // Using @ to suppress file_get_contents warnings

                    if ($json !== false) {
                        $data = json_decode($json, true);
                        // Check if JSON decoding was successful
                        if ($data !== null) {
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

                            // Return after finding and processing oEmbed
                            return $preview;
                        }
                    }
                    // Stop after finding the first oEmbed link
                    break; 
                }
            }
        }
    }


    // Fallbacks

    //// Title
    $titles = $doc->getElementsByTagName("title");
    if ($titles->length > 0) {
        $preview["title"] = trim($titles[0]->textContent);
    }

    //// Futher fallback for title (h1, h2, h3, h4, h5, h6)
    if ($preview["title"] === null) {
        $body = $doc->getElementsByTagName("body")->item(0);
        if ($body) {
            $nodes_to_check = ["h1", "h2", "h3", "h4", "h5", "h6"];
            foreach ($nodes_to_check as $tag_name) {
                $elements = $body->getElementsByTagName($tag_name);
                if ($elements->length > 0) {
                    foreach ($elements as $element) {
                        $text = trim($element->textContent);
                        if ($text !== '') {
                            $preview["title"] = $text;
                            // Break out of both inner and outer loops
                            break 2;
                        }
                    }
                }
            }
        }
    }

    //// Description Meta Tag
    if ($meta_tags->length > 0) {
        foreach ($meta_tags as $tag) {
            if ($tag->hasAttribute("name") && $tag->getAttribute("name") === "description") {
                $preview["description"] = $tag->getAttribute("content");
                // Found the description meta tag, no need to continue
                break;
            }
        }
    }

    //// Further fallback for description (p, b, i)
    if ($preview["description"] === null || empty($preview["description"])) {
        $body = $doc->getElementsByTagName("body")->item(0);
        if ($body) {
            $nodes_to_check = ["p", "b", "i"];
            foreach ($nodes_to_check as $tag_name) {
                $elements = $body->getElementsByTagName($tag_name);
                if ($elements->length > 0) {
                    foreach ($elements as $element) {
                        $text = trim($element->textContent);
                        if ($text !== '') {
                            $preview["description"] = $text;
                            break 2; // Break out of both inner and outer loops
                        }
                    }
                }
            }
        }
    }


    //// Image
    $images = $doc->getElementsByTagName("img");
    for ($i = 0; $i < $images->length; $i++) {
        $src = $images->item($i)->getAttribute("src");
        if (!empty($src)) {
            $preview["image"] = resolve_url($src, $base_url);
            // Found the first image with a src, no need to continue
            break;
        }
    }

    //// Favicon
    for ($i = 0; $i < $link_tags->length; $i++) {
        $rel = $link_tags->item($i)->getAttribute("rel");
        if (strpos($rel, "icon") !== false) {
            $href = $link_tags->item($i)->getAttribute("href");
            if (!empty($href)) {
                $preview["favicon"] = resolve_url($href, $base_url);
                // Found the first favicon link, no need to continue
                break;
            }
        }
    }

    // Return the preview data
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

    $options = 0;
    if (!empty($unescape_json)) {
        $options |= JSON_UNESCAPED_SLASHES;
    }
    if (!empty($unescaped_unicode_json)) {
        $options |= JSON_UNESCAPED_UNICODE;
    }

    echo json_encode($toret, $options);

    die(); //MARK: Should we exit instead?
}
#endregion