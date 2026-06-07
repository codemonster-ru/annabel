<?php

namespace Codemonster\Razor;

class Compiler
{
    protected string $cachePath;

    public function __construct(string $cachePath)
    {
        $this->cachePath = rtrim($cachePath, '/');

        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }
    }

    public function compile(string $file): string
    {
        $cacheFile = $this->cachePath . '/' . md5($file) . '.php';

        if (!file_exists($cacheFile) || filemtime($file) > filemtime($cacheFile)) {
            $contents = file_get_contents($file);

            if ($contents === false) {
                throw new \RuntimeException("Unable to read Razor template: {$file}");
            }

            $contents = str_replace(['{{', '}}'], ['<?= htmlspecialchars(', '); ?>'], $contents);
            $contents = preg_replace('/@if\s*\((.*?)\)/', '<?php if ($1): ?>', $contents) ?? $contents;
            $contents = str_replace('@endif', '<?php endif; ?>', $contents);
            $contents = preg_replace('/@foreach\s*\((.*?)\)/', '<?php foreach ($1): ?>', $contents) ?? $contents;
            $contents = str_replace('@endforeach', '<?php endforeach; ?>', $contents);
            $contents = preg_replace(
                '/@include\s*\(\s*[\'"](.*?)[\'"]\s*\)/',
                '<?php echo $this->render("$1", get_defined_vars()); ?>',
                $contents
            ) ?? $contents;

            file_put_contents($cacheFile, $contents);
        }

        return $cacheFile;
    }
}
