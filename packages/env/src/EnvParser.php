<?php

namespace Codemonster\Env;

class EnvParser
{
    private const STATE_START = 0;
    private const STATE_UNQUOTED = 1;
    private const STATE_SINGLE = 2;
    private const STATE_DOUBLE = 3;
    private const STATE_ESCAPE = 4;
    private const STATE_SPACE = 5;
    private const STATE_COMMENT = 6;

    private const INVALID_END_STATES = [
        self::STATE_SINGLE,
        self::STATE_DOUBLE,
        self::STATE_ESCAPE,
    ];

    private const TOKEN_PATTERNS = [
        '[\r\n]{1,1000}',
        '[^\S\r\n]{1,1000}',
        '\\\\',
        '\'',
        '"',
        '\\#',
        '\\$',
        '([^(\s\\\\\'"\\#\\$)]|\\(|\\)){1,1000}',
    ];

    private function __construct() {}

    /** @return list<array{string, string|null, list<int>}> */
    public static function parseContent(string $content): array
    {
        return self::parseContentWithOptions($content, false);
    }

    /** @return list<array{string, string|null, list<int>}> */
    public static function parseString(string $content, ?string $encoding = null): array
    {
        return self::parseContent(self::normalizeEncoding($content, $encoding, false));
    }

    /** @return list<array{string, string|null, list<int>}> */
    public static function parseStringRaw(string $content, ?string $encoding = null): array
    {
        return self::parseString($content, $encoding);
    }

    /** @return list<array{string, string|null, list<int>}> */
    public static function parseStringRawWithOptions(
        string $content,
        ?string $encoding = null,
        bool $asciiNames = false
    ): array {
        $normalized = self::normalizeEncoding($content, $encoding, false);

        return self::parseContentWithOptions($normalized, $asciiNames);
    }

    public static function normalizeEncoding(string $input, ?string $encoding, bool $convertOnNull = true): string
    {
        if (!function_exists('mb_convert_encoding')) {
            if ($encoding !== null) {
                throw new Exception\InvalidEncodingException('mbstring is required to use custom file encodings.');
            }

            return self::stripUtf8Bom($input);
        }

        if ($encoding === null && !$convertOnNull) {
            return self::stripUtf8Bom($input);
        }

        if ($encoding !== null && function_exists('mb_list_encodings')) {
            if (!in_array($encoding, mb_list_encodings(), true)) {
                throw new Exception\InvalidEncodingException("Illegal character encoding [{$encoding}] specified.");
            }
        }

        $converted = $encoding === null
            ? @mb_convert_encoding($input, 'UTF-8')
            : @mb_convert_encoding($input, 'UTF-8', $encoding);

        if (!is_string($converted)) {
            throw new Exception\InvalidEncodingException(
                sprintf('Conversion from encoding [%s] failed.', $encoding ?? 'NULL')
            );
        }

        return self::stripUtf8Bom($converted);
    }

    /** @param list<int> $vars */
    public static function expandVariables(string $value, array $vars): string
    {
        [$resolved] = self::expandVariablesWithReport($value, $vars);

        return $resolved;
    }

    /**
     * @param list<int> $vars
     * @return array{string, list<string>}
     */
    public static function expandVariablesWithReport(string $value, array $vars): array
    {
        if ($vars === []) {
            return [$value, []];
        }

        $unresolved = [];

        rsort($vars);

        foreach ($vars as $pos) {
            [$resolved, $resolvedName] = self::resolveVariableWithReport(substr($value, $pos));
            $value = substr($value, 0, $pos) . $resolved;

            if ($resolvedName !== null) {
                $unresolved[] = $resolvedName;
            }
        }

        return [$value, $unresolved];
    }


    /**
     * @param list<string> $lines
     * @return list<string>
     */
    private static function collapseMultiline(array $lines): array
    {
        $output = [];
        $buffer = [];
        $collecting = false;
        $started = false;

        foreach ($lines as $line) {
            $startsHere = !$collecting && self::isMultilineStart($line);

            if ($startsHere) {
                $collecting = true;
                $started = true;
            }

            if ($collecting) {
                $buffer[] = $line;

                if (self::isMultilineStop($line, $startsHere)) {
                    $collecting = false;
                    $line = implode("\n", $buffer);
                    $buffer = [];
                }
            }

            if (!$collecting && !self::isIgnorable($line)) {
                $output[] = $line;
            }
        }

        if ($collecting && $started) {
            self::raiseParseError('a missing closing quote', implode("\n", $buffer));
        }

        return $output;
    }

