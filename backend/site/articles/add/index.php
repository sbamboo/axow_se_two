<?php
header("Content-Type: application/json; charset=utf-8");

require_once(__DIR__ . "/../../_php_common_/env.php");
require_once(__DIR__ . "/../../_php_common_/responders.php");

req_require_method("POST");

$decoded_token = req_require_token();

$req_data = get_request_body();

list($data, $msg) = get_node_data("articles");
if ($data === null) {
    req_send(false, "Failed to retrieve articles data: $msg", 500);  // HTTP code 500 : Internal Server Error
}

if (isset($req_data["category"])) {
    req_require_permission("articles-cat.add", $decoded_token, $decoded_token["usr"]);

    // Add the category to the data
    if (!isset($data["categories"]) || !is_array($data["categories"])) {
        $data["categories"] = [$req_data["category"]];
    } else {
        if (in_array($req_data["category"], $data["categories"])) {
            req_send(false, "Category already exists", 409); // HTTP code 409 : Conflict
        }
        $data["categories"][] = $req_data["category"];
    }
    // Update the data node
    list($success, $msg) = update_data_node_json_file("articles", "data.jsonc", $data);
    if ($success) {
        req_send(true, "Category added successfully", 200);  // HTTP code 200 : OK
    } else {
        req_send(false, "Failed to add category: $msg", 500);  // HTTP code 500 : Internal Server Error
    }

} else if (isset($req_data["subcategory"])) {
    req_require_permission("articles-subcat.add", $decoded_token, $decoded_token["usr"]);

    // Add the subcategory to the data
    if (!isset($data["subcategories"]) || !is_array($data["subcategories"])) {
        $data["subcategories"] = [$req_data["subcategory"]];
    } else {
        if (in_array($req_data["subcategory"], $data["subcategories"])) {
            req_send(false, "Subcategory already exists", 409); // HTTP code 409 : Conflict
        }
        $data["subcategories"][] = $req_data["subcategory"];
    }
    // Update the data node
    list($success, $msg) = update_data_node_json_file("articles", "data.jsonc", $data);
    if ($success) {
        req_send(true, "Subcategory added successfully", 200);  // HTTP code 200 : OK
    } else {
        req_send(false, "Failed to add subcategory: $msg", 500);  // HTTP code 500 : Internal Server Error
    }

} else if (isset($req_data["article"])) {
    req_require_permission("articles.add", $decoded_token, $decoded_token["usr"]);

    // Ensure $req_data["article"]["meta"] and $req_data["article"]["content"]
    if (!isset($req_data["article"]["meta"]) || !is_array($req_data["article"]["meta"]) || !isset($req_data["article"]["content"]) || !is_string($req_data["article"]["content"])) {
        req_send(false, "Missing 'meta' or 'content' in provided data", 400); // HTTP code 400 : Bad Request
    }

    // Validate the article meta data has required fields
    $required_fields = ["id", "category", "subcategory", "name", "author", "author_title", "published", "last_changed", "tags", "favicon", "banner", "card_background"];
    foreach ($required_fields as $field) {
        if (!isset($req_data["article"]["meta"][$field]) || !is_string($req_data["article"]["meta"][$field]) || trim($req_data["article"]["meta"][$field]) === "") {
            req_send(false, "Missing or invalid '$field' in article meta data", 400); // HTTP code 400 : Bad Request
        }
    }

    $article_folder = str_replace([".", "/", "\\"], "_", $req_data["article"]["meta"]["category"]) . "." . str_replace([".", "/", "\\"], "_", $req_data["article"]["meta"]["subcategory"]) . "." . str_replace([".", "/", "\\"], "_", $req_data["article"]["meta"]["id"]);

    // Check if the article already exists in data
    foreach ($data["articles"] ?? [] as $article) {
        if ($article["id"] === $req_data["article"]["meta"]["id"] && 
            $article["category"] === $req_data["article"]["meta"]["category"] && 
            $article["subcategory"] === $req_data["article"]["meta"]["subcategory"]) {
            req_send(false, "Article with ID '{$req_data["article"]["meta"]["id"]}' already exists in category '{$req_data["article"]["meta"]["category"]}' and subcategory '{$req_data["article"]["meta"]["subcategory"]}'", 409); // HTTP code 409 : Conflict
        }
    }

    // Check so the article_folder does not already exist
    if (check_data_node_file_exists("articles", "data/" . $article_folder)) {
        req_send(false, "Article data '$article_folder' already exists", 409); // HTTP code 409 : Conflict
    }

    // Create the article folder
    if (!create_data_node_file("articles", "data/" . $article_folder, [])) {
        req_send(false, "Failed to create article folder '$article_folder'", 500); // HTTP code 500 : Internal Server Error
    }

    // Save the article content to $article_folder/source.md
    $content_file = "data/" . $article_folder . "/source.md";
    if (!write_data_node_file("articles", $content_file, $req_data["article"]["content"])) {
        req_send(false, "Failed to write article content to '$content_file'", 500); // HTTP code 500 : Internal Server Error
    }

    // Add the metadata to $data["articles"]
    $data["articles"][] = $req_data["article"]["meta"];

    // Update the data node
    list($success, $msg) = update_data_node_json_file("articles", "data.jsonc", $data);
    if ($success) {
        req_send(true, "Article added successfully", 200);  // HTTP code 200 : OK
    } else {
        req_send(false, "Failed to add article: $msg", 500);  // HTTP code 500 : Internal Server Error
    }

} else {
    req_send(false, "Missing 'category', 'subcategory' or 'article' parameter", 400); // HTTP code 400 : Bad Request
}