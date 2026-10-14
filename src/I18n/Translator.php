<?php

declare(strict_types=1);

namespace FilebrowserNg\I18n;

class Translator
{
    private array $strings = [];

    public function __construct(
        private readonly string $langDir,
        private readonly string $language = 'en',
    ) {
        $this->load($language);
    }

    private function load(string $lang): void
    {
        $file     = $this->langDir . '/' . $lang . '.php';
        $fallback = $this->langDir . '/en.php';

        if (file_exists($file)) {
            $this->strings = require $file;
        } elseif (file_exists($fallback)) {
            $this->strings = require $fallback;
        }
    }

    public function t(string $key, array $replace = []): string
    {
        $str = $this->strings[$key] ?? $key;
        foreach ($replace as $placeholder => $value) {
            $str = str_replace(':' . $placeholder, (string) $value, $str);
        }
        return $str;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }
}