    private static function isMultilineStart(string $line): bool
    {
        $pos = strpos($line, '=');

        if ($pos === false) {
            return false;
        }

        $name = trim(substr($line, 0, $pos));

        if ($name === '') {
            return false;
        }

        if (strlen($name) > 8 && substr($name, 0, 6) === 'export' && ctype_space(substr($name, 6, 1))) {
            $name = trim(substr($name, 6));

            if ($name === '') {
                return false;
            }
        }

        $value = trim(substr($line, $pos + 1));

        if ($value === '' || $value[0] !== '"') {
            return false;
        }

        return !self::isMultilineStop($line, true);
    }

    private static function isMultilineStop(string $line, bool $started): bool
    {
        if ($line === '"') {
            return true;
        }

        $line = str_replace('\\\\', '', $line);
        $count = preg_match_all('/(?=([^\\\\]"))/', $line);

        if ($count === false) {
            return false;
        }

        return $started ? $count > 1 : $count >= 1;
    }

    private static function isIgnorable(string $line): bool
    {
        $line = trim($line);

        return $line === '' || (isset($line[0]) && $line[0] === '#');
    }

    /** @return array{string, string|null, list<int>} */
    private static function parseEntry(string $raw, bool $asciiNames): array
    {
        [$namePart, $valuePart] = self::splitLine($raw);
        $name = self::normalizeName($namePart, $asciiNames);

        if ($valuePart === null) {
            return [$name, null, []];
        }

        [$value, $vars] = self::parseValue($valuePart);

        return [$name, $value, $vars];
    }

    /** @return array{string, string} */
    private static function splitLine(string $line): array
    {
        $pos = strpos($line, '=');

        if ($pos === false) {
            $name = trim($line);

            if ($name === '') {
                self::raiseParseError('an unexpected empty name', $line);
            }

            return [$name, ''];
        }

        $parts = explode('=', $line, 2);
        $name = trim($parts[0]);
        $value = trim($parts[1]);

        if ($name === '') {
            self::raiseParseError('an unexpected equals', $line);
        }

        return [$name, $value];
    }

    private static function normalizeName(string $name, bool $asciiNames): string
    {
        if (strlen($name) > 8 && substr($name, 0, 6) === 'export' && ctype_space(substr($name, 6, 1))) {
            $name = ltrim(substr($name, 6));
        }

        if (self::isNameQuoted($name)) {
            $name = substr($name, 1, -1);
        }

        if (!self::isNameValid($name, $asciiNames)) {
            self::raiseParseError('an invalid name', $name);
        }

        return $name;
    }

    private static function isNameQuoted(string $name): bool
    {
        if (strlen($name) < 3) {
            return false;
        }

        $first = $name[0];
        $last = $name[strlen($name) - 1];

        return ($first === '"' && $last === '"') || ($first === '\'' && $last === '\'');
    }

    private static function isNameValid(string $name, bool $asciiNames): bool
    {
        if ($asciiNames) {
            return preg_match('/\A[a-zA-Z0-9_.]+\z/', $name) === 1;
        }

        return preg_match('~(*UTF8)\A[\p{Ll}\p{Lu}\p{M}\p{N}_.]+\z~u', $name) === 1;
    }

    /** @return list<array{string, string|null, list<int>}> */
    private static function parseContentWithOptions(string $content, bool $asciiNames): array
    {
        $lines = preg_split("/(\r\n|\n|\r)/", $content);

        if ($lines === false) {
            throw new Exception\InvalidFileException('Could not split into separate lines.');
        }

        $entries = [];

        foreach (self::collapseMultiline($lines) as $raw) {
            $entries[] = self::parseEntry($raw, $asciiNames);
        }

        return $entries;
    }

    /** @return array{string, list<int>} */
    private static function parseValue(string $value): array
    {
        if (trim($value) === '') {
            return ['', []];
        }

        $output = '';
        $vars = [];
        $state = self::STATE_START;

        foreach (self::tokenize($value) as $token) {
            [$emit, $isVar, $state] = self::advanceState($state, $token, $value);

            if ($isVar) {
                $vars[] = strlen($output);
            }

            $output .= $emit;
        }

        if (in_array($state, self::INVALID_END_STATES, true)) {
            self::raiseParseError('a missing closing quote', $value);
        }

        return [$output, $vars];
    }

