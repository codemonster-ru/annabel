<?php

declare(strict_types=1);

namespace Codemonster\Razor\Components\Compilation;

use Closure;
use Codemonster\Razor\Components\Contracts\ComponentResolverInterface;
use Codemonster\Razor\Components\Parsing\ComponentParser;
use Codemonster\Razor\Exceptions\RazorException;

final readonly class ComponentTemplateCompiler
{
    public function __construct(
        private ComponentParser $parser,
        private ComponentInvocationCompiler $invocations,
        private ComponentResolverInterface $resolver,
    ) {
    }

    /** @param Closure(string): string $compileRazor */
    public function compile(
        string $source,
        Closure $compileRazor,
        string $file = 'Razor template',
        int $lineOffset = 0,
    ): string
    {
        $compiled = '';
        $cursor = 0;
        $plainStart = 0;
        $length = strlen($source);

        while ($cursor < $length) {
            $start = strpos($source, '<', $cursor);

            if ($start === false) {
                break;
            }

            try {
                $invocation = $this->parser->parseAt($source, $start);
            } catch (RazorException $exception) {
                throw $this->diagnostic($exception, $file, $source, $start, $lineOffset);
            }

            if ($invocation === null) {
                if (preg_match('/\G<\/([a-z][a-z0-9]*(?:-[a-z0-9]+)+)\s*>/', $source, $match, 0, $start) === 1
                    && $this->resolver->handles($match[1])) {
                    throw $this->diagnostic(
                        new RazorException("Unexpected Razor component closing tag [</{$match[1]}>]."),
                        $file,
                        $source,
                        $start,
                        $lineOffset,
                    );
                }

                if (str_starts_with(substr($source, $start), '<razor-slot')) {
                    throw $this->diagnostic(
                        new RazorException('A Razor named slot must be a direct child of a component.'),
                        $file,
                        $source,
                        $start,
                        $lineOffset,
                    );
                }

                $cursor = $start + 1;
                continue;
            }

            if ($this->resolver->resolve($invocation->tag) === null) {
                throw $this->diagnostic(
                    new RazorException("Unknown Razor component [{$invocation->tag}]."),
                    $file,
                    $source,
                    $start,
                    $lineOffset,
                );
            }

            $compiled .= $compileRazor(substr($source, $plainStart, $start - $plainStart));

            try {
                $nestedLineOffset = $lineOffset + substr_count(substr($source, 0, $start), "\n");
                $compiled .= $this->invocations->compile(
                    $invocation,
                    fn (string $content): string => $this->compile($content, $compileRazor, $file, $nestedLineOffset),
                );
            } catch (RazorException $exception) {
                throw $this->diagnostic($exception, $file, $source, $start, $lineOffset);
            }

            $plainStart = $invocation->endOffset;
            $cursor = $plainStart;
        }

        return $compiled . $compileRazor(substr($source, $plainStart));
    }

    private function diagnostic(
        RazorException $exception,
        string $file,
        string $source,
        int $offset,
        int $lineOffset,
    ): RazorException
    {
        if (preg_match('/ in ' . preg_quote($file, '/') . ':\d+$/', $exception->getMessage()) === 1) {
            return $exception;
        }

        $line = $lineOffset + substr_count(substr($source, 0, $offset), "\n") + 1;

        return new RazorException($exception->getMessage() . " in {$file}:{$line}", 0, $exception);
    }
}
