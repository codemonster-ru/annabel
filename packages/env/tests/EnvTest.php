<?php

use Codemonster\Env\Env;
use Codemonster\Env\EnvLoader;
use PHPUnit\Framework\TestCase;

class EnvTest extends TestCase
{
    protected function setUp(): void
    {
        Env::reset();

        foreach (
            [
                'APP_NAME',
                'FEATURE_ENABLED',
                'FEATURE_DISABLED',
                'OPTIONAL_VALUE',
                'EMPTY_VALUE',
                'SSR_URL',
                'QUOTED_SINGLE',
                'QUOTED_DOUBLE',
                'QUOTED_WITH_COMMENT',
                'SINGLE_LITERAL',
                'QUOTED_BACKSLASH',
                'QUOTED_TRUE',
                'QUOTED_NULL',
                'WITH_SPACES',
                'INLINE_COMMENT',
                'ESCAPED_HASH',
                'ESCAPED_EQUALS',
                'MULTILINE',
                'EXPORTED_VALUE',
                'APP.URL',
                'BASE_URL',
                'FULL_URL',
                'MIXED_URL',
                'DOUBLE_QUOTED_EXPAND',
                'SINGLE_QUOTED_EXPAND',
                'MULTI_EXPAND',
                'RELOAD_VALUE'
            ] as $key
        ) {
            unset($_ENV[$key], $_SERVER[$key]);

            putenv($key);
        }

        Env::load(__DIR__ . '/.env.example');
    }

    public function testEnvLoadsString(): void
    {
        $this->assertSame('MyApp', Env::get('APP_NAME'));
    }

    public function testEnvLoadsBooleanStrings(): void
    {
        $this->assertSame('true', Env::get('FEATURE_ENABLED'));
        $this->assertSame('false', Env::get('FEATURE_DISABLED'));
    }

    public function testEnvLoadsNullString(): void
    {
        $this->assertSame('null', Env::get('OPTIONAL_VALUE'));
    }

    public function testEnvLoadsEmptyStringKeyword(): void
    {
        $this->assertSame('empty', Env::get('EMPTY_VALUE'));
    }

    public function testEnvGetCastCastsCommonValues(): void
    {
        $this->assertTrue(Env::getCast('FEATURE_ENABLED', null, true));
        $this->assertFalse(Env::getCast('FEATURE_DISABLED', null, true));
        $this->assertNull(Env::getCast('OPTIONAL_VALUE', 'fallback', true));
        $this->assertSame('', Env::getCast('EMPTY_VALUE', 'fallback', true));
        $this->assertSame('MyApp', Env::getCast('APP_NAME', null, true));
        $this->assertSame('default', Env::getCast('NOT_DEFINED', 'default', true));
    }

    public function testEnvGetCastStripsWrappingQuotes(): void
    {
        unset($_ENV['CAST_QUOTED'], $_SERVER['CAST_QUOTED']);
        putenv('CAST_QUOTED');
        putenv('CAST_QUOTED="hello"');

        try {
            $this->assertSame('hello', Env::getCast('CAST_QUOTED', null, true));
        } finally {
            unset($_ENV['CAST_QUOTED'], $_SERVER['CAST_QUOTED']);
            putenv('CAST_QUOTED');
        }
    }

    public function testEnvRemovesQuotes(): void
    {
        $this->assertSame('http://localhost:3000', Env::get('SSR_URL'));
    }

    public function testEnvHandlesSingleAndDoubleQuotes(): void
    {
        $this->assertSame('Hello Single', Env::get('QUOTED_SINGLE'));
        $this->assertSame('Hello "Double"', Env::get('QUOTED_DOUBLE'));
    }

    public function testEnvKeepsSingleQuotesLiteral(): void
    {
        $this->assertSame('line1\\nline2', Env::get('SINGLE_LITERAL'));
    }

    public function testEnvParsesEscapedBackslashesInDoubleQuotes(): void
    {
        $this->assertSame('C:\\path\\file', Env::get('QUOTED_BACKSLASH'));
    }

