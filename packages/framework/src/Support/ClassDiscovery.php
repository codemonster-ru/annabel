<?php

namespace Codemonster\Annabel\Support;

final class ClassDiscovery
{
    /**
     * @param list<string> $paths
     * @return list<class-string>
     */
    public function discover(array $paths): array
    {
        $classes = [];

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            foreach ($this->phpFiles($path) as $file) {
                require_once $file;

                $class = $this->classFromFile($file);
                if ($class !== null && class_exists($class)) {
                    $classes[] = $class;
                }
            }
        }

        return array_values(array_unique($classes));
    }

    /**
     * @return list<string>
     */
    private function phpFiles(string $path): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
        );
        $files = [];

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    /**
     * @return class-string|null
     */
    private function classFromFile(string $file): ?string
    {
        $contents = file_get_contents($file);

        if ($contents === false) {
            return null;
        }

        $tokens = token_get_all($contents);
        $namespace = '';

        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = '';

                for ($j = $i + 1; $j < $count; $j++) {
                    $part = $tokens[$j];

                    if (is_array($part) && ($part[0] === T_STRING || $part[0] === T_NAME_QUALIFIED)) {
                        $namespace .= $part[1];
                    } elseif (is_array($part) && $part[0] === T_NS_SEPARATOR) {
                        $namespace .= '\\';
                    } elseif ($part === ';' || $part === '{') {
                        break;
                    }
                }
            }

            if ($token[0] !== T_CLASS) {
                continue;
            }

            $prev = $tokens[$i - 1] ?? null;
            if (is_array($prev) && $prev[0] === T_NEW) {
                continue;
            }

            for ($j = $i + 1; $j < $count; $j++) {
                $part = $tokens[$j];

                if (is_array($part) && $part[0] === T_STRING) {
                    /** @var class-string $class */
                    $class = $namespace !== '' ? $namespace . '\\' . $part[1] : $part[1];

                    return $class;
                }
            }
        }

        return null;
    }
}
