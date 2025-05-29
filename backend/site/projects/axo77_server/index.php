<?php
header("Content-Type: application/json; charset=utf-8");

require_once("../../_php_common_/env.php");
require_once("../../_php_common_/responders.php");
require_once("../../_php_common_/data_nodes.php");
require_once("../../_php_common_/datetime.php");
require_once("../../_php_common_/libs/php-json-comment.php");
require_once("../../_php_common_/url_preview/fetch.php");

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

    // Unless $req_data["no_url_previews"] is set:
    //   Check if history/description or media/carusell/media or media/carusell/description is url, if so call urlPreview
    if (!isset($req_data["no_url_previews"])) {
        list($url_preview_user_agent, $url_preview_ttl_seconds, $url_preview_oembed_url) = extract_url_params_for_url_preview($req_data);
        // Check history/{i}/description
        if (isset($data["history"]) && is_array($data["history"])) {
            foreach ($data["history"] as $i => $entry) {
                if (isset($entry["description"]) && filter_var($entry["description"], FILTER_VALIDATE_URL)) {
                    list($data["history"][$i]["description_preview"], $msg) = fetch_url_preview($entry["description"], $url_preview_user_agent, $url_preview_ttl_seconds, $url_preview_oembed_url);
                }
            }
        }
        // Check media/carusell/{i}/media

        // Check media/carusell/{i}/description
    }

    req_send(true, $msg, 200, $data); // HTTP code 200 : OK
}