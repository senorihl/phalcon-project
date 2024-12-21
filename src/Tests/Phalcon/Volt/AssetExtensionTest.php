<?php

namespace Phalcon\Volt;

use App\Phalcon\Volt\AssetExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AssetExtensionTest extends TestCase
{
    protected function setUp(): void
    {
        $reflectedClass = new \ReflectionClass(AssetExtension::class);
        $reflectedClass->setStaticPropertyValue('assetMap', ['test' => 'test_public']);
    }

    public static function getAssetsCases()
    {
        return [
            ['test', '<link rel="stylesheet" href="test_public" />', '<script type="application/javascript" src="test_public"></script>'],
            ['not_found', null, null],
        ];
    }

    #[DataProvider('getAssetsCases')]
    public function testCss_entry_tag(string $entryName, ?string $expectedCSSTag, ?string $expectedScriptTag)
    {
        $this->assertEquals($expectedCSSTag, AssetExtension::css_entry_tag($entryName));
    }

    #[DataProvider('getAssetsCases')]
    public function testJs_entry_tag(string $entryName, ?string $expectedCSSTag, ?string $expectedScriptTag)
    {
        $this->assertEquals($expectedScriptTag, AssetExtension::js_entry_tag($entryName));
    }

    #[DataProvider('getNotNativeFunctionCalls')]
    public function testCompileFunction(string $name, string $arguments, $expected)
    {
        $this->assertEquals($expected, (new AssetExtension())->compileFunction($name, $arguments));
    }

    public static function getNotNativeFunctionCalls()
    {
        return [
            ['js_entry_tag', '"app.js"', 'App\Phalcon\Volt\AssetExtension::js_entry_tag("app.js")'],
            ['css_entry_tag', '"app.css"', 'App\Phalcon\Volt\AssetExtension::css_entry_tag("app.css")'],
            ['dump', '"anything"', null],
            ['json_encode', 'totally_invalid_code', null]
        ];
    }
}
