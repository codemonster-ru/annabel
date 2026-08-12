<?php

declare(strict_types=1);

namespace Codemonster\Razor\Components\Parsing;

use Codemonster\Razor\Exceptions\RazorException;

final readonly class ComponentSlotParser
{
    public function __construct(private ComponentParser $components)
    {
    }

    public function parse(string $content): ComponentSlots
    {
        $default = '';
        $named = [];
        $cursor = 0;
        $htmlDepth = 0;
        $length = strlen($content);

        while ($cursor < $length) {
            $start = strpos($content, '<', $cursor);

            if ($start === false) {
                $default .= substr($content, $cursor);
                break;
            }

            $default .= substr($content, $cursor, $start - $cursor);
            $nested = $this->components->parseAt($content, $start);

            if ($nested !== null) {
                $default .= substr($content, $start, $nested->endOffset - $start);
                $cursor = $nested->endOffset;
                continue;
            }

            if (!str_starts_with(substr($content, $start), '<razor-slot')) {
                if (preg_match('/\G<\/([a-z][a-z0-9-]*)\s*>/', $content, $match, 0, $start) === 1) {
                    $default .= $match[0];
                    $htmlDepth = max(0, $htmlDepth - 1);
                    $cursor = $start + strlen($match[0]);
                    continue;
                }

                if (preg_match('/\G<([a-z][a-z0-9-]*)\b[^>]*>/', $content, $match, 0, $start) === 1) {
                    $default .= $match[0];
                    $void = in_array($match[1], ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'], true);

                    if (!$void && !str_ends_with(rtrim($match[0]), '/>')) {
                        $htmlDepth++;
                    }

                    $cursor = $start + strlen($match[0]);
                    continue;
                }

                $default .= '<';
                $cursor = $start + 1;
                continue;
            }

            if ($htmlDepth > 0) {
                throw new RazorException('Razor named slots must be direct component children.');
            }

            if (preg_match('/\G<razor-slot\s+name=(["\'])([a-z][A-Za-z0-9]*)\1\s*>/', $content, $match, 0, $start) !== 1) {
                throw new RazorException('Malformed Razor named slot declaration.');
            }

            $name = $match[2];

            if (isset($named[$name])) {
                throw new RazorException("Duplicate Razor named slot [{$name}].");
            }

            $slotStart = $start + strlen($match[0]);
            $slotEnd = strpos($content, '</razor-slot>', $slotStart);

            if ($slotEnd === false) {
                throw new RazorException("Unclosed Razor named slot [{$name}].");
            }

            $slotContent = substr($content, $slotStart, $slotEnd - $slotStart);

            if (str_contains($slotContent, '<razor-slot')) {
                throw new RazorException('Razor named slots cannot be nested.');
            }

            $named[$name] = $slotContent;
            $cursor = $slotEnd + strlen('</razor-slot>');
        }

        return new ComponentSlots($default, $named);
    }
}