    /** @return list<string> */
    private static function tokenize(string $input): array
    {
        /** @var string|null $regex */
        static $regex;

        if ($regex === null) {
            $regex = '((' . implode(')|(', self::TOKEN_PATTERNS) . '))A';
        }

        $offset = 0;
        $tokens = [];

        while (isset($input[$offset])) {
            if (!preg_match($regex, $input, $matches, 0, $offset)) {
                self::raiseParseError(
                    sprintf('an unexpected character [%s]', $input[$offset]),
                    $input
                );
            }

            $offset += strlen($matches[0]);
            $tokens[] = $matches[0];
        }

        return $tokens;
    }

    /** @return array{string, bool, int} */
    private static function advanceState(int $state, string $token, string $value): array
    {
        switch ($state) {
            case self::STATE_START:
                if ($token === '\'') {
                    return ['', false, self::STATE_SINGLE];
                }
                if ($token === '"') {
                    return ['', false, self::STATE_DOUBLE];
                }
                if ($token === '#') {
                    return ['', false, self::STATE_COMMENT];
                }
                if ($token === '$') {
                    return [$token, true, self::STATE_UNQUOTED];
                }

                return [$token, false, self::STATE_UNQUOTED];
            case self::STATE_UNQUOTED:
                if ($token === '#') {
                    return ['', false, self::STATE_COMMENT];
                }
                if (ctype_space($token)) {
                    return ['', false, self::STATE_SPACE];
                }
                if ($token === '$') {
                    return [$token, true, self::STATE_UNQUOTED];
                }

                return [$token, false, self::STATE_UNQUOTED];
            case self::STATE_SINGLE:
                if ($token === '\'') {
                    return ['', false, self::STATE_SPACE];
                }

                return [$token, false, self::STATE_SINGLE];
            case self::STATE_DOUBLE:
                if ($token === '"') {
                    return ['', false, self::STATE_SPACE];
                }
                if ($token === '\\') {
                    return ['', false, self::STATE_ESCAPE];
                }
                if ($token === '$') {
                    return [$token, true, self::STATE_DOUBLE];
                }

                return [$token, false, self::STATE_DOUBLE];
            case self::STATE_ESCAPE:
                if ($token === '"' || $token === '\\' || $token === '$') {
                    return [$token, false, self::STATE_DOUBLE];
                }

                $lead = $token[0];

                if (in_array($lead, ['f', 'n', 'r', 't', 'v'], true)) {
                    return [stripcslashes('\\' . $lead) . substr($token, 1), false, self::STATE_DOUBLE];
                }

                self::raiseParseError('an unexpected escape sequence', $value);
            case self::STATE_SPACE:
                if ($token === '#') {
                    return ['', false, self::STATE_COMMENT];
                }
                if (!ctype_space($token)) {
                    self::raiseParseError('unexpected whitespace', $value);
                }

                return ['', false, self::STATE_SPACE];
            case self::STATE_COMMENT:
                return ['', false, self::STATE_COMMENT];
            default:
                throw new \Error('Parser entered invalid state.');
        }
    }

    private static function raiseParseError(string $cause, string $subject): void
    {
        $message = sprintf(
            'Failed to parse dotenv file. %s',
            self::formatParseError($cause, $subject)
        );

        throw new Exception\InvalidFileException($message);
    }

    private static function formatParseError(string $cause, string $subject): string
    {
        return sprintf('Encountered %s at [%s].', $cause, strtok($subject, "\n"));
    }

    /** @return array{string, string|null} */
    private static function resolveVariableWithReport(string $value): array
    {
        if (preg_match('/\A\${([a-zA-Z0-9_.]+)}/', $value, $matches) === 1) {
            $resolved = self::readRaw($matches[1]);

            if ($resolved !== null) {
                return [$resolved . substr($value, strlen($matches[0])), null];
            }

            return [$value, $matches[1]];
        }

        if (preg_match('/\A\$([a-zA-Z0-9_.]+)/', $value, $matches) === 1) {
            $resolved = self::readRaw($matches[1]);

            if ($resolved !== null) {
                return [$resolved . substr($value, strlen($matches[0])), null];
            }

            return [$value, $matches[1]];
        }

        return [$value, null];
    }

    private static function readRaw(string $key): ?string
    {
        if (array_key_exists($key, $_ENV)) {
            return is_string($_ENV[$key]) ? $_ENV[$key] : null;
        }

        if (array_key_exists($key, $_SERVER)) {
            return is_string($_SERVER[$key]) ? $_SERVER[$key] : null;
        }

        $value = getenv($key);

        return $value === false ? null : $value;
    }

    private static function stripUtf8Bom(string $input): string
    {
        return str_starts_with($input, "\xEF\xBB\xBF") ? substr($input, 3) : $input;
    }
}
