<?php
header("Content-Type: application/json; charset=utf-8");

require_once(__DIR__ . "/../../_php_common_/env.php");
require_once(__DIR__ . "/../../_php_common_/responders.php");
require_once(__DIR__ . "/../../_php_common_/data_nodes.php");

req_require_method("GET");

$req_data = get_request_body();

/*
"articles": {
    "id": "<string:article_id>",
    "category": "<string:category>",
    "subcategory": "<string:subcategory>",
    "name": "<string>",
    "author": "<string:profile_id_with_@>",
    "published": "<date:yyyy-mm-dd>",
    "last_changed": "<date:yyyy-mm-dd>",
    "tags": ["<string>"],
    "favicon": "<string:optional:url_or_base_url>",
    "card_background": "<string:optional:url_or_base_url>"
}
*/

list($data, $msg) = get_node_data("articles");
if ($data === null) {
    req_send(false, "Failed to retrieve articles data: $msg", 500);  // HTTP code 500 : Internal Server Error
}

if (isset($req_data["categories"])) {
    // Get all categories
    if (isset($data["categories"]) && is_array($data["categories"])) {
        return req_send(true, "", 200, ["categories" => $data["categories"]]);  // HTTP code 200 : OK
    } else {
        return req_send(true, "", 200, ["categories" => []]);  // HTTP code 200 : OK
    }

} elseif (isset($req_data["subcategories"])) {
    // Get all subcategories
    if (isset($data["subcategories"]) && is_array($data["subcategories"])) {
        return req_send(true, "", 200, ["subcategories" => $data["subcategories"]]);  // HTTP code 200 : OK
    } else {
        return req_send(true, "", 200, ["subcategories" => []]);  // HTTP code 200 : OK
    }

} elseif (isset($req_data["articles"])) {
    // Get all articles
    if (isset($data["articles"]) && is_array($data["articles"])) {
        $articles = array_map(function($article) {

            $article_folder = str_replace([".", "/", "\\"], "_", $article["category"]) . "." . str_replace([".", "/", "\\"], "_", $article["subcategory"]) . "." . str_replace([".", "/", "\\"], "_", $article["id"]);

            // Remove the "banner" field
            unset($article["banner"]);

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

            // Card background as base64
            if (isset($article["card_background"]) && filter_var($article["card_background"], FILTER_VALIDATE_URL) === false) {
                if (strpos($article["card_background"], "/") === false) {
                    $file = "data/" . $article_folder . "/" . $article["card_background"];
                } else {
                    $file = "data/" . $article["card_background"];
                }
                $file_exists = check_data_node_file_exists("articles", $file);
                if ($file_exists) {
                    list($image, $msg) = read_data_node_file("articles", $file);
                    if ($image !== null) {
                        $article["card_background"] = "data:image/png;base64," . base64_encode($image);
                    } else {
                        $article["card_background"] = null;
                    }
                } else {
                    $article["card_background"] = null;
                }
            }

            // Return the article with the modified fields
            return $article;
        }, $data["articles"]);

        req_send(true, "", 200, ["articles" => $articles]);  // HTTP code 200 : OK
    } else {
        req_send(true, "", 200, ["articles" => []]);  // HTTP code 200 : OK
    }

} else {
    // Return error
    req_send(false, "Invalid request. Please add 'categories', 'subcategories', or 'articles'.", 400);  // HTTP code 400 : Bad Request
}