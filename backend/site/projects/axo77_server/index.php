<?php
header("Content-Type: application/json; charset=utf-8");

require_once(__DIR__ . "/../../_php_common_/env.php");
require_once(__DIR__ . "/../../_php_common_/responders.php");
require_once(__DIR__ . "/../../_php_common_/data_nodes.php");
require_once(__DIR__ . "/../../_php_common_/datetime.php");
require_once(__DIR__ . "/../../_php_common_/libs/php-json-comment.php");
require_once(__DIR__ . "/../../_php_common_/url_preview/fetch.php");

req_require_method("GET");

$req_data = get_request_body();

list($data, $msg) = read_data_node_json_file("projects", "axo77_server/data.jsonc");

if ($data === null) {
    req_send(false, $msg, 500); // HTTP code 500 : Internal Server Error
} else {
    // Auto handle datetimes in the history data, so if the entries "date_format" is "auto" we auto-identify the datetime format
    if (isset($data["history"])) {
        foreach ($data["history"] as  $i => $entry) {
            if (isset($entry["date_format"]) && $entry["date_format"] === "auto" && isset($entry["date"])) {
                $data["history"][$i]["date_format"] = detectDatetimeFormat($entry["date"]);
            }
        }
    }

    // If $req_data["url_previews"] is set:
    //   Check if history/description or media/carusell/media or media/carusell/description is url, if so call urlPreview
    if (isset($req_data["url_previews"])) {
        $url_previews = [];
        list($url_preview_user_agent, $url_preview_ttl_seconds, $url_preview_oembed_url) = extract_url_params_for_url_preview($req_data);
        // Check history/{i}/description
        if (isset($data["history"]) && is_array($data["history"])) {
            foreach ($data["history"] as $i => $entry) {
                if (isset($entry["description"]) && filter_var($entry["description"], FILTER_VALIDATE_URL)) {
                    list($url_previews["history/" . strval($i)], $msg) = fetch_url_preview($entry["description"], $url_preview_user_agent, $url_preview_ttl_seconds, $url_preview_oembed_url);
                }
            }
        }
        // Check media/carusell/{i}/media if type is "link" url is "href" field
        if (isset($data["media"]) && isset($data["media"]["carusell"]) && is_array($data["media"]["carusell"])) {
            foreach ($data["media"]["carusell"] as $i => $entry) {
                if (isset($entry["type"]) && $entry["type"] === "link" && isset($entry["href"]) && filter_var($entry["href"], FILTER_VALIDATE_URL)) {
                    list($url_previews["media/carusell/" . strval($i) . "/media"], $msg) = fetch_url_preview($entry["href"], $url_preview_user_agent, $url_preview_ttl_seconds, $url_preview_oembed_url);
                }
            }
        }

        // Check media/carusell/{i}/description contains URLs, extract them and fetch previews
        if (isset($data["media"]) && isset($data["media"]["carusell"]) && is_array($data["media"]["carusell"])) {
            foreach ($data["media"]["carusell"] as $i => $entry) {
                if (isset($entry["description"]) && is_string($entry["description"])) {
                    // Extract URLs from the description
                    preg_match_all('/https?:\/\/[^\s]+/', $entry["description"], $matches);
                    foreach ($matches[0] as $url) {
                        list($url_previews["media/carusell/" . strval($i) . "/description"], $msg) = fetch_url_preview($url, $url_preview_user_agent, $url_preview_ttl_seconds, $url_preview_oembed_url);
                    }
                }
            }
        }


        $data["url_previews"] = $url_previews;
    }

    req_send(true, $msg, 200, $data); // HTTP code 200 : OK
}