<?php
header("Content-Type: application/json; charset=utf-8");

require_once(__DIR__ . "/../../_php_common_/env.php");
require_once(__DIR__ . "/../../_php_common_/responders.php");
require_once(__DIR__ . "/../../_php_common_/data_nodes.php");
require_once(__DIR__ . "/../../_php_common_/url_preview/fetch.php");

req_require_method("GET");

$req_data = get_request_body();

if (!isset($req_data["id"]) || !is_string($req_data["id"])) {
    req_send(false, "Missing 'id' parameter", 400); // HTTP code 400 : Bad Request
}
$article_query = $req_data["id"];

list($data, $msg) = get_node_data("articles");
if ($data === null) {
    req_send(false, "Failed to retrieve articles data: $msg", 500);  // HTTP code 500 : Internal Server Error
}

if (isset($data["articles"]) && is_array($data["articles"])) {

    // From the article_id extract id, subcategory, category; article_id is in the format "<category>/<subcategory>/<id>" pls note that subcategory can include "/"
    //// Split the article_id by "/"
    $parts = explode("/", $article_query);
    //// Get first part as category, the last part as id, and everything in between as subcategory
    $category = $parts[0];
    $article_id = $parts[count($parts) - 1];
    $subcategory = implode("/", array_slice($parts, 1, count($parts) - 2));

    // Build the article_folder
    $article_folder = str_replace([".", "/", "\\"], "_", $category) . "." . str_replace([".", "/", "\\"], "_", $subcategory) . "." . str_replace([".", "/", "\\"], "_", $article_id);

    // Check so an article with the given ID exists
    $article = null;
    foreach ($data["articles"] as $a) {
        if ($a["id"] === $article_id && $a["category"] === $category && $a["subcategory"] === $subcategory) {
            $article = $a;
            break;
        }
    }
    if ($article === null) {
        req_send(false, "Article with query '$article_query' not found", 404); // HTTP code 404 : Not Found
    }

    // Favicon as base64
    if (isset($article["favicon"]) && filter_var($article["favicon"], FILTER_VALIDATE_URL) === false) {
        if (strpos($article["favicon"], "/") === false) {
            $file = "data/" . $article_folder . "/" . $article["favicon"];
        } else {
            $file = "data/" . $article["favicon"];
        }
        $file_exists = check_data_node_file_exists("articles", $file);
        if ($file_exists) {
            list($image, $msg) = read_data_node_file("articles", $file);
            if ($image !== null) {
                $article["favicon"] = "data:image/png;base64," . base64_encode($image);
            } else {
                $article["favicon"] = null;
            }
        } else {
            $article["favicon"] = null;
        }
    }

    // Remove the "card_background" field
    unset($article["card_background"]);

    // Banner as base64
    if (isset($article["banner"]) && filter_var($article["banner"], FILTER_VALIDATE_URL) === false) {
        if (strpos($article["banner"], "/") === false) {
            $file = "data/" . $article_folder . "/" . $article["banner"];
        } else {
            $file = "data/" . $article["banner"];
        }
        $file_exists = check_data_node_file_exists("articles", $file);
        if ($file_exists) {
            list($image, $msg) = read_data_node_file("articles", $file);
            if ($image !== null) {
                $article["banner"] = "data:image/png;base64," . base64_encode($image);
            } else {
                $article["banner"] = null;
            }
        } else {
            $article["banner"] = null;
        }
    }

    // Include content as markdown source
    $article = ["meta" => $article, "content" => null, "url_previews" => []];
    // Check if the article has a content file
    $content_file = "data/" . $article_folder . "/source.md";
    $file_exists = check_data_node_file_exists("articles", $content_file);
    if ($file_exists) {
        list($content, $msg) = read_data_node_file("articles", $content_file);
        if ($content !== null) {
            $article["content"] = $content;
        } else {
            $article["content"] = null;
        }
    } else {
        $article["content"] = null;
    }

    // If content was set check for URL:s and if found generate previews for them
    if ($article["content"] !== null) {
        list($url_preview_user_agent, $url_preview_ttl_seconds, $url_preview_oembed_url) = extract_url_params_for_url_preview($req_data);
        // Extract URLs from the content
        preg_match_all('/https?:\/\/[^\s)>\]]+/', $article["content"], $matches);

        $urls = array_map(function ($url) {
            // Remove trailing punctuation that is not likely part of the URL
            return rtrim($url, '.,);:!?]>');
        }, $matches[0]);

        foreach ($urls as $url) {
            list($article["url_previews"][], $msg) = fetch_url_preview($url, $url_preview_user_agent, $url_preview_ttl_seconds, $url_preview_oembed_url);
        }
    }

    // Filter any null values from the URL previews
    $article["url_previews"] = array_filter($article["url_previews"], function($preview) {
        return $preview !== null;
    });

    req_send(true, "", 200, ["article" => $article]);  // HTTP code 200 : OK
} else {
    req_send(false, "No articles found", 404);  // HTTP code 404 : Not Found
}