    public function testEnvKeepsQuotedValuesAsStrings(): void
    {
        $this->assertSame('true', Env::get('QUOTED_TRUE'));
        $this->assertSame('null', Env::get('QUOTED_NULL'));
    }

    public function testEnvReturnsDefaultIfNotSet(): void
    {
        $this->assertSame('default', Env::get('NOT_DEFINED', 'default'));
    }

    public function testEnvDoesNotOverrideExisting(): void
    {
        Env::reset();

        unset($_ENV['APP_NAME'], $_SERVER['APP_NAME']);
        putenv('APP_NAME');

        $_ENV['APP_NAME'] = 'ManualApp';

        Env::load(__DIR__ . '/.env.example');

        $this->assertSame('ManualApp', Env::get('APP_NAME'));
    }

    public function testEnvHandlesInlineComments(): void
    {
        $this->assertSame('works', Env::get('INLINE_COMMENT'));
        $this->assertSame('Hello # not comment', Env::get('QUOTED_WITH_COMMENT'));
    }

    public function testEnvHandlesSpacesAroundEquals(): void
    {
        $this->assertSame('spaced value', Env::get('WITH_SPACES'));
    }

    public function testEnvHandlesEscapedChars(): void
    {
        $this->assertSame('foo#bar', Env::get('ESCAPED_HASH'));
        $this->assertSame('foo=bar', Env::get('ESCAPED_EQUALS'));
    }

    public function testEnvHandlesEscapedNewlines(): void
    {
        $this->assertSame("line1\nline2\rline3", Env::get('MULTILINE'));
    }

    public function testEnvHandlesExportPrefix(): void
    {
        $this->assertSame('exported', Env::get('EXPORTED_VALUE'));
    }

    public function testEnvAllowsDotInVariableNames(): void
    {
        $this->assertSame('https://example.test', Env::get('APP.URL'));
    }

    public function testEnvResolvesNestedVariables(): void
    {
        $this->assertSame('https://example.test/api', Env::get('FULL_URL'));
    }

    public function testEnvLeavesUnresolvedVariablesIntact(): void
    {
        $this->assertSame('https://example.test/api/${UNDEFINED_VAR}/v1', Env::get('MIXED_URL'));
    }

    public function testEnvStrictResolveThrowsOnUnresolved(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        file_put_contents($path, "BAD_VALUE=\${UNDEFINED_VAR}\n");

        try {
            $this->expectExceptionMessage('Unresolved variable reference(s) [UNDEFINED_VAR] in value for BAD_VALUE.');
            $this->expectException(\Codemonster\Env\Exception\InvalidFileException::class);

            Env::load($path, null, null, true);
        } finally {
            unlink($path);
        }
    }

    public function testEnvStrictResolveSucceedsWhenResolved(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        file_put_contents($path, "BASE=ok\nFULL=\${BASE}/done\n");

        try {
            Env::load($path, null, null, true);

            $this->assertSame('ok/done', Env::get('FULL'));
        } finally {
            unset($_ENV['BASE'], $_SERVER['BASE'], $_ENV['FULL'], $_SERVER['FULL']);
            putenv('BASE');
            putenv('FULL');
            unlink($path);
        }
    }

    public function testEnvExpandsDollarVariableSyntax(): void
    {
        Env::loadString("BASE=ok\nFULL=\$BASE/done\n");

        try {
            $this->assertSame('ok/done', Env::get('FULL'));
        } finally {
            unset($_ENV['BASE'], $_SERVER['BASE'], $_ENV['FULL'], $_SERVER['FULL']);
            putenv('BASE');
            putenv('FULL');
        }
    }

    public function testEnvStrictResolveThrowsOnUnresolvedDollarSyntax(): void
    {
        $this->expectException(\Codemonster\Env\Exception\InvalidFileException::class);

        Env::loadString("BAD=\$MISSING\n", null, true);
    }

    public function testEnvSafeLoadStrictResolveReturnsFalse(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        file_put_contents($path, "BAD_VALUE=\${UNDEFINED_VAR}\n");

        try {
            $this->assertFalse(Env::safeLoad($path, null, null, true));
        } finally {
            unlink($path);
        }
    }

