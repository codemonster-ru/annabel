<?php

declare(strict_types=1);

namespace Codemonster\Razor\Tests\Components;

use Codemonster\Razor\Components\ComponentRenderContext;
use Codemonster\Razor\Components\Contracts\ComponentInterface;
use Codemonster\Razor\Components\Contracts\ComponentResolverInterface;
use Codemonster\Razor\Components\RenderedHtml;
use PHPUnit\Framework\TestCase;

final class ComponentContractsTest extends TestCase
{
    public function testComponentReceivesPropsAndLazyMemoizedSlots(): void
    {
        $slotRenders = 0;
        $context = new ComponentRenderContext(
            ['tone' => 'strong', 'nullable' => null],
            [
                'default' => static function () use (&$slotRenders): RenderedHtml {
                    $slotRenders++;

                    return RenderedHtml::fromTrustedString('<em>Content</em>');
                },
            ],
        );
        $component = new class implements ComponentInterface {
            public function render(ComponentRenderContext $context): RenderedHtml
            {
                $tone = $context->prop('tone');
                if (!is_string($tone)) {
                    throw new \UnexpectedValueException('The tone prop must be a string.');
                }

                return RenderedHtml::fromTrustedString(
                    $tone . ':' . $context->slot('default')->value() . $context->slot('default')->value(),
                );
            }
        };

        self::assertTrue($context->hasProp('nullable'));
        self::assertNull($context->prop('nullable', 'fallback'));
        self::assertTrue($context->hasSlot('default'));
        self::assertSame('fallback', $context->prop('missing', 'fallback'));
        self::assertSame('', $context->slot('missing')->value());
        self::assertSame('strong:<em>Content</em><em>Content</em>', $component->render($context)->value());
        self::assertSame(1, $slotRenders);
    }

    public function testResolverContractSeparatesPrefixOwnershipFromResolution(): void
    {
        $component = new class implements ComponentInterface {
            public function render(ComponentRenderContext $context): RenderedHtml
            {
                return RenderedHtml::empty();
            }
        };
        $resolver = new class ($component) implements ComponentResolverInterface {
            public function __construct(private readonly ComponentInterface $component)
            {
            }

            public function handles(string $tag): bool
            {
                return str_starts_with($tag, 'test-');
            }

            public function resolve(string $tag): ?ComponentInterface
            {
                return $tag === 'test-known' ? $this->component : null;
            }

            public function cacheSignature(): string
            {
                return 'test-signature';
            }
        };

        self::assertTrue($resolver->handles('test-missing'));
        self::assertSame($component, $resolver->resolve('test-known'));
        self::assertNull($resolver->resolve('native-element'));
        self::assertSame('test-signature', $resolver->cacheSignature());
    }
}
