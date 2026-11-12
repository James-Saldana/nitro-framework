<?php

declare(strict_types=1);

namespace Tests\View;

use Nitro\View\ViewEngine;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ViewEngineTest extends TestCase
{
    private string $viewPath;

    private ViewEngine $engine;

    protected function setUp(): void
    {
        $this->viewPath = sys_get_temp_dir() . '/nitro-views-' . uniqid();
        mkdir($this->viewPath . '/layouts', 0777, true);

        file_put_contents($this->viewPath . '/layouts/base.php',
            '<title><?php echo $__engine->section(\'title\', \'Default\'); ?></title>'
            . '<body><?php echo $__engine->section(\'content\'); ?></body>'
        );

        file_put_contents($this->viewPath . '/hello.php',
            '<?php $__engine->extends("layouts.base"); ?>'
            . '<?php $__engine->start("title"); ?>Hello<?php $__engine->stop(); ?>'
            . '<?php $__engine->start("content"); ?>Hi, <?php echo \Nitro\View\ViewEngine::escape($name); ?><?php $__engine->stop(); ?>'
        );

        $this->engine = new ViewEngine($this->viewPath, sys_get_temp_dir());
    }

    protected function tearDown(): void
    {
        @unlink($this->viewPath . '/layouts/base.php');
        @unlink($this->viewPath . '/hello.php');
        @rmdir($this->viewPath . '/layouts');
        @rmdir($this->viewPath);
    }

    public function testRendersViewWithLayoutAndEscaping(): void
    {
        $output = $this->engine->render('hello', ['name' => '<b>World</b>']);

        self::assertStringContainsString('<title>Hello</title>', $output);
        self::assertStringContainsString('Hi, &lt;b&gt;World&lt;/b&gt;', $output);
    }

    public function testMissingViewThrows(): void
    {
        $this->expectException(RuntimeException::class);

        $this->engine->render('does.not.exist');
    }
}
