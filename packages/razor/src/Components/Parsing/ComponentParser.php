<?php

declare(strict_types=1);

namespace Codemonster\Razor\Components\Parsing;

use Codemonster\Razor\Components\Contracts\ComponentResolverInterface;

final readonly class ComponentParser
{
    public function __construct(private ComponentResolverInterface $resolver)
    {
    }

    public function parseAt(string $source, int $offset): ?ComponentInvocation
    {
        if (preg_match('/\G<([a-z][a-z0-9]*(?:-[a-z0-9]+)+)\b/', $source, $match, 0, $offset) !== 1) {
            return null;
        }

        $tag = $match[1];

        if (!$this->resolver->handles($tag)) {
            return null;
        }

        $attributesStart = $offset + strlen($match[0]);
        $end = $this->findSelfClosingEnd($source, $attributesStart);

        if ($end === null) {
            return null;
        }

        return new ComponentInvocation(
            $tag,
            trim(substr($source, $attributesStart, $end - $attributesStart)),
            null,
            $offset,
            $end + 2,
        );
    }

    private function findSelfClosingEnd(string $source, int $offset): ?int
    {
        $quote = null;
        $escaped = false;
        $length = strlen($source);

        for ($index = $offset; $index < $length - 1; $index++) {
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

            if ($character === '"' || $character === "'") {
                $quote = $character;
                continue;
            }

            if ($character === '>' && ($source[$index - 1] ?? '') !== '/') {
                return null;
            }

            if ($character === '/' && $source[$index + 1] === '>') {
                return $index;
            }
        }

        return null;
    }
}
