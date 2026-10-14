<?php

declare(strict_types=1);

namespace Tests\Unit;

use FilebrowserNg\I18n\Translator;
use PHPUnit\Framework\TestCase;

class TranslatorTest extends TestCase
{
    private string $langDir;

    protected function setUp(): void
    {
        $this->langDir = dirname(__DIR__, 2) . '/lang';
    }

    public function testTranslatesKnownKey(): void
    {
        $t = new Translator($this->langDir, 'en');
        $this->assertSame('filebrowser-ng', $t->t('app.name'));
    }

    public function testFallsBackToKeyForUnknown(): void
    {
        $t = new Translator($this->langDir, 'en');
        $this->assertSame('unknown.key', $t->t('unknown.key'));
    }

    public function testReplacesPlaceholders(): void
    {
        $t = new Translator($this->langDir, 'en');
        $result = $t->t('nav.logged_in_as', ['user' => 'alice']);
        $this->assertStringContainsString('alice', $result);
    }

    public function testGermanTranslation(): void
    {
        $t = new Translator($this->langDir, 'de');
        $this->assertSame('Anmelden', $t->t('login.title'));
    }

    public function testGetLanguage(): void
    {
        $t = new Translator($this->langDir, 'de');
        $this->assertSame('de', $t->getLanguage());
    }
}
