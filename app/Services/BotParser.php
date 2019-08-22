<?php

namespace App\Services;

class BotParser {
    private static $regex = '/(\/\*\s*PARAMS\n((.|\n)*))\*\/\n*(\/\*\s*ABOUT\n((.|\n)*))\*\//';

    private static function parseParams($paramsString): array {
        $params = [];
        $unparsedParams = explode("\n", $paramsString);
        $unparsedParams = array_filter($unparsedParams, function($item) { return !empty($item); });
        foreach ($unparsedParams as $parameter) {
            $data = [];
            preg_match('/\*\s@(.*) (.*)\s\[(.*)\]/', $parameter, $data);
            if(empty($data)) preg_match('/\*\s@(.*) (.*)/', $parameter, $data);
            if($data) {
                $param = [
                    'name' => $data[1],
                    'type' => $data[2],
                ];
                if(isset($data[3])) {
                    $result = self::parseEnumOrRange($data[3]);
                    $param[$result['type']] = $result['value'];
                }
                array_push($params, $param);
            }
        }
        return $params;
    }

    private static function parseAbout($aboutString): array {
        $about = [];
        $unparsedParams = explode("\n", $aboutString);
        $unparsedParams = array_filter($unparsedParams, function($item) { return !empty($item); });
        foreach ($unparsedParams as $parameter) {
            $data = [];
            preg_match('/\*\s@(\S*) (.*)/', $parameter, $data);
            if($data) {
                array_push($about, [$data[1] => $data[2]]);
            }
        }
        return $about;
    }

    private static function parseEnumOrRange ($string): array {
        $result = [
          'type' => '',
          'value' => []
        ];
        if(preg_match('/\.\./', $string)) {
            $result['type'] = 'range';
            $string = str_replace('..', '', $string);
            $result['value'] = array_values(
                array_filter(
                    explode(',', str_replace(' ', '', $string)),
                    function ($item) { return !empty($item); }
                )
            );
        } else {
            $result['type'] = 'enum';
            $result['value'] = array_filter(explode(',', str_replace(' ', '', $string)),
                function ($item) { return !empty($item); }
            );
        }
        return $result;
    }

    public static function getBotInfo ($filePath) {
        $matches = $params = $about = [];
        $result = [
            'params' => [],
            'about' => [],
        ];
        preg_match(self::$regex, file_get_contents($filePath), $matches);

        if($matches && isset($matches[2]) && !empty($matches[2])) {
            $result['params'] = self::parseParams($matches[2]);
        }
        if($matches && isset($matches[5]) && !empty($matches[5])) {
            $result['about'] = self::parseAbout($matches[5]);
        }

        return $result;
    }

    public static function syncBotsWithDb () {
        $files = array_filter(scandir(base_path('resources/puppeteer/')), function($item) {
            return !is_dir(base_path('resources/puppeteer/') . $item)
                && !preg_match('/^\.git/', $item);
        });

        $bots = array_values(array_filter(array_map(function($item) {
            return self::getBotParams(base_path('resources/puppeteer/') .$item);
        }, $files), function($item) { return !empty($item); } ));
    }
}