    public function testEnvLoaderAcceptsCustomParser(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        $parser = new class implements \Codemonster\Env\ParserInterface {
            public function parseStringRaw(string $content, ?string $encoding = null): array
            {
                return [['CUSTOM', 'ok', []]];
            }

            public function expandVariables(string $value, array $vars): string
            {
                return $value;
            }

            public function expandVariablesWithReport(string $value, array $vars): array
            {
                return [$value, []];
            }
        };

        $loader = new \Codemonster\Env\EnvLoader($parser);

        try {
            file_put_contents($path, "IGNORED=1\n");

            $loader->loadFile($path);

            $this->assertSame('ok', Env::get('CUSTOM'));
        } finally {
            unset($_ENV['CUSTOM'], $_SERVER['CUSTOM']);
            putenv('CUSTOM');
            unlink($path);
        }
    }

    public function testEnvSetDefaultParser(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        $parser = new class implements \Codemonster\Env\ParserInterface {
            public function parseStringRaw(string $content, ?string $encoding = null): array
            {
                return [['CUSTOM_DEFAULT', 'yes', []]];
            }

            public function expandVariables(string $value, array $vars): string
            {
                return $value;
            }

            public function expandVariablesWithReport(string $value, array $vars): array
            {
                return [$value, []];
            }
        };

        try {
            file_put_contents($path, "IGNORED=1\n");

            Env::setDefaultParser($parser);
            Env::load($path);

            $this->assertSame('yes', Env::get('CUSTOM_DEFAULT'));
        } finally {
            Env::setDefaultParser(null);

            unset($_ENV['CUSTOM_DEFAULT'], $_SERVER['CUSTOM_DEFAULT']);
            putenv('CUSTOM_DEFAULT');
            unlink($path);
        }
    }

    public function testEnvGetDefaultLoaderAndParser(): void
    {
        Env::reset();

        $this->assertInstanceOf(\Codemonster\Env\EnvLoader::class, Env::getDefaultLoader());
        $this->assertNull(Env::getDefaultParser());
    }

    public function testEnvExpandsVariablesInDoubleQuotes(): void
    {
        $this->assertSame('Base is https://example.test', Env::get('DOUBLE_QUOTED_EXPAND'));
    }

    public function testEnvDoesNotExpandVariablesInSingleQuotes(): void
    {
        $this->assertSame('Base is ${BASE_URL}', Env::get('SINGLE_QUOTED_EXPAND'));
    }

    public function testEnvExpandsMultipleVariablesInOneValue(): void
    {
        $this->assertSame(
            'https://example.test/one/https://example.test/two',
            Env::get('MULTI_EXPAND')
        );
    }

    public function testEnvDoesNotOverrideGetenv(): void
    {
        Env::reset();

        unset($_ENV['APP_NAME'], $_SERVER['APP_NAME']);
        putenv('APP_NAME');
        putenv('APP_NAME=SystemApp');

        Env::load(__DIR__ . '/.env.example');

        $this->assertSame('SystemApp', Env::get('APP_NAME'));
    }

    public function testEnvThrowsOnUnclosedQuote(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        file_put_contents($path, "BROKEN='oops\n");

        try {
            $this->expectException(\Codemonster\Env\Exception\InvalidFileException::class);

            Env::load($path);
        } finally {
            unlink($path);
        }
    }

    public function testEnvThrowsOnUnclosedMultilineQuote(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        file_put_contents($path, "BROKEN=\"line1\nline2");

        try {
            $this->expectException(\Codemonster\Env\Exception\InvalidFileException::class);

            Env::load($path);
        } finally {
            unlink($path);
        }
    }

    public function testEnvThrowsOnTrailingCharactersAfterQuote(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        file_put_contents($path, "BROKEN=\"ok\" trailing\n");

        try {
            $this->expectException(\Codemonster\Env\Exception\InvalidFileException::class);

            Env::load($path);
        } finally {
            unlink($path);
        }
    }

    public function testEnvThrowsOnUnexpectedEscapeSequence(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        file_put_contents($path, "BROKEN=\"C:\\path\"\n");

        try {
            $this->expectException(\Codemonster\Env\Exception\InvalidFileException::class);

            Env::load($path);
        } finally {
            unlink($path);
        }
    }

