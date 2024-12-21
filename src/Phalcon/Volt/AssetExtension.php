<?php

namespace App\Phalcon\Volt;

class AssetExtension
{
    /**
     * @var array
     */
    private static array $assetMap = [];

    /**
     * @codeCoverageIgnore
     */
    public function initialize()
    {
        if (file_exists(realpath(BASE_PATH . '/public/assets/manifest.json'))) {
            $content = file_get_contents(BASE_PATH . '/public/assets/manifest.json');
            $content = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                self::$assetMap = $content;
            }
        }
    }

    public function compileFunction(string $name, string $arguments)
    {
        if (in_array($name, ['js_entry_tag', 'css_entry_tag']) && method_exists(get_called_class(), $name)) {
            return sprintf('%s::%s(%s)', get_called_class(), $name, $arguments);
        }
    }

    public static function js_entry_tag(string $name)
    {
        if (!empty(self::$assetMap[$name])) {
            return sprintf('<script type="application/javascript" src="%s"></script>', htmlentities(self::$assetMap[$name]));
        }
    }

    public static function css_entry_tag(string $name)
    {
        if (!empty(self::$assetMap[$name])) {
            return sprintf('<link rel="stylesheet" href="%s" />', htmlentities(self::$assetMap[$name]));
        }
    }

}