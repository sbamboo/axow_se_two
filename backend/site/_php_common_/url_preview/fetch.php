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

require_once(__DIR__ . "/../../_config_/general.php");
require_once(__DIR__ . "/../requests.php"); 
require_once(__DIR__ . "/../db.php"); 

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
    $stmt->bind_param("s", $url_hash);
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
    $expires_at = gmdate("Y-m-d H:i:s", time() + $ttl_seconds);
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
    $stmt->bind_param("ssss", $url_hash, $url, $previewdata_json, $expires_at);
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
            return [null, rtrim(preg_replace('/^file_get_contents\(.*?\):\s*/', "", $error["message"] ?? "Unknown error"), "\r\n")];
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
function parse_html_for_preview($html, $url, ?string $oEmbed_url = null) {
    global $GENERAL_CONFIG;
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
    $preview["oembed"] = null;

    $found_opengraph_or_twitter = false;

    // OpenGraph
    $meta_tags = $doc->getElementsByTagName("meta");
    if ($meta_tags->length > 0) {
        foreach ($meta_tags as $tag) {
            if ($tag->hasAttribute("property")) {
                $prop = $tag->getAttribute("property");
                if (strpos($prop, "og:") === 0) {
                    $found_opengraph_or_twitter = true;
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
    }

    // Twitter Card
    if ($meta_tags->length > 0) {
        foreach ($meta_tags as $tag) {
            if ($tag->hasAttribute("name")) {
                $name = $tag->getAttribute("name");
                if (strpos($name, "twitter:") === 0) {
                    $found_opengraph_or_twitter = true;
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
    }

    // oEmbed
    $link_tags = $doc->getElementsByTagName("link");
    $oEmbed_link = null;
    if ($link_tags->length > 0) {
        foreach ($link_tags as $tag) {
            if ($tag->hasAttribute("type") && $tag->hasAttribute("href")) {
                $type = $tag->getAttribute("type");
                if ($type === "application/json+oembed" || $type === "text/json+oembed") {
                    $href = $tag->getAttribute("href");
                    $oEmbed_link = $href;
                    // Stop after finding the first oEmbed link
                    break;
                }
            }
        }
    }

    $oembed_autofilled = false;
    if ($oEmbed_link == null && $oEmbed_url !== null) {
        if ($oEmbed_url === "auto") {
            $oembed_autofilled = true;
            // Guess the oEmbed URL based on the URL
            $oEmbeds = $GENERAL_CONFIG["oembed_urls_per_provider"]; // "<provider>" => "<oembed_url>" (e.g. "youtube" => "https://www.youtube.com/oembed?url=%&format=json")
            // if domain main is in the oEmbed list use that provider
            $parsed_url = parse_url($url);
            $domain = $parsed_url["host"] ?? null;
            $domain_parts = explode(".", $domain);
            // handle if subdomain is present or not
            if (count($domain_parts) > 2) {
                $domain = $domain_parts[count($domain_parts) - 2] . "." . $domain_parts[count($domain_parts) - 1];
            } else {
                $domain = $domain_parts[count($domain_parts) - 1];
            }
            $domain = strtolower($domain);
            if (isset($oEmbeds[$domain])) {
                $oEmbed_url = $oEmbeds[$domain];
            } else {
                // Fallback to generic oEmbed URL
                $oEmbed_url = $GENERAL_CONFIG["oembed_generic_url"];
            }
        }
        $oEmbed_link = str_replace("%", rawurlencode($url), $oEmbed_url);
    }

    if ($oEmbed_link !== null && !empty($oEmbed_link)) {
            
        $json = @file_get_contents($oEmbed_link); // Using @ to suppress file_get_contents warnings

        if ($json !== false) {
            $data = json_decode($json, true);
            // Check if JSON decoding was successful
            if ($data !== null) {
                // Only if we haven't found OpenGraph or TwitterCard data before we map it from the oEmbed
                if ($found_opengraph_or_twitter === false) {
                    if ($oembed_autofilled !== true) {
                        $preview["format"] = "oembed";
                    }

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
                }

                // Save oEmbed payload
                $preview["oembed_provider"] = $oEmbed_link;
                $used_keys = ["title", "description", "thumbnail_url", "type"];
                $oembed_remaining = array_diff_key($data, array_flip($used_keys));
                if (!empty($oembed_remaining)) {
                    $preview["oembed"] = $oembed_remaining;
                }
            }
        }
    }


    // Fallbacks

    //// Title
    if ($preview["title"] === null) {
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
                            if ($text !== "") {
                                $preview["title"] = $text;
                                // Break out of both inner and outer loops
                                break 2;
                            }
                        }
                    }
                }
            }
        }
    }

    //// Description Meta Tag
    if ($preview["description"] === null) {
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
                            if ($text !== "") {
                                $preview["description"] = $text;
                                break 2; // Break out of both inner and outer loops
                            }
                        }
                    }
                }
            }
        }
    }


    //// Image
    if ($preview["image"] === null) {
        $images = $doc->getElementsByTagName("img");
        for ($i = 0; $i < $images->length; $i++) {
            $src = $images->item($i)->getAttribute("src");
            if (!empty($src)) {
                $preview["image"] = resolve_url($src, $base_url);
                // Found the first image with a src, no need to continue
                break;
            }
        }
    }

    //// Favicon
    if ($preview["favicon"] === null) {
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
    }

    // Return the preview data
    return $preview;
}

