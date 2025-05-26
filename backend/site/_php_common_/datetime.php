<?php

function detectDatetimeFormat(string $value) {
    $patterns = [
        // dates
        '/^\d{4}$/' => 'yyyy',
        '/^\d{4}\/\d{2}$/'  => 'yyyy/MM',
        '/^\d{4}-\d{2}$/'  => 'yyyy-MM',
        '/^\d{4}\/\d{2}\/\d{2}$/'  => 'yyyy/MM/dd',
        '/^\d{4}-\d{2}-\d{2}$/' => 'yyyy-MM-dd',

        // date + hh
        '/^\d{4}\/\d{2}\/\d{2} \d{2}$/' => 'yyyy/MM/dd hh',
        '/^\d{4}-\d{2}-\d{2} \d{2}$/' => 'yyyy-MM-dd hh',

        // date + hh:mm
        '/^\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}$/' => 'yyyy/MM/dd hh:mm',
        '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/' => 'yyyy-MM-dd hh:mm',

        // date + hh:mm:ss
        '/^\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}$/' => 'yyyy/MM/dd hh:mm:ss',
        '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/' => 'yyyy-MM-dd hh:mm:ss',

        // date + hh:mm:ss.fff
        '/^\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}\.\d{3}$/' => 'yyyy/MM/dd hh:mm:ss.fff',
        '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{3}$/' => 'yyyy-MM-dd hh:mm:ss.fff',

        // date + hh:mm:ss:fff
        '/^\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}\:\d{3}$/' => 'yyyy/MM/dd hh:mm:ss:fff',
        '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\:\d{3}$/' => 'yyyy-MM-dd hh:mm:ss:fff',

        // ISO‑8601 variants
        '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/' => 'yyyy-MM-ddTHH:mm:ssZ',
        '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+\-]\d{2}:\d{2}$/' => 'yyyy-MM-ddTHH:mm:ss±HH:mm',
        '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/' => 'yyyy-MM-ddTHH:mm:ss.fffZ',
        '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}[+\-]\d{2}:\d{2}$/' => 'yyyy-MM-ddTHH:mm:ss.fff±HH:mm',

        // alphabetic months
        '/^[A-Za-z]{3} \d{2} \d{4}$/'  => 'MMM dd yyyy',
        '/^\d{4} \d{2} [A-Za-z]{3}$/' => 'yyyy dd MMM',
        '/^[A-Za-z]+ \d{2} \d{4}$/' => 'MMMM dd yyyy',
        '/^\d{4} \d{2} [A-Za-z]+$/' => 'yyyy dd MMMM',
    ];

    foreach ($patterns as $regex => $format) {
        if (preg_match($regex, $value)) {
            return $format;
        }
    }
    return "unknown";
}