    public function testEnvRejectsInvalidVariableName(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        file_put_contents($path, "BAD NAME=oops\n");

        try {
            $this->expectException(\Codemonster\Env\Exception\InvalidFileException::class);

            Env::load($path);
        } finally {
            unlink($path);
        }
    }

    public function testEnvTreatsNoEqualsAsEmptyString(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        file_put_contents($path, "CLEAR_ME=ok\nCLEAR_ME\n");

        try {
            Env::load($path);

            $this->assertSame('', Env::get('CLEAR_ME', 'default'));
        } finally {
            unset($_ENV['CLEAR_ME'], $_SERVER['CLEAR_ME']);
            putenv('CLEAR_ME');
            unlink($path);
        }
    }

    public function testEnvIgnoresLeadingWhitespaceComments(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        file_put_contents($path, "  # leading comment\nLEADING_COMMENT_TEST=ok\n");

        try {
            Env::load($path);

            $this->assertSame('ok', Env::get('LEADING_COMMENT_TEST'));
        } finally {
            unset($_ENV['LEADING_COMMENT_TEST'], $_SERVER['LEADING_COMMENT_TEST']);
            putenv('LEADING_COMMENT_TEST');
            unlink($path);
        }
    }

    public function testEnvReloadsPreviouslyLoadedValues(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        try {
            file_put_contents($path, "RELOAD_VALUE=one\n");

            Env::load($path);

            $this->assertSame('one', Env::get('RELOAD_VALUE'));

            file_put_contents($path, "RELOAD_VALUE=two\n");

            Env::load($path);

            $this->assertSame('two', Env::get('RELOAD_VALUE'));
        } finally {
            unset($_ENV['RELOAD_VALUE'], $_SERVER['RELOAD_VALUE']);
            putenv('RELOAD_VALUE');
            unlink($path);
        }
    }

    public function testEnvSupportsCustomEncoding(): void
    {
        if (!function_exists('mb_convert_encoding')) {
            $this->markTestSkipped('mbstring is required for encoding conversion.');
        }

        $path = tempnam(sys_get_temp_dir(), 'env');
        $content = "ENCODED_NAME=K\xC3\xADrill\n";
        $encoded = mb_convert_encoding($content, 'ISO-8859-1', 'UTF-8');

        try {
            file_put_contents($path, $encoded);

            Env::load($path, 'ISO-8859-1');

            $this->assertSame("K\xC3\xADrill", Env::get('ENCODED_NAME'));
        } finally {
            unset($_ENV['ENCODED_NAME'], $_SERVER['ENCODED_NAME']);
            putenv('ENCODED_NAME');
            unlink($path);
        }
    }

    public function testEnvSafeLoadReturnsFalseWhenMissing(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        if (is_file($path)) {
            unlink($path);
        }

        $this->assertFalse(Env::safeLoad($path));
    }

    public function testEnvEnforcesMaxFileSize(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        file_put_contents($path, "TOO_BIG=1\n");

        try {
            $this->expectException(\Codemonster\Env\Exception\InvalidFileException::class);

            Env::load($path, null, 5);
        } finally {
            unlink($path);
        }
    }

    public function testEnvLoadFileRejectsNegativeMaxBytes(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        file_put_contents($path, "OK=1\n");

        try {
            $this->expectException(\Codemonster\Env\Exception\InvalidFileException::class);

            Env::load($path, null, -1);
        } finally {
            unlink($path);
        }
    }

    public function testEnvSafeLoadReturnsFalseWhenTooLarge(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        file_put_contents($path, "TOO_BIG=1\n");

        try {
            $this->assertFalse(Env::safeLoad($path, null, 5));
        } finally {
            unlink($path);
        }
    }

    public function testEnvSafeLoadReturnsFalseWhenNegativeMaxBytes(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        file_put_contents($path, "OK=1\n");

        try {
            $this->assertFalse(Env::safeLoad($path, null, -1));
        } finally {
            unlink($path);
        }
    }

