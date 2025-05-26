<?php
header("Content-Type: application/json; charset=utf-8");

require_once("../../_php_common_/env.php");
require_once("../../_php_common_/responders.php");
require_once("../../_php_common_/data_nodes.php");
require_once("../../_php_common_/datetime.php");
require_once("../../_php_common_/libs/php-json-comment.php");

req_require_method("GET");

list($data, $msg) = read_data_node_json_file("projects", "axo77_server/data.jsonc");

if ($data === null) {
    req_send(false, $msg, 500); // HTTP code 500 : Internal Server Error
} else {
    // Auto handle datetimes in the history data, so if the entries "date_format" is "auto" we auto-identify the datetime format
    if (isset($data["history"])) {
        $i = 0;
        foreach ($data["history"] as $entry) {
            if (isset($entry["date_format"]) && $entry["date_format"] === "auto" && isset($entry["date"])) {
                $data["history"][$i]["date_format"] = detectDatetimeFormat($entry["date"]);
            }
            $i++;
        }
    }
    req_send(true, $msg, 200, $data); // HTTP code 200 : OK
}