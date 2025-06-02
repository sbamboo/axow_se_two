<?php
header("Content-Type: application/json; charset=utf-8");

require_once(__DIR__ . "/../../_php_common_/env.php");
require_once(__DIR__ . "/../../_php_common_/responders.php");
require_once(__DIR__ . "/../../_php_common_/requests.php");
require_once(__DIR__ . "/../../_php_common_/data_nodes.php");

req_require_method("GET");

$req_data = get_request_body();

list($data, $msg) = get_node_data("wiki");
if ($data === null) {
    req_send(false, $msg, 500); // HTTP code 500 : Internal Server Error
}

if (!isset($data["wikis"])) {
    req_send(false, "No wikis found", 404); // HTTP code 404 : Not Found
}

$toret = [];

foreach ($data["wikis"] as $wiki => $wiki_data) {
    $toret[$wiki] = [
        "name" = => $wiki_data["name"] ?? "Unnamed Wiki",
        "description" => $wiki_data["description"] ?? "No description available"
        "categories" => $wiki_data["categories"] ?? [], // <- Associate array
    ]

    if (isset($req_data["include_pages"])) {
        // To know pages we must listdir /data/<wiki>/<page> then map "$<wiki>:<category>/<page>" => <page_data>; <category> is <page_data>["category"]
        if (check_data_node_dir_exists("wiki", $wiki)) {
            list($folders, $msg) = list_data_node_files("wiki", $wiki);
            if (!$folders === null) {
                // Foreach folders read it using read_data_node_json_file("wiki", $wiki . "/" . $folder . "/page.jsonc");
                $pages = [];
                foreach ($files as $file) {
                    list($page_data, $msg) = read_data_node_json_file("wiki", "$wiki/$file/page.jsonc");
                    if ($page_data !== null) {
                        $category = $page_data["category"] ?? "default";
                        $pages["$$wiki:$category/$file"] = $page_data;
                    }
                }
                $toret[$wiki]["pages"] = $pages;
            }
        }
    }
}

req_send(true, "", 200, ["wikis" => $toret]); // HTTP code 200 : OK