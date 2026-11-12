<?php

declare(strict_types=1);

namespace Nitro\View;

use RuntimeException;
use Throwable;

/**
 * Lightweight PHP template engine with layouts, sections and
 * automatic HTML escaping.
 */
final class ViewEngine
{
    /** @var array<string, string> */
    private array $sections = [];

    /** @var array<int, string> */
    private array $sectionStack = [];

    private ?string $layout = null;

    public function __construct(
        private readonly string $viewPath,
        private readonly string $cachePath
    ) {
    }

    /**
     * Render a view with data; returns the compiled output.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string
    {
        $file = $this->resolve($template);

        $data['__engine'] = $this;

        extract($data, EXTR_SKIP);

        ob_start();

        try {
            include $file;
        } catch (Throwable $e) {
            ob_end_clean();

            throw new RuntimeException(sprintf('View "%s" failed to render: %s', $template, $e->getMessage()), 0, $e);
        }

        $content = (string) ob_get_clean();

        if ($this->layout !== null) {
            $layout = $this->layout;
            $this->layout = null;
            $this->sections['content'] = $content;

            return $this->render($layout, $data);
        }

        return $content;
    }

    /** Inside a template: extend a layout. */
    public function extends(string $template): void
    {
        $this->layout = $template;
    }

    /** Inside a template: start capturing a named section. */
    public function start(string $section): void
    {
        $this->sectionStack[] = $section;
        ob_start();
    }

    /** Inside a template: stop capturing the current section. */
    public function stop(): void
    {
        $section = array_pop($this->sectionStack);

        if ($section === null) {
            throw new RuntimeException('stop() called without a matching start().');
        }

        $this->sections[$section] = (string) ob_get_clean();
    }

    /** Inside a template: yield a section with an optional default. */
    public function section(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function hasSection(string $name): bool
    {
        return isset($this->sections[$name]);
    }

    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function resolve(string $template): string
    {
        $file = rtrim($this->viewPath, '/') . '/' . str_replace('.', '/', $template) . '.php';

        if (! is_file($file)) {
            throw new RuntimeException(sprintf('View "%s" not found at "%s".', $template, $file));
        }

        return $file;
    }
}
