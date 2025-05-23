<?php
/**
 * @file   Comment.php
 * @brief  This is a compressed version of https://github.com/adhocore/php-json-comment
 */

/**
 * Strips block and line comments from a JSON string.
 *
 * This function uses a state machine approach to correctly handle comments
 * within quoted strings and avoid incorrect stripping of URL-like patterns.
 * It also removes trailing commas before closing brackets or braces.
 *
 * @param string $json The JSON string with potential comments.
 * @return string The JSON string with comments and trailing commas removed.
 */
function filter_json(string $json): string {
    $index = -1;      // The current index being scanned
    $inStr = false;   // If current char is within a string
    $comment = 0;     // 0 = no comment, 1 = single line, 2 = multi lines
    $commaPos = -1;   // Holds the backtrace position of a possibly trailing comma
    $return = '';
    $crlf = ["\n" => '\n', "\r" => '\r'];

    // Quick check to avoid processing if no potential comments or trailing commas exist
    if (!\preg_match('%\/(\/|\*)%', $json) && !\preg_match('/,\s*(\}|\])/', $json)) {
        return $json;
    }

    while (isset($json[++$index])) {
        $oldprev = $prev ?? '';
        $prev = $json[$index - 1] ?? '';
        $char = $json[$index];
        $next = $json[$index + 1] ?? '';

        // Check for trailing commas
        if ($char === ',' || $commaPos === -1) {
            $commaPos = $commaPos + ($char === ',' ? 1 : 0);
        } elseif (\ctype_digit($char) || \strpbrk($char, '"tfn{[')) {
            $commaPos = -1;
        } elseif ($char === ']' || $char === '}') {
            $pos = \strlen($return) - $commaPos - 1;
            $return = \substr($return, 0, $pos) . \ltrim(\substr($return, $pos), ',');
            $commaPos = -1;
        } else {
            $commaPos += 1;
        }

        // --- Comment and String Handling ---

        $inStringBefore = $inStr;
        $commentBefore = $comment;

        // Check if currently within a string
        if ($comment === 0 && $char === '"' && $prev !== '\\') {
            $inStr = !$inStr;
        }

        // Adjust inStr state for escaped quotes within strings
        if ($inStr && \in_array($char . $next, ['":', '",', '"]', '"}'], true)) {
             // Check for double escaped quotes to stay in string
            if (!($oldprev === '\\' && $prev === '\\')) {
                 $inStr = false;
             } else {
                 $inStr = true; // Still in string if double escaped
             }
        }

        // Check for start of comments if not in a string and no comment is active
        if (!$inStr && $comment === 0) {
            if ($char . $next === '//') {
                $comment = 1; // Single line comment
            } elseif ($char . $next === '/*') {
                $comment = 2; // Multi line comment
            }
        }

        // Determine if current character should be added to the return string
        if ($inStr || $comment > 0) {
            // If within a string, add character (handle newlines)
             if ($inStr) {
                 $return .= isset($crlf[$char]) ? $crlf[$char] : $char;
             }
            // If within a comment, do not add character
        } else {
            // If not in a string or comment, add the character
            $return .= $char;
        }

        // Check if comment has ended
        $singleEnded = $comment === 1 && $char === "\n";
        $multiEnded = $comment === 2 && $char . $next === '*/';

        if ($singleEnded || $multiEnded) {
            $comment = 0;
            // If single line comment ended with newline, trim trailing whitespace
            if ($singleEnded) {
                 $return = rtrim($return);
            }
             // If multi-line comment ended, skip the next character ('/')
            if ($multiEnded) {
                 $index++;
            }
        }
    }

    return $return;
}