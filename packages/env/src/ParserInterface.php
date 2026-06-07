<?php

namespace Codemonster\Env;

interface ParserInterface
{
    /** @return list<array{string, string|null, list<int>}> */
    public function parseStringRaw(string $content, ?string $encoding = null): array;

    /** @param list<int> $vars */
    public function expandVariables(string $value, array $vars): string;

    /**
     * @param list<int> $vars
     * @return array{string, list<string>}
     */
    public function expandVariablesWithReport(string $value, array $vars): array;
}
