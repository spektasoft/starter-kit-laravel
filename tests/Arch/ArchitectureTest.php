<?php

namespace Tests\Arch;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ArchitectureTest extends TestCase
{
    /**
     * Ensure no classes in App\Filament import or use RichEditor directly,
     * except for the allowed CuratorEnabledRichEditor itself.
     */
    public function test_no_direct_rich_editor_calls_in_filament(): void
    {
        $directoryPath = __DIR__.'/../../app/Filament';

        if (! is_dir($directoryPath)) {
            $this->markTestSkipped('Filament directory not found.');
        }

        $directory = new RecursiveDirectoryIterator($directoryPath);
        $iterator = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filePath = $file->getRealPath();

                // Skip the custom component itself if it resides within App\Filament
                if (str_contains($filePath, 'CuratorEnabledRichEditor.php')) {
                    continue;
                }

                /** @var string */
                $content = file_get_contents($filePath);

                // Check for direct imports or fully qualified usages of RichEditor
                $hasDirectImport = str_contains($content, 'use Filament\Forms\Components\RichEditor;');
                $hasInlineUsage = str_contains($content, ' \Filament\Forms\Components\RichEditor');

                $this->assertFalse(
                    $hasDirectImport || $hasInlineUsage,
                    "File contains a direct reference to RichEditor: {$filePath}"
                );
            }
        }
    }
}
