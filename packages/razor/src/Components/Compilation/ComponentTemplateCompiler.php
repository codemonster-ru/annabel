<?php

declare(strict_types=1);

namespace Codemonster\Razor\Components\Compilation;

use Closure;
use Codemonster\Razor\Components\Parsing\ComponentParser;

final readonly class ComponentTemplateCompiler
{
    public function __construct(
        private ComponentParser $parser,
        private ComponentInvocationCompiler $invocations,
    ) {
    }

    /** @param Closure(string): string $compileRazor */
    public function compile(string $source, Closure $compileRazor): string
    {
        $compiled = '';
        $cursor = 0;
        $length = strlen($source);

        while ($cursor < $length) {
            $start = strpos($source, '<', $cursor);

            if ($start === false) {
                break;
            }

            $invocation = $this->parser->parseAt($source, $start);

            if ($invocation === null) {
                $cursor = $start + 1;
                continue;
            }

            $compiled .= $compileRazor(substr($source, 0, $start));
            $compiled .= $this->invocations->compile(
                $invocation,
                fn (string $content): string => $this->compile($content, $compileRazor),
            );
            $source = substr($source, $invocation->endOffset);
            $cursor = 0;
            $length = strlen($source);
        }

        return $compiled . $compileRazor($source);
    }
}
