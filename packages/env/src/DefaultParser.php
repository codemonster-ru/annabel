<?php

namespace Codemonster\Env;

class DefaultParser implements ParserInterface
{
    private bool $asciiNames;

    public function __construct(bool $asciiNames = false)
    {
        $this->asciiNames = $asciiNames;
    }

    /** @return list<array{string, string|null, list<int>}> */
    public function parseStringRaw(string $content, ?string $encoding = null): array
    {
        return EnvParser::parseStringRawWithOptions($content, $encoding, $this->asciiNames);
    }

    /** @param list<int> $vars */
    public function expandVariables(string $value, array $vars): string
    {
        return EnvParser::expandVariables($value, $vars);
    }

    /**
     * @param list<int> $vars
     * @return array{string, list<string>}
     */
    public function expandVariablesWithReport(string $value, array $vars): array
    {
        return EnvParser::expandVariablesWithReport($value, $vars);
    }
}
