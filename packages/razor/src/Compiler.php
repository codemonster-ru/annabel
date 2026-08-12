<?php

declare(strict_types=1);

namespace Codemonster\Razor;

use Codemonster\Razor\Components\Compilation\ComponentInvocationCompiler;
use Codemonster\Razor\Components\Compilation\ComponentPropCompiler;
use Codemonster\Razor\Components\Compilation\ComponentTemplateCompiler;
use Codemonster\Razor\Components\ComponentRegistry;
use Codemonster\Razor\Components\Contracts\ComponentResolverInterface;
use Codemonster\Razor\Components\Parsing\ComponentParser;
use Codemonster\Razor\Components\Parsing\ComponentSlotParser;
use Codemonster\Razor\Exceptions\RazorException;

final class Compiler
{
    private const CACHE_VERSION = '3';

    private readonly string $cachePath;
    private readonly ComponentResolverInterface $components;
    private readonly ComponentTemplateCompiler $componentCompiler;

    public function __construct(string $cachePath, ?ComponentResolverInterface $components = null)
    {
        $this->cachePath = rtrim($cachePath, DIRECTORY_SEPARATOR);

        if ($this->cachePath === '') {
            throw new RazorException('The Razor cache path must not be empty.');
        }

        if (!is_dir($this->cachePath) && !@mkdir($this->cachePath, 0775, true) && !is_dir($this->cachePath)) {
            throw new RazorException("Unable to create Razor cache directory: {$this->cachePath}");
        }

        if (!is_writable($this->cachePath)) {
            throw new RazorException("Razor cache directory is not writable: {$this->cachePath}");
        }

        $this->components = $components ?? new ComponentRegistry();
        $parser = new ComponentParser($this->components);
        $this->componentCompiler = new ComponentTemplateCompiler(
            $parser,
            new ComponentInvocationCompiler(
                new ComponentPropCompiler(),
                new ComponentSlotParser($parser),
            ),
        );
    }

    public function compile(string $file): string
    {
        $realFile = realpath($file);

        if ($realFile === false || !is_file($realFile) || !is_readable($realFile)) {
            throw new RazorException("Unable to read Razor template: {$file}");
        }

        $source = file_get_contents($realFile);

        if ($source === false) {
            throw new RazorException("Unable to read Razor template: {$realFile}");
        }

        $signature = hash(
            'sha256',
            self::CACHE_VERSION . "\0" . $this->components->cacheSignature() . "\0" . $realFile . "\0" . $source,
        );
        $cacheFile = $this->cachePath . DIRECTORY_SEPARATOR . hash('sha256', $realFile) . '.php';
        $marker = "<?php /* razor-cache:{$signature} */ ?>";

        if ($this->cacheIsFresh($cacheFile, $marker)) {
            return $cacheFile;
        }

        // PHP consumes the first newline after a closing tag. Keep a dedicated
        // separator so the template's leading whitespace remains unchanged.
        $compiled = $marker . "\n" . $this->compileSource($source, $realFile);
        $temporary = $cacheFile . '.' . bin2hex(random_bytes(6)) . '.tmp';

        try {
            if (file_put_contents($temporary, $compiled, LOCK_EX) === false) {
                throw new RazorException("Unable to write compiled Razor template: {$temporary}");
            }

            if (!@rename($temporary, $cacheFile)) {
                throw new RazorException("Unable to publish compiled Razor template: {$cacheFile}");
            }
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }

        return $cacheFile;
    }

    private function cacheIsFresh(string $cacheFile, string $marker): bool
    {
        if (!is_file($cacheFile) || !is_readable($cacheFile)) {
            return false;
        }

        $handle = @fopen($cacheFile, 'rb');

        if ($handle === false) {
            return false;
        }

        try {
            return fread($handle, max(1, strlen($marker))) === $marker;
        } finally {
            fclose($handle);
        }
    }

    private function compileSource(string $source, string $file): string
    {
        return $this->componentCompiler->compile(
            $source,
            fn (string $segment): string => $this->compileRazorSource($segment, $file),
        );
    }