    public function testEnvAllowsCustomDefaultLoader(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');
        $loader = new EnvLoader();

        try {
            file_put_contents($path, "CUSTOM_LOADER=ok\n");

            Env::setDefaultLoader($loader);
            Env::load($path);

            $this->assertSame('ok', Env::get('CUSTOM_LOADER'));
        } finally {
            Env::setDefaultLoader(null);

            unset($_ENV['CUSTOM_LOADER'], $_SERVER['CUSTOM_LOADER']);
            putenv('CUSTOM_LOADER');
            unlink($path);
        }
    }

    public function testEnvParseReturnsEntries(): void
    {
        $entries = Env::parse("PARSED=ok\nEMPTY\n");

        $this->assertCount(2, $entries);
        $this->assertSame('PARSED', $entries[0][0]);
        $this->assertSame('ok', $entries[0][1]);
        $this->assertSame('EMPTY', $entries[1][0]);
        $this->assertSame('', $entries[1][1]);
    }

    public function testEnvParseStrictThrowsOnDuplicate(): void
    {
        $this->expectException(
            \Codemonster\Env\Exception\InvalidFileException::class
        );

        Env::parse("DUP=one\nDUP=two\n", null, true);
    }

    public function testEnvParserRawReturnsEntries(): void
    {
        $entries = \Codemonster\Env\EnvParser::parseStringRaw("RAW=ok\nEMPTY\n");

        $this->assertCount(2, $entries);
        $this->assertSame('RAW', $entries[0][0]);
        $this->assertSame('ok', $entries[0][1]);
        $this->assertSame('EMPTY', $entries[1][0]);
        $this->assertSame('', $entries[1][1]);
    }

    public function testEnvParseToArrayReturnsMap(): void
    {
        $entries = Env::parseToArray("PARSED=ok\nEMPTY\n");

        $this->assertCount(2, $entries);
        $this->assertSame('ok', $entries['PARSED']);
        $this->assertSame('', $entries['EMPTY']);
    }

    public function testEnvParseToArrayStripsUtf8Bom(): void
    {
        $entries = Env::parseToArray("\xEF\xBB\xBFBOM_KEY=ok\n");

        $this->assertSame('ok', $entries['BOM_KEY']);
        $this->assertArrayNotHasKey("\xEF\xBB\xBFBOM_KEY", $entries);
    }

    public function testEnvParseToArrayStrictThrowsOnDuplicate(): void
    {
        $this->expectException(
            \Codemonster\Env\Exception\InvalidFileException::class
        );

        Env::parseToArray("DUP=one\nDUP=two\n", null, true);
    }

    public function testEnvLoadString(): void
    {
        Env::loadString("STRING_VALUE=ok\n");

        $this->assertSame('ok', Env::get('STRING_VALUE'));
    }

    public function testEnvSafeLoadStringStrictResolveReturnsFalse(): void
    {
        $this->assertFalse(Env::safeLoadString("BAD=\${MISSING}\n", null, true));
    }

    public function testEnvLoadStringEnforcesMaxBytes(): void
    {
        $this->expectException(\Codemonster\Env\Exception\InvalidFileException::class);

        Env::loadString("TOO_LONG\n", null, false, 5);
    }

    public function testEnvSafeLoadStringReturnsFalseWhenTooLarge(): void
    {
        $this->assertFalse(Env::safeLoadString("TOO_LONG\n", null, false, 5));
    }

    public function testEnvLoadStringRejectsNegativeMaxBytes(): void
    {
        $this->expectException(\Codemonster\Env\Exception\InvalidFileException::class);

        Env::loadString("OK\n", null, false, -1);
    }

    public function testEnvSafeLoadStringReturnsFalseWhenNegativeMaxBytes(): void
    {
        $this->assertFalse(Env::safeLoadString("OK\n", null, false, -1));
    }

