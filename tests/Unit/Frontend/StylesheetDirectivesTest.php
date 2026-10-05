<?php

namespace Tests\Unit\Frontend;

use Tests\TestCase;

class StylesheetDirectivesTest extends TestCase
{
    public function test_app_css_contains_no_malformed_directives_or_stray_quotes(): void
    {
        $content = file_get_contents(resource_path('css/app.css'));

        $this->assertNotFalse($content, 'Failed to read resources/css/app.css');
        $this->assertStringNotContainsString("blade.php'", $content);
        $this->assertDoesNotMatchRegularExpression('/@source\s+["\'][^"\']+\'[";]/', $content);
    }

    public function test_app_css_imports_resolve_to_existing_files(): void
    {
        $content = file_get_contents(resource_path('css/app.css'));
        $this->assertNotFalse($content, 'Failed to read resources/css/app.css');

        preg_match_all('/@import\s+["\']([^"\']+)["\'];/', $content, $matches);

        $this->assertNotEmpty($matches[1], 'No @import directives found in resources/css/app.css');

        foreach ($matches[1] as $importPath) {
            if ($importPath === 'tailwindcss') {
                continue;
            }

            $resolved = realpath(resource_path('css/'.$importPath));
            $this->assertNotFalse($resolved, "Import target could not be resolved: {$importPath}");
            $this->assertFileExists($resolved);
        }
    }

    public function test_app_css_source_directories_exist_and_contain_templates(): void
    {
        $content = file_get_contents(resource_path('css/app.css'));
        $this->assertNotFalse($content, 'Failed to read resources/css/app.css');

        preg_match_all('/@source\s+["\']([^"\']+)["\'];/', $content, $matches);

        $this->assertNotEmpty($matches[1], 'No @source directives found in resources/css/app.css');

        foreach ($matches[1] as $sourcePattern) {
            $baseDirPattern = preg_replace('/(\/\*.*)$/', '', $sourcePattern);
            $resolved = realpath(resource_path('css/'.$baseDirPattern));

            $this->assertNotFalse($resolved, "Source base path does not resolve: {$sourcePattern}");
            $this->assertDirectoryExists($resolved);
        }
    }
}