    private function compileRazorSource(string $source, string $file): string
    {
        $compiled = '';
        $length = strlen($source);

        for ($offset = 0; $offset < $length;) {
            if (substr($source, $offset, 4) === '{{--') {
                $end = strpos($source, '--}}', $offset + 4);

                if ($end === false) {
                    throw $this->syntaxError($file, $source, $offset, 'Unclosed Razor comment.');
                }

                $comment = substr($source, $offset, $end + 4 - $offset);
                $compiled .= str_repeat("\n", substr_count($comment, "\n"));
                $offset = $end + 4;
                continue;
            }

            if (substr($source, $offset, 3) === '{!!') {
                [$expression, $offset] = $this->readEcho($source, $offset + 3, '!!}', $file, $offset);
                $compiled .= '<?= $__razor->raw(' . $expression . ') ?>';
                continue;
            }

            if (substr($source, $offset, 2) === '{{') {
                [$expression, $offset] = $this->readEcho($source, $offset + 2, '}}', $file, $offset);
                $compiled .= '<?= $__razor->escape(' . $expression . ') ?>';
                continue;
            }

            if ($source[$offset] !== '@') {
                $compiled .= $source[$offset];
                $offset++;
                continue;
            }

            if (substr($source, $offset, 3) === '@{{') {
                $compiled .= '{{';
                $offset += 3;
                continue;
            }

            if (($source[$offset + 1] ?? '') === '@') {
                $compiled .= '@';
                $offset += 2;
                continue;
            }

            if (preg_match('/\G@([A-Za-z_][A-Za-z0-9_]*)/', $source, $match, 0, $offset) !== 1) {
                $compiled .= '@';
                $offset++;
                continue;
            }

            $directive = $match[1];
            $directiveStart = $offset;
            $offset += strlen($match[0]);
            $offset = $this->skipHorizontalWhitespace($source, $offset);

            if (in_array($directive, ['if', 'elseif', 'foreach', 'for', 'while', 'switch'], true)) {
                [$arguments, $offset] = $this->readDirectiveArguments($source, $offset, $file, $directiveStart);
                $compiled .= "<?php {$directive} ({$arguments}): ?>";
                continue;
            }

            $terminators = [
                'endif' => 'endif',
                'endforeach' => 'endforeach',
                'endfor' => 'endfor',
                'endwhile' => 'endwhile',
                'endswitch' => 'endswitch',
            ];

            if (isset($terminators[$directive])) {
                $compiled .= "<?php {$terminators[$directive]}; ?>";
                continue;
            }

            if ($directive === 'else') {
                $compiled .= '<?php else: ?>';
                continue;
            }

            if ($directive === 'case') {
                [$arguments, $offset] = $this->readDirectiveArguments($source, $offset, $file, $directiveStart);
                $compiled .= "<?php case {$arguments}: ?>";
                continue;
            }

            if ($directive === 'default') {
                $compiled .= '<?php default: ?>';
                continue;
            }

            if (in_array($directive, ['break', 'continue'], true)) {
                if (($source[$offset] ?? '') === '(') {
                    [$arguments, $offset] = $this->readDirectiveArguments($source, $offset, $file, $directiveStart);
                    $compiled .= "<?php if ({$arguments}) { {$directive}; } ?>";
                } else {
                    $compiled .= "<?php {$directive}; ?>";
                }

                continue;
            }

            if ($directive === 'include') {
                [$arguments, $offset] = $this->readDirectiveArguments($source, $offset, $file, $directiveStart);
                $parts = $this->splitArguments($arguments, $file, $source, $directiveStart);

                if (count($parts) < 1 || count($parts) > 2) {
                    throw $this->syntaxError($file, $source, $directiveStart, '@include expects a view and optional data array.');
                }

                $data = $parts[1] ?? '[]';
                $compiled .= '<?= $__razor->includeView(' . $parts[0] . ', $__razorData, ' . $data . ') ?>';
                continue;
            }

            if ($directive === 'extends') {
                [$arguments, $offset] = $this->readDirectiveArguments($source, $offset, $file, $directiveStart);
                $compiled .= '<?php $__razor->extend(' . $arguments . '); ?>';
                continue;
            }

            if ($directive === 'section') {
                [$arguments, $offset] = $this->readDirectiveArguments($source, $offset, $file, $directiveStart);
                $compiled .= '<?php $__razor->startSection(' . $arguments . '); ?>';
                continue;
            }

            if ($directive === 'endsection') {
                $compiled .= '<?php $__razor->endSection(); ?>';
                continue;
            }

            if ($directive === 'yield') {
                [$arguments, $offset] = $this->readDirectiveArguments($source, $offset, $file, $directiveStart);
                $compiled .= '<?= $__razor->yieldSection(' . $arguments . ') ?>';
                continue;
            }

            $compiled .= substr($source, $directiveStart, $offset - $directiveStart);
        }

        return $compiled;
    }