    public function testEnvLoadFilesShortCircuitLoadsFirstExisting(): void
    {
        $missing = tempnam(sys_get_temp_dir(), 'env');
        $existing = tempnam(sys_get_temp_dir(), 'env');

        if (is_file($missing)) {
            unlink($missing);
        }

        file_put_contents($existing, "MULTI_FILE_KEY=ok\n");

        try {
            Env::loadFiles([$missing, $existing], null, null, false, true);

            $this->assertSame('ok', Env::get('MULTI_FILE_KEY'));
        } finally {
            unset($_ENV['MULTI_FILE_KEY'], $_SERVER['MULTI_FILE_KEY']);
            putenv('MULTI_FILE_KEY');
            unlink($existing);
        }
    }

    public function testEnvLoadFilesWithoutShortCircuitThrowsOnMissing(): void
    {
        $existing = tempnam(sys_get_temp_dir(), 'env');
        $missing = tempnam(sys_get_temp_dir(), 'env');

        if (is_file($missing)) {
            unlink($missing);
        }

        file_put_contents($existing, "MULTI_FILE_KEY=ok\n");

        try {
            $this->expectException(\Codemonster\Env\Exception\InvalidPathException::class);

            Env::loadFiles([$existing, $missing], null, null, false, false);
        } finally {
            unset($_ENV['MULTI_FILE_KEY'], $_SERVER['MULTI_FILE_KEY']);
            putenv('MULTI_FILE_KEY');
            unlink($existing);
        }
    }

    public function testEnvSafeLoadFilesReturnsFalseWhenNone(): void
    {
        $missing = tempnam(sys_get_temp_dir(), 'env');

        if (is_file($missing)) {
            unlink($missing);
        }

        $this->assertFalse(Env::safeLoadFiles([$missing]));
    }

    public function testEnvSafeLoadFilesReturnsTrueWhenAny(): void
    {
        $missing = tempnam(sys_get_temp_dir(), 'env');
        $existing = tempnam(sys_get_temp_dir(), 'env');

        if (is_file($missing)) {
            unlink($missing);
        }

        file_put_contents($existing, "MULTI_FILE_KEY=ok\n");

        try {
            $this->assertTrue(Env::safeLoadFiles([$missing, $existing]));
            $this->assertSame('ok', Env::get('MULTI_FILE_KEY'));
        } finally {
            unset($_ENV['MULTI_FILE_KEY'], $_SERVER['MULTI_FILE_KEY']);
            putenv('MULTI_FILE_KEY');
            unlink($existing);
        }
    }

    public function testEnvLoadFilesSupportsGlob(): void
    {
        $dir = sys_get_temp_dir();
        $file1 = tempnam($dir, 'env');
        $file2 = tempnam($dir, 'env');

        file_put_contents($file1, "GLOB_ONE=1\n");
        file_put_contents($file2, "GLOB_TWO=2\n");

        try {
            Env::loadFiles([$dir . DIRECTORY_SEPARATOR . 'env*'], null, null, false, false);

            $this->assertSame('1', Env::get('GLOB_ONE'));
            $this->assertSame('2', Env::get('GLOB_TWO'));
        } finally {
            unset($_ENV['GLOB_ONE'], $_SERVER['GLOB_ONE'], $_ENV['GLOB_TWO'], $_SERVER['GLOB_TWO']);
            putenv('GLOB_ONE');
            putenv('GLOB_TWO');
            unlink($file1);
            unlink($file2);
        }
    }

    public function testEnvAsciiNameParserRejectsUnicodeName(): void
    {
        $parser = new \Codemonster\Env\DefaultParser(true);
        $badName = "\xD0\x9A\xD0\x98\xD0\xA0=1\n";

        try {
            Env::setDefaultParser($parser);


            $this->expectException(\Codemonster\Env\Exception\InvalidFileException::class);
            Env::loadString($badName);
        } finally {
            Env::setDefaultParser(null);
        }
    }

    public function testEnvLoadStripsUtf8Bom(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');

        file_put_contents($path, "\xEF\xBB\xBFBOM_KEY=ok\n");

        try {
            Env::load($path);

            $this->assertSame('ok', Env::get('BOM_KEY'));
            $this->assertSame('default', Env::get("\xEF\xBB\xBFBOM_KEY", 'default'));
        } finally {
            unset($_ENV['BOM_KEY'], $_SERVER['BOM_KEY']);
            putenv('BOM_KEY');
            unlink($path);
        }
    }

