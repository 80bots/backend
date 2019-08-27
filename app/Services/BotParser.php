<?php

namespace App\Services;

class BotParser
{
    /** PARAMS
    {
        "email": {
            "type": "String",
            "title": "Email",
            "description": "Your Facebook Email",
            "icon": "fa-email"
        },
        "password": {
            "type": "password",
            "title": "Password",
            "description": "Your Facebook Password",
            "icon": "fa-key"
        },
        "speed": {
            "type": "range",
            // if no title, Capitalise the key by default
            "range": "1-9"
            // should be presented to the user as a slider
        }
    }
     */

    /** ABOUT
    {
        "name": "facebook-find-posts-by-keyword bot",
        "description": "Scrolls through your facebook feed and mails the posts that have the keyword"
    }
     */

    private static $regex = '/\/\*\*\s*PARAMS\n(.*)\*\/\s*\/\*\*\sABOUT\n(.*)\*\//s';

    private static function removeCommentsAndNewLines(string $text): string
    {
        // remove single line comments
        $source = preg_replace('#^\s*//.+$#m', "", $text);
        // remove new lines
        $json = trim(preg_replace('/\s\s+/', ' ', $source));
        return trim(preg_replace('/\n+/', ' ', $json));
    }

    public static function getBotInfo($fileContent)
    {
        $result = [
            'params' => [],
            'about' => [],
        ];

        if (preg_match(self::$regex, $fileContent, $matches)) {

            // PARAMS
            if (! empty($matches[1])) {
                // Decode to object
                $decode = json_decode(self::removeCommentsAndNewLines($matches[1]));

                if (! empty($decode)) {
                    $params = [];
                    foreach ((array)$decode as $key => $param) {
                        $params[] = array_merge(['name' => $key], (array)$param);
                    }
                    $result['params'] = $params;
                }
            }

            // ABOUT
            if (! empty($matches[2])) {
                // Decode to object
                $result['about'] = json_decode(self::removeCommentsAndNewLines($matches[2]));
            }
        }

        return $result;
    }
}
