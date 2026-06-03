<?php

namespace Codemonster\Env;

interface ParserInterface
{
    public function parseStringRaw(string $content, ?string $encoding = null): array;

    public function expandVariables(string $value, array $vars): string;

    public function expandVariablesWithReport(string $value, array $vars): array;
}