    public function testEnvLoadWithNullEncodingDoesNotConvert(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env');
        $value = "K\xE1";

        file_put_contents($path, "RAW={$value}\n");

        try {
            Env::load($path, null);

            $this->assertSame($value, Env::get('RAW'));
        } finally {
            unset($_ENV['RAW'], $_SERVER['RAW']);
            putenv('RAW');
            unlink($path);
        }
    }

    public function testEnvLoadFilesGlobFlagsControlBraceExpansion(): void
    {
        if (!defined('GLOB_BRACE')) {
            $this->markTestSkipped('GLOB_BRACE is not available on this platform.');
        }

        $dir = sys_get_temp_dir();
        $file1 = tempnam($dir, 'foo');
        $file2 = tempnam($dir, 'bar');

        file_put_contents($file1, "GLOB_FLAG_ONE=1\n");
        file_put_contents($file2, "GLOB_FLAG_TWO=2\n");

        $pattern = $dir . DIRECTORY_SEPARATOR . '{foo,bar}*';

        try {
            Env::loadFiles([$pattern], null, null, false, false, constant('GLOB_BRACE'));

            $this->assertSame('1', Env::get('GLOB_FLAG_ONE'));
            $this->assertSame('2', Env::get('GLOB_FLAG_TWO'));

            unset($_ENV['GLOB_FLAG_ONE'], $_SERVER['GLOB_FLAG_ONE'], $_ENV['GLOB_FLAG_TWO'], $_SERVER['GLOB_FLAG_TWO']);
            putenv('GLOB_FLAG_ONE');
            putenv('GLOB_FLAG_TWO');

            $this->expectException(\Codemonster\Env\Exception\InvalidPathException::class);

            Env::loadFiles([$pattern], null, null, false, false, 0);
        } finally {
            unset($_ENV['GLOB_FLAG_ONE'], $_SERVER['GLOB_FLAG_ONE'], $_ENV['GLOB_FLAG_TWO'], $_SERVER['GLOB_FLAG_TWO']);
            putenv('GLOB_FLAG_ONE');
            putenv('GLOB_FLAG_TWO');
            unlink($file1);
            unlink($file2);
        }
    }

    public function testEnvLoadFilesStrictResolveWithGlobThrowsOnUnresolved(): void
    {
        $dir = sys_get_temp_dir();
        $file1 = tempnam($dir, 'env');
        $file2 = tempnam($dir, 'env');

        file_put_contents($file1, "BAD=\${MISSING}\n");
        file_put_contents($file2, "OK=1\n");

        try {
            $this->expectException(\Codemonster\Env\Exception\InvalidFileException::class);

            Env::loadFiles([$dir . DIRECTORY_SEPARATOR . 'env*'], null, null, true, false);
        } finally {
            unset($_ENV['OK'], $_SERVER['OK']);
            putenv('OK');
            unlink($file1);
            unlink($file2);
        }
    }

    public function testEnvSafeLoadFilesStrictResolveReturnsFalse(): void
    {
        $dir = sys_get_temp_dir();
        $file1 = tempnam($dir, 'env');
        $file2 = tempnam($dir, 'env');

        file_put_contents($file1, "BAD=\${MISSING}\n");
        file_put_contents($file2, "OK=1\n");

        try {
            $this->assertFalse(Env::safeLoadFiles([$dir . DIRECTORY_SEPARATOR . 'env*'], null, null, true, false));
        } finally {
            unset($_ENV['OK'], $_SERVER['OK']);
            putenv('OK');
            unlink($file1);
            unlink($file2);
        }
    }

    public function testEnvLoadFilesGlobNoMatchesThrowsWhenNotShortCircuit(): void
    {
        $dir = sys_get_temp_dir();
        $pattern = $dir . DIRECTORY_SEPARATOR . 'env_no_match_' . uniqid('', true) . '*';

        $this->expectException(
            \Codemonster\Env\Exception\InvalidPathException::class
        );

        Env::loadFiles([$pattern], null, null, false, false);
    }
}
