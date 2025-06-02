<?php
header("Content-Type: application/json; charset=utf-8");

require_once(__DIR__ . "/../../_php_common_/env.php");
require_once(__DIR__ . "/../../_php_common_/responders.php");
require_once(__DIR__ . "/../../_php_common_/requests.php");
require_once(__DIR__ . "/../../_php_common_/data_nodes.php");

req_require_method("GET");

$req_data = get_request_body();
// Check if either "wiki" or "page" but not both
if (isset($req_data["wiki"]) && isset($req_data["page"])) {
    req_send(false, "You must specify either 'wiki' or 'page', not both", 400); // HTTP code 400 : Bad Request
}

if (isset($req_data["wiki"])) {
    if (empty($req_data["wiki"])) {
        req_send(false, "'wiki' cannot be empty", 400); // HTTP code 400 : Bad Request
    }

    $wiki = $req_data["wiki"];

    list($data, $msg) = get_node_data("wiki");
    if ($data === null) {
        req_send(false, $msg, 500); // HTTP code 500 : Internal Server Error
    }

    if (!isset($data["wikis"])) {
        req_send(false, "No wikis found", 404); // HTTP code 404 : Not Found
    }

    // If $wiki is not key of $data["wikis"], return 404
    if (!array_key_exists($wiki, $data["wikis"])) {
        req_send(false, "Wiki '" . $wiki . "' not found", 404); // HTTP code 404 : Not Found
    }

    $toret = ["homepage" => [$wiki => [
        "name" => $data["wikis"][$wiki]["name"] ?? "Unnamed Wiki",
        "description" => $data["wikis"][$wiki]["description"] ?? "",
        "editors" => $data["wikis"][$wiki]["editors"] ?? [],
        "categories" => $data["wikis"][$wiki]["categories"] ?? [],               // <- Associate array
        "highlighted_media" => $data["wikis"][$wiki]["highlighted_media"] ?? [],
        "highlighted_articles" => $data["wikis"][$wiki]["highlighted_articles"] ?? []
    ]]];

    req_send(true, "", 200, $toret); // HTTP code 200 : OK
}

else if (isset($req_data["page"])) {
    if (empty($req_data["page"])) {
        req_send(false, "'page' cannot be empty", 400); // HTTP code 400 : Bad Request
    }

    $page_id = $req_data["page"];

    // Validate $page_id format is $<wiki>/<category>/<page>
    if (!preg_match('/^\$([a-zA-Z0-9_-]+)\:([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)$/', $page_id, $matches)) {
        req_send(false, "Invalid page format. Expected format is $<wiki>:<category>/<page>", 400); // HTTP code 400 : Bad Request
        if ($matches === false || count($matches) !== 4) {
            req_send(false, "Invalid page format. Expected format is $<wiki>:<category>/<page>", 400); // HTTP code 400 : Bad Request
        }

        $wiki = $matches[1];
        $category = $matches[2];
        $page = $matches[3];
    }

    // Check if check_data_node_dir_exists("wiki", $wiki) exists
    if (!check_data_node_dir_exists("wiki", $wiki)) {
        req_send(false, "Wiki '" . $wiki . "' not found", 404); // HTTP code 404 : Not Found
    }

    // Use read_data_node_json_file("wiki", $wiki . "/" . $page . "/page.jsonc") to read the page data
    list($page_data, $msg) = read_data_node_json_file("wiki", "$wiki/$category/$page/page.jsonc");
    if ($page_data === null) {
        req_send(false, $msg, 500); // HTTP code 500 : Internal Server Error
    }

    req_send(true, "", 200, $page_data); // HTTP code 200 : OK
}