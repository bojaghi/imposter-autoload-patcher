<?php

namespace Bojaghi\ImposterAutoloadPatcher\Tests;

use Bojaghi\ImposterAutoloadPatcher\AutoloadPatcher;
use WP_UnitTestCase;

class TestAutoloadPatcher extends WP_UnitTestCase
{
    private AutoloadPatcher $patcher;

    public function setUp(): void
    {
        $this->patcher = new AutoloadPatcher(__DIR__ . '/test-obj/test-composer.json');
    }

    /**
     * @throws \ReflectionException
     */
    public function testPatch(): void
    {
        $propVendor = getAccessibleProperty(
            $this->patcher::class,
            'vendor'
        );

        $vendor  = $propVendor->getValue($this->patcher);
        $file    = __DIR__ . "/test-obj/$vendor/composer/autoload_static.php";
        $content = file_get_contents($file);

        // Never directly call $this->patcher->patch();
        $patchLength = getAccessibleMethod(
            $this->patcher::class,
            'patchLength'
        );

        $patchDirs = getAccessibleMethod(
            $this->patcher::class,
            'patchDirs'
        );

        $content = $patchLength->invoke($this->patcher, $content);
        $content = $patchDirs->invoke($this->patcher, $content);

        $expected = file_get_contents(__DIR__ . '/test-obj/test-vendor/composer/autoload_static.patched.php');

        $this->assertEquals($expected, $content);

        $this->patcher->patchInstalled(__DIR__ . '/test-obj/test-vendor/composer/installed.json');
    }
}