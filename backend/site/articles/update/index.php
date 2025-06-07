<?php
header("Content-Type: application/json; charset=utf-8");

require_once(__DIR__ . "/../../_php_common_/env.php");
require_once(__DIR__ . "/../../_php_common_/responders.php");
require_once(__DIR__ . "/../../_php_common_/permissions.php");
require_once(__DIR__ . "/../../_php_common_/users.php");

req_require_method("POST");

$decoded_token = req_require_token();

$req_data = get_request_body();

list($data, $msg) = get_node_data("articles");
if ($data === null) {
    req_send(false, "Failed to retrieve articles data: $msg", 500);  // HTTP code 500 : Internal Server Error
}

if (isset($req_data["category"])) {
    req_require_permission("articles-cat.update", $decoded_token, $decoded_token["usr"]);

    // Ensure $req_data["new_category"]
    if (!isset($req_data["new_category"]) || !is_string($req_data["new_category"]) || trim($req_data["new_category"]) === "") {
        req_send(false, "Missing 'new_category' parameter", 400); // HTTP code 400 : Bad Request
    }

    // Check so category exists under $data["categories"]
    if (isset($data["categories"]) && is_array($data["categories"]) && in_array($req_data["category"], $data["categories"])) {
        // Rename the category in the data
        $data["categories"] = array_map(function($cat) use ($req_data) {
            return $cat === $req_data["category"] ? $req_data["new_category"] : $cat;
        }, $data["categories"]);

        // Check if any article has the category, if so update its category to the new category and build its $article_folder to rename that
        $articles = $data["articles"] ?? [];
        foreach ($articles as $i=>$article) {
            if (is_array($article) && isset($article["category"]) && $article["category"] === $req_data["category"]) {
                // Update the article's category
                $data["articles"][$i]["category"] = $req_data["new_category"];
                
                // Build the article_folder
                $article_folder = str_replace([".", "/", "\\"], "_", $article["category"]) . "." . str_replace([".", "/", "\\"], "_", $article["subcategory"]) . "." . str_replace([".", "/", "\\"], "_", $article["id"]);
                // Rename the folder to the new category
                $new_article_folder = str_replace([".", "/", "\\"], "_", $req_data["new_category"]) . "." . str_replace([".", "/", "\\"], "_", $article["subcategory"]) . "." . str_replace([".", "/", "\\"], "_", $article["id"]);
                rename_data_node_file("articles", "data/" . $article_folder, "data/" . $new_article_folder);
            }
        }

        // Update the data node
        list($success, $msg) = update_data_node_json_file("articles", "data.jsonc", $data);
        
    } else {
        req_send(false, "Category not found", 404);  // HTTP code 404 : Not Found
    }

} else if (isset($req_data["subcategory"])) {
    req_require_permission("articles-subcat.update", $decoded_token, $decoded_token["usr"]);

    // Ensure $req_data["new_subcategory"]
    if (!isset($req_data["new_subcategory"]) || !is_string($req_data["new_subcategory"]) || trim($req_data["new_subcategory"]) === "") {
        req_send(false, "Missing 'new_subcategory' parameter", 400); // HTTP code 400 : Bad Request
    }

    // Check so subcategory exists under $data["subcategories"]
    if (isset($data["subcategories"]) && is_array($data["subcategories"]) && in_array($req_data["subcategory"], $data["subcategories"])) {
        // Rename the subcategory in the data
        $data["subcategories"] = array_map(function($subcat) use ($req_data) {
            return $subcat === $req_data["subcategory"] ? $req_data["new_subcategory"] : $subcat;
        }, $data["subcategories"]);

        // Check if any article has the subcategory, if so update its subcategory and build its $article_folder to rename that
        $articles = $data["articles"] ?? [];
        foreach ($articles as $i=>$article) {
            if (is_array($article) && isset($article["subcategory"]) && $article["subcategory"] === $req_data["subcategory"]) {
                // Update the article's subcategory
                $data["articles"][$i]["subcategory"] = $req_data["new_subcategory"];
                
                // Build the article_folder
                $article_folder = str_replace([".", "/", "\\"], "_", $article["category"]) . "." . str_replace([".", "/", "\\"], "_", $article["subcategory"]) . "." . str_replace([".", "/", "\\"], "_", $article["id"]);
                // Rename the folder to the new subcategory
                $new_article_folder = str_replace([".", "/", "\\"], "_", $article["category"]) . "." . str_replace([".", "/", "\\"], "_", $req_data["new_subcategory"]) . "." . str_replace([".", "/", "\\"], "_", $article["id"]);
                rename_data_node_file("articles", "data/" . $article_folder, "data/" . $new_article_folder);
            }
        }

        // Update the data node
        list($success, $msg) = update_data_node_json_file("articles", "data.jsonc", $data);
        
    } else {
        req_send(false, "Subcategory not found", 404);  // HTTP code 404 : Not Found
    }

} else if (isset($req_data["article"])) {
    req_require_permission("articles.update", $decoded_token, $decoded_token["usr"]);

    // Ensure $req_data["meta"] or $req_data["content"]

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

    // Validate that unless user has the "all-articles" property permission the article author.lowercase() must be "@<username>"
    $has_all_articles = check_user_permission_exact($decoded_token["usr"], "all-articles");
    if (!$has_all_articles) {
        $article_author = strtolower($article["author"]);
        // $decoded_token["usr"] is userID use get_current_username to get username by ID from database
        list($username, $msg) = get_current_username($decoded_token["usr"]);
        if (!$username !== null) {
            req_send(false, "Failed to get current username: $msg", 500); // HTTP code 500 : Internal Server Error
        }
        if ($article_author !== "@" . strtolower($username)) {
            req_send(false, "You do not have permission to update this article", 403); // HTTP code 403 : Forbidden
        }
    }

    // Build the article_folder
    $article_folder = str_replace([".", "/", "\\"], "_", $category) . "." . str_replace([".", "/", "\\"], "_", $subcategory) . "." . str_replace([".", "/", "\\"], "_", $article_id);

    // If "content" find the source.md file for the article and update it with the markdown content
    if (isset($req_data["content"])) {
        $article_source = "data/" . $article_folder . "/source.md";
        $exists = check_data_node_file_exists("articles", $article_source);
        if (!$exists) {
            req_send(false, "Article markdown file not found", 404); // HTTP code 404 : Not Found
        }

        
        list($success, $msg) = replace_data_node_file("articles", $article_source, $req_data["content"]);
        if (!$success) {
            req_send(false, "Failed to update article content: $msg", 500); // HTTP code 500 : Internal Server Error
        }
    }

    // If "meta" modify the articles entry in the data
    if (isset($req_data["meta"])) {
        // Recursively merge $req_data["meta"] onto $article using merge_data_node_json_file
        list($success, $msg) = merge_data_node_json_file("articles", "data.jsonc", $article, $req_data["meta"]);
        if (!$success) {
            req_send(false, "Failed to update article metadata: $msg", 500); // HTTP code 500 : Internal Server Error
        }

        // If we changed the category or subcategory, we need to rename its folder
        if (isset($req_data["meta"]["category"])) {
            // Rename the folder
            $new_article_folder = str_replace([".", "/", "\\"], "_", $req_data["meta"]["category"]) . "." . str_replace([".", "/", "\\"], "_", $subcategory) . "." . str_replace([".", "/", "\\"], "_", $article_id);
            rename_data_node_file("articles", "data/" . $article_folder, "data/" . $new_article_folder);
        }
        if (isset($req_data["meta"]["subcategory"])) {
            // Rename the folder
            $new_article_folder = str_replace([".", "/", "\\"], "_", $category) . "." . str_replace([".", "/", "\\"], "_", $req_data["meta"]["subcategory"]) . "." . str_replace([".", "/", "\\"], "_", $article_id);
            rename_data_node_file("articles", "data/" . $article_folder, "data/" . $new_article_folder);
        }
    }

} else {
    req_send(false, "Missing 'category=', 'subcategory=' or 'article=' parameter", 400); // HTTP code 400 : Bad Request
}