    /** @return array{string, int} */
    private function readEcho(
        string $source,
        int $offset,
        string $terminator,
        string $file,
        int $start,
    ): array {
        $end = $this->findTerminator($source, $offset, $terminator);

        if ($end === null) {
            throw $this->syntaxError($file, $source, $start, "Unclosed {$terminator} expression.");
        }

        $expression = trim(substr($source, $offset, $end - $offset));

        if ($expression === '') {
            throw $this->syntaxError($file, $source, $start, 'Razor output expression must not be empty.');
        }

        return [$expression, $end + strlen($terminator)];
    }

    private function findTerminator(string $source, int $offset, string $terminator): ?int
    {
        $quote = null;
        $escaped = false;
        $length = strlen($source);

        for ($index = $offset; $index < $length; $index++) {
            $character = $source[$index];

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === "'" || $character === '"') {
                $quote = $character;
                continue;
            }

            if (substr($source, $index, strlen($terminator)) === $terminator) {
                return $index;
            }
        }

        return null;
    }

    /** @return array{string, int} */
    private function readDirectiveArguments(
        string $source,
        int $offset,
        string $file,
        int $start,
    ): array {
        if (($source[$offset] ?? '') !== '(') {
            throw $this->syntaxError($file, $source, $start, 'Razor directive expects parentheses.');
        }

        $closing = $this->findClosingParenthesis($source, $offset);

        if ($closing === null) {
            throw $this->syntaxError($file, $source, $start, 'Unclosed Razor directive arguments.');
        }

        $arguments = trim(substr($source, $offset + 1, $closing - $offset - 1));

        if ($arguments === '') {
            throw $this->syntaxError($file, $source, $start, 'Razor directive arguments must not be empty.');
        }

        return [$arguments, $closing + 1];
    }

    private function findClosingParenthesis(string $source, int $offset): ?int
    {
        $depth = 0;
        $quote = null;
        $escaped = false;
        $lineComment = false;
        $blockComment = false;
        $length = strlen($source);

        for ($index = $offset; $index < $length; $index++) {
            $character = $source[$index];
            $next = $source[$index + 1] ?? '';

            if ($lineComment) {
                if ($character === "\n") {
                    $lineComment = false;
                }

                continue;
            }

            if ($blockComment) {
                if ($character === '*' && $next === '/') {
                    $blockComment = false;
                    $index++;
                }

                continue;
            }

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === "'" || $character === '"' || $character === '`') {
                $quote = $character;
                continue;
            }

            if (($character === '/' && $next === '/') || $character === '#') {
                $lineComment = true;
                $index += $character === '/' ? 1 : 0;
                continue;
            }

            if ($character === '/' && $next === '*') {
                $blockComment = true;
                $index++;
                continue;
            }

            if ($character === '(') {
                $depth++;
                continue;
            }

            if ($character === ')') {
                $depth--;

                if ($depth === 0) {
                    return $index;
                }
            }
        }

        return null;
    }

    /** @return list<string> */
    private function splitArguments(string $arguments, string $file, string $source, int $start): array
    {
        $parts = [];
        $partStart = 0;
        $stack = [];
        $quote = null;
        $escaped = false;
        $length = strlen($arguments);

        for ($index = 0; $index < $length; $index++) {
            $character = $arguments[$index];

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === "'" || $character === '"') {
                $quote = $character;
                continue;
            }

            if (in_array($character, ['(', '[', '{'], true)) {
                $stack[] = $character;
                continue;
            }

            if (in_array($character, [')', ']', '}'], true)) {
                array_pop($stack);
                continue;
            }

            if ($character === ',' && $stack === []) {
                $parts[] = trim(substr($arguments, $partStart, $index - $partStart));
                $partStart = $index + 1;
            }
        }

        $parts[] = trim(substr($arguments, $partStart));

        if (in_array('', $parts, true)) {
            throw $this->syntaxError($file, $source, $start, 'Razor directive contains an empty argument.');
        }

        return $parts;
    }

    private function skipHorizontalWhitespace(string $source, int $offset): int
    {
        $length = strlen($source);

        while ($offset < $length && ($source[$offset] === ' ' || $source[$offset] === "\t")) {
            $offset++;
        }

        return $offset;
    }

    private function syntaxError(string $file, string $source, int $offset, string $message): RazorException
    {
        $line = substr_count(substr($source, 0, $offset), "\n") + 1;

        return new RazorException("{$message} [{$file}:{$line}]");
    }
}
