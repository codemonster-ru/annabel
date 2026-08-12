<?php

declare(strict_types=1);

namespace Codemonster\Razor\Components\Compilation;

use Codemonster\Razor\Exceptions\RazorException;

final class ComponentPropCompiler
{
    public function compile(string $attributes): string
    {
        $props = [];
        $offset = 0;
        $length = strlen($attributes);

        while ($offset < $length) {
            $offset = $this->skipWhitespace($attributes, $offset);

            if ($offset >= $length) {
                break;
            }

            if (preg_match('/\G(:?)([a-z][a-z0-9]*(?:-[a-z0-9]+)*)/', $attributes, $match, 0, $offset) !== 1) {
                throw new RazorException('Malformed Razor component prop near [' . substr($attributes, $offset) . '].');
            }

            $expression = $match[1] === ':';
            $name = $match[2];
            $offset += strlen($match[0]);

            if (isset($props[$name])) {
                throw new RazorException("Duplicate Razor component prop [{$name}].");
            }

            $offset = $this->skipWhitespace($attributes, $offset);

            if (($attributes[$offset] ?? '') !== '=') {
                if ($expression) {
                    throw new RazorException("Expression prop [{$name}] requires a quoted value.");
                }

                $props[$name] = 'true';
                continue;
            }

            $offset = $this->skipWhitespace($attributes, $offset + 1);
            $quote = $attributes[$offset] ?? '';

            if ($quote !== '"' && $quote !== "'") {
                throw new RazorException("Razor component prop [{$name}] requires a quoted value.");
            }

            [$value, $offset] = $this->readQuotedValue($attributes, $offset, $quote, $name);

            if ($expression) {
                if (trim($value) === '') {
                    throw new RazorException("Expression prop [{$name}] must not be empty.");
                }

                $props[$name] = '(' . $value . ')';
            } else {
                $props[$name] = var_export(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            }

            if ($offset < $length && preg_match('/\s/', $attributes[$offset]) !== 1) {
                throw new RazorException("Razor component props must be separated by whitespace near [{$name}].");
            }
        }

        $entries = [];

        foreach ($props as $name => $value) {
            $entries[] = var_export($name, true) . ' => ' . $value;
        }

        return '[' . implode(', ', $entries) . ']';
    }

    /** @return array{string, int} */
    private function readQuotedValue(string $attributes, int $offset, string $quote, string $name): array
    {
        $start = $offset + 1;
        $length = strlen($attributes);

        for ($index = $start; $index < $length; $index++) {
            if ($attributes[$index] === $quote && ($attributes[$index - 1] ?? '') !== '\\') {
                return [substr($attributes, $start, $index - $start), $index + 1];
            }
        }

        throw new RazorException("Unclosed quoted value for Razor component prop [{$name}].");
    }

    private function skipWhitespace(string $source, int $offset): int
    {
        $length = strlen($source);

        while ($offset < $length && preg_match('/\s/', $source[$offset]) === 1) {
            $offset++;
        }

        return $offset;
    }
}
