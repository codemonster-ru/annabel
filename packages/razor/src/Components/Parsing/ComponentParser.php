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
        $opening = $this->findOpeningEnd($source, $attributesStart);

        if ($opening === null) {
            return null;
        }

        [$openingEnd, $selfClosing] = $opening;

        if (!$selfClosing) {
            $closing = $this->findClosingTag($source, $openingEnd + 1, $tag);

            if ($closing === null) {
                return null;
            }

            [$closingStart, $endOffset] = $closing;

            return new ComponentInvocation(
                $tag,
                trim(substr($source, $attributesStart, $openingEnd - $attributesStart)),
                substr($source, $openingEnd + 1, $closingStart - $openingEnd - 1),
                $offset,
                $endOffset,
            );
        }

        return new ComponentInvocation(
            $tag,
            trim(substr($source, $attributesStart, $openingEnd - $attributesStart - 1)),
            null,
            $offset,
            $openingEnd + 1,
        );
    }

    /** @return array{int, bool}|null */
    private function findOpeningEnd(string $source, int $offset): ?array
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

            if ($character === '"' || $character === "'") {
                $quote = $character;
                continue;
            }

            if ($character === '>') {
                return [$index, ($source[$index - 1] ?? '') === '/'];
            }
        }

        return null;
    }

    /** @return array{int, int}|null */
    private function findClosingTag(string $source, int $offset, string $tag): ?array
    {
        $length = strlen($source);

        for ($cursor = $offset; $cursor < $length;) {
            $nextTag = strpos($source, '<', $cursor);

            if ($nextTag === false) {
                return null;
            }

            if (preg_match('/\G<\/' . preg_quote($tag, '/') . '\s*>/', $source, $match, 0, $nextTag) === 1) {
                return [$nextTag, $nextTag + strlen($match[0])];
            }

            $nested = $this->parseAt($source, $nextTag);

            if ($nested !== null) {
                $cursor = $nested->endOffset;
                continue;
            }

            $cursor = $nextTag + 1;
        }

        return null;
    }
}