// Function to get the url preview data
function fetch_url_preview($url, $user_agent, $ttl_seconds, ?string $oEmbed_url = null) {
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

    $preview = parse_html_for_preview($html, $url, $oEmbed_url);
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
function extract_url_params_for_url_preview($req_data) {
    global $GENERAL_CONFIG;
    
    $use_client_user_agent = $req_data["client-user-agent"] ?? false;
    $user_agent = "MetadataFetcher/1.0";
    if ($use_client_user_agent && isset($_SERVER["HTTP_USER_AGENT"])) {
        $user_agent = $_SERVER["HTTP_USER_AGENT"];
    }

    $ttl_seconds = $GENERAL_CONFIG["url_preview_default_ttl"];
    if (isset($req_data["cache-ttl"])) {
        $ttl_seconds = intval($req_data["cache-ttl"]);
    }

    $oEmbed_url = null;
    if (isset($req_data["oembed_url"])) {
        $oEmbed_url = $req_data["oembed_url"];
    }

    return [$user_agent, $ttl_seconds, $oEmbed_url];
}

function req_fetch_url_preview($req_data) {
    global $GENERAL_CONFIG;

    list($user_agent, $ttl_seconds, $oEmbed_url) = extract_url_params_for_url_preview($req_data);

    // if method is GET ensure urls are not url-encoded
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        if (isset($req_data["url"])) {
            $req_data["url"] = urldecode($req_data["url"]);
        }
        if (isset($req_data["urls"])) {
            $req_data["urls"] = urldecode($req_data["urls"]);
        }
        if ($oEmbed_url !== null) {
            $oEmbed_url = urldecode($oEmbed_url);
        }
    }

    $request_urls = [];
    if (isset($req_data["url"])) {
        $request_urls = [$req_data["url"]];
    } else if (isset($req_data["urls"])) {
        $request_urls = explode($GENERAL_CONFIG["url_preview_multi_delim"], $req_data["urls"]);
    }

    $toret_final = [
        "status" => "success",
        "msg" => "",
        "previews" => []
    ];

    // Foreach $request_url in $request_urls
    foreach ($request_urls as $request_url) {
        $request_url = trim($request_url);

        if ($request_url === null) {
            http_response_code(400); // HTTP code 400 : Bad Request
            $toret_final["previews"][] = [
                "status" => "failed",
                "msg" => "Invalid request, missing url"
            ];
            continue;
        }

        if ($ttl_seconds > $GENERAL_CONFIG["url_preview_max_ttl"]) {
            $ttl_seconds = $GENERAL_CONFIG["url_preview_max_ttl"];
        }

        list($preview, $success, $error_message, $http_code) = fetch_url_preview($request_url, $user_agent, $ttl_seconds, $oEmbed_url);
        
        $toret_final["previews"][] = [
            "status" => $success ? "success" : "failed",
            "msg" => $success ? "Preview fetched successfully" : $error_message
        ] + ($preview ?? []);
        continue;
    }

    if (count($toret_final["previews"]) === 0) {
        $toret_final = [
            "status" => "failed",
            "msg" => "No previews found"
        ];
    } else if (count($toret_final["previews"]) === 1) {
        $toret_final = $toret_final["previews"][0];
    }

    http_response_code($http_code);
    echo format_json_response($toret_final, isset($_REQUEST["escape_unicode"]) ? true : false);
    die(); //MARK: Should we exit instead?
}
#endregion