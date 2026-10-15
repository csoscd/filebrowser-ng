<?php

declare(strict_types=1);

namespace FilebrowserNg\View;

use FilebrowserNg\I18n\Translator;

class TemplateEngine
{
    /** @var array<string, mixed> */
    private array $globals = [];

    public function __construct(
        private readonly string $templateDir,
        private readonly Translator $translator,
    ) {}

    /** @param array<string, mixed> $globals */
    public function setGlobals(array $globals): void
    {
        $this->globals = $globals;
    }

    public function render(string $template, array $vars = [], ?string $layout = 'layout'): string
    {
        $content = $this->renderPartial($template, $vars);

        if ($layout !== null) {
            $vars['content'] = $content;
            return $this->renderPartial($layout, $vars);
        }

        return $content;
    }

    public function renderPartial(string $template, array $vars = []): string
    {
        $file = $this->templateDir . '/' . $template . '.php';
        if (!file_exists($file)) {
            throw new \RuntimeException("Template not found: {$template}");
        }

        $t = $this->translator;
        extract(array_merge($this->globals, $vars), EXTR_SKIP);

        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    public function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
