<?php
header("Content-Type: application/json; charset=utf-8");

require_once(__DIR__ . "/../../_php_common_/env.php");
require_once(__DIR__ . "/../../_php_common_/responders.php");
require_once(__DIR__ . "/../../_php_common_/requests.php");
require_once(__DIR__ . "/../../_php_common_/data_nodes.php");

req_require_method("POST");

$decoded_token = req_require_token();

$req_data = get_request_body();

list($data, $msg) = get_node_data("articles");
if ($data === null) {
    req_send(false, "Failed to retrieve articles data: $msg", 500);  // HTTP code 500 : Internal Server Error
}

if (isset($req_data["category"])) {

    req_require_permission("articles-cat.remove", $decoded_token, $decoded_token["usr"]);

    // Check so category exists under $data["categories"]
    if (isset($data["categories"]) && is_array($data["categories"]) && in_array($req_data["category"], $data["categories"])) {
        // Category exists remove it from the data using update_data_node_json_file
        //// Remove the category from the data
        $data["categories"] = array_filter($data["categories"], function($cat) use ($req_data) {
            return $cat !== $req_data["category"];
        });

        // Move any articles in this category to the "none" category
        // Get all articles from the nodes data, then for each make their $article_folder and rename that folder
        $articles = $data["articles"] ?? [];
        $made_changes = false;
        foreach ($articles as $i => $article) {
            if (is_array($article) && isset($article["category"]) && $article["category"] === $req_data["category"]) {
                // Build the article_folder
                $article_folder = str_replace([".", "/", "\\"], "_", $article["category"]) . "." . str_replace([".", "/", "\\"], "_", $article["subcategory"]) . "." . str_replace([".", "/", "\\"], "_", $article["id"]);
                // Rename the folder to "none" category
                $new_article_folder = "none." . str_replace([".", "/", "\\"], "_", $article["subcategory"]) . "." . str_replace([".", "/", "\\"], "_", $article["id"]);
                rename_data_node_file("articles", "data/" . $article_folder, "data/" . $new_article_folder);
                // Update the article's category to "none"
                $data["articles"][$i]["category"] = "none";
                $made_changes = true;
            }
        }

        // Update
        list($success, $msg) = update_data_node_json_file("articles", "data.jsonc", $data);
        if ($success) {
            req_send(true, "Category removed successfully", 200);  // HTTP code 200 : OK
        } else {
            req_send(false, "Failed to remove category: $msg", 500);  // HTTP code 500 : Internal Server Error
        }
    } else {
        req_send(false, "Category not found", 404);  // HTTP code 404 : Not Found
    }

} else if (isset($req_data["subcategory"])) {

    req_require_permission("articles-subcat.remove", $decoded_token, $decoded_token["usr"]);

    // Check so subcategory exists under $data["subcategories"]
    if (isset($data["subcategories"]) && is_array($data["subcategories"]) && in_array($req_data["subcategory"], $data["subcategories"])) {
        // Subcategory exists remove it from the data using update_data_node_json_file
        //// Remove the subcategory from the data
        $data["subcategories"] = array_filter($data["subcategories"], function($subcat) use ($req_data) {
            return $subcat !== $req_data["subcategory"];
        });

        // Move any articles in this subcategory to the "none" subcategory
        // Get all articles from the nodes data, then for each make their $article_folder and rename that folder
        $articles = $data["articles"] ?? [];
        $made_changes = false;
        foreach ($articles as $i => $article) {
            if (is_array($article) && isset($article["subcategory"]) && $article["subcategory"] === $req_data["subcategory"]) {
                // Build the article_folder
                $article_folder = str_replace([".", "/", "\\"], "_", $article["category"]) . "." . str_replace([".", "/", "\\"], "_", $article["subcategory"]) . "." . str_replace([".", "/", "\\"], "_", $article["id"]);
                // Rename the folder to "none" subcategory
                $new_article_folder = str_replace([".", "/", "\\"], "_", $article["category"]) . ".none." . str_replace([".", "/", "\\"], "_", $article["id"]);
                rename_data_node_file("articles", "data/" . $article_folder, "data/" . $new_article_folder);
                // Update the article's subcategory to "none"
                $data["articles"][$i]["subcategory"] = "none";
                $made_changes = true;
            }
        }

        // Update
        list($success, $msg) = update_data_node_json_file("articles", "data.jsonc", $data);
        if ($success) {
            req_send(true, "Subcategory removed successfully", 200);  // HTTP code 200 : OK
        } else {
            req_send(false, "Failed to remove subcategory: $msg", 500);  // HTTP code 500 : Internal Server Error
        }
    } else {
        req_send(false, "Subcategory not found", 404);  // HTTP code 404 : Not Found
    }

} else if (isset($req_data["article"])) {

    req_require_permission("articles-cat.remove", $decoded_token, $decoded_token["usr"]);

    // From the article_id extract id, subcategory, category; article_id is in the format "<category>/<subcategory>/<id>" pls note that subcategory can include "/"
    //// Split the article_id by "/"
    $article_query = $req_data["article"];
    $parts = explode("/", $article_query);
    //// Get first part as category, the last part as id, and everything in between as subcategory
    $category = $parts[0];
    $article_id = $parts[count($parts) - 1];
    $subcategory = implode("/", array_slice($parts, 1, count($parts) - 2));

    // Check if any article matches the above and if so build its $article_folder
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

    // Build the article_folder
    $article_folder = str_replace([".", "/", "\\"], "_", $category) . "." . str_replace([".", "/", "\\"], "_", $subcategory) . "." . str_replace([".", "/", "\\"], "_", $article_id);

    // Remove the folder
    list($success, $msg) = remove_data_node_folder("articles", $article_folder);

    if ($success) {
        // Remove the article from the data
        $data["articles"] = array_filter($data["articles"], function($a) use ($article_id, $category, $subcategory) {
            return !($a["id"] === $article_id && $a["category"] === $category && $a["subcategory"] === $subcategory);
        });
        // Update the data
        list($success, $msg) = update_data_node_json_file("articles", "data.jsonc", $data);
        if ($success) {
            req_send(true, "Article removed successfully", 200);  // HTTP code 200 : OK
        } else {
            req_send(false, "Failed to remove article: $msg", 500);  // HTTP code 500 : Internal Server Error
        }
    } else {
        req_send(false, "Failed to remove article folder: $msg", 500);  // HTTP code 500 : Internal Server Error
    }
}