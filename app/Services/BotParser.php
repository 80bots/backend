<?php

namespace App\Services;

class BotParser
{
    /** ABOUT
    {
        "name": "linkedin-track-companies",
        "platform": "Linkedin",
        "description": "maintians a record of no. of employees and jobs posted by companies provided.",
        "icon": "fa-meh-rolling-eyes"
    }
     */

    /** PARAMS
    [
        {
            "name": "username",
            "type": "string",
            "title": "Username",
            "description": "Your Linkedin Username",
            "icon": "fa-user"
        },
        {
            "name": "password",
            "type": "password",
            "title": "Password",
            "description": "Your Linkedin Password",
            "icon": "fa-key"
        },
        {
            "name": "url",
            "type": "string",
            "title": "LinkedinCompanyURL",
            "description": "Linkedin company URL",
            "icon": "fa-link"
        },
        {
            "name": "speed",
            "type": "range",
            "range": "1-9",
            "title": "Speed",
            "description": "How fast the bot operates. Slower is better for staying under the radar.",
            "icon": "fa-tachometer-alt-slowest"
        }
    ]
     */

    private static $regexParams = '/\/\*\*\s*PARAMS[\r\n|\r|\n](.*)\*\//sU';
    private static $regexAbout  = '/\/\*\*\s*ABOUT[\r\n|\r|\n](.*)\*\/[\r\n|\r|\n]+\//sU';

    /**
     * @param string $text
     * @return string
     */
    private static function removeCommentsAndNewLines(string $text): string
    {
        // remove single line comments
        $source = preg_replace('#^\s*//.+$#m', "", $text);
        // remove new lines
        $json = trim(preg_replace('/\s\s+/', ' ', $source));
        return trim(preg_replace('/[\r\n|\r|\n]+/', ' ', $json));
    }

    /**
     * @param $fileContent
     * @return array[]
     */
    public static function getBotInfo($fileContent)
    {
        $result = [
            'params' => [],
            'about' => [],
        ];

        if (preg_match(self::$regexParams, $fileContent, $matches)) {

            // PARAMS
            if (! empty($matches[1])) {
                // Decode to object
                $json = self::removeCommentsAndNewLines($matches[1]);
                $decode = json_decode($json);

                if (! empty($decode)) {

                    foreach ($decode as $item) {
                        if (! empty($item->range)) {
                            $item->range = explode('-', $item->range);
                        }
                    }

                    $result['params'] = $decode;
                }
            }
        }

        if (preg_match(self::$regexAbout, $fileContent, $matches)) {

            // ABOUT
            if (! empty($matches[1])) {
                // Decode to object
                $result['about'] = json_decode(self::removeCommentsAndNewLines($matches[1]));
            }
        }

        return $result;
    }
}
