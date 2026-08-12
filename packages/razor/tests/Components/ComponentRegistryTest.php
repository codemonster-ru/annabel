<?php

declare(strict_types=1);

namespace Codemonster\Razor\Tests\Components;

use Codemonster\Razor\Components\ComponentRegistry;
use Codemonster\Razor\Components\ComponentRenderContext;
use Codemonster\Razor\Components\Contracts\ComponentInterface;
use Codemonster\Razor\Components\Contracts\ComponentProviderInterface;
use Codemonster\Razor\Components\RenderedHtml;
use Codemonster\Razor\Exceptions\RazorException;
use PHPUnit\Framework\TestCase;

final class ComponentRegistryTest extends TestCase
{
    public function testRegistersAProviderAndResolvesItsComponents(): void
    {
        $button = $this->component();
        $provider = new class ($button) implements ComponentProviderInterface {
            public function __construct(private readonly ComponentInterface $button)
            {
            }

            public function prefix(): string
            {
                return 'cm';
            }

            public function components(): array
            {
                return ['button' => $this->button];
            }
        };
        $registry = new ComponentRegistry();
        $emptySignature = $registry->cacheSignature();

        $registry->register($provider);

        self::assertTrue($registry->handles('cm-button'));
        self::assertTrue($registry->handles('cm-unknown'));
        self::assertFalse($registry->handles('button'));
        self::assertSame($button, $registry->resolve('cm-button'));
        self::assertNull($registry->resolve('cm-unknown'));
        self::assertNotSame($emptySignature, $registry->cacheSignature());
    }

    public function testAllowsAnEmptyPrefixRegistration(): void
    {
        $registry = new ComponentRegistry();

        $registry->registerPrefix('cm', []);

        self::assertTrue($registry->handles('cm-future'));
        self::assertNull($registry->resolve('cm-future'));
    }

    /**
     * @param array<mixed> $components
     * @dataProvider invalidRegistrationProvider
     */
    public function testRejectsInvalidOrDuplicateRegistrations(string $prefix, array $components): void
    {
        $registry = new ComponentRegistry();

        $this->expectException(RazorException::class);

        /** @var array<string, ComponentInterface> $components */
        $registry->registerPrefix($prefix, $components);
    }

    /** @return iterable<string, array{string, array<mixed>}> */
    public static function invalidRegistrationProvider(): iterable
    {
        yield 'invalid prefix' => ['CM', []];
        yield 'invalid name' => ['cm', ['BadName' => self::componentStatic()]];
        yield 'invalid component' => ['cm', ['button' => new \stdClass()]];
    }

    public function testRejectsDuplicatePrefixes(): void
    {
        $registry = new ComponentRegistry();
        $registry->registerPrefix('cm', []);

        $this->expectException(RazorException::class);
        $this->expectExceptionMessage('already registered');

        $registry->registerPrefix('cm', []);
    }

    private function component(): ComponentInterface
    {
        return self::componentStatic();
    }

    private static function componentStatic(): ComponentInterface
    {
        return new class implements ComponentInterface {
            public function render(ComponentRenderContext $context): RenderedHtml
            {
                return RenderedHtml::empty();
            }
        };
    }
}
