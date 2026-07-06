<?php

namespace Codemonster\Annabel\Tests;

use Codemonster\Annabel\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ContainerTest extends TestCase
{
    public function test_bind_and_make(): void
    {
        $c = new Container();
        $c->bind('foo', fn () => 'bar');
        $this->assertSame('bar', $c->make('foo'));
    }

    public function test_container_implements_psr_container_interface(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, new Container());
    }

    public function test_get_resolves_entries_through_psr_interface(): void
    {
        $c = new Container();
        $c->bind('foo', fn () => 'bar');

        $this->assertSame('bar', $c->get('foo'));
    }

    public function test_has_returns_true_for_autowirable_classes(): void
    {
        $c = new Container();

        $this->assertTrue($c->has(Foo::class));
    }

    public function test_get_throws_psr_not_found_exception_for_unknown_entries(): void
    {
        $c = new Container();

        $this->expectException(NotFoundExceptionInterface::class);

        $c->get('missing-service');
    }

    public function test_singleton_returns_same_instance(): void
    {
        $c = new Container();
        $c->singleton(\stdClass::class, fn () => new \stdClass());

        $a = $c->make(\stdClass::class);
        $b = $c->make(\stdClass::class);

        $this->assertSame($a, $b);
    }

    public function test_autowiring_works(): void
    {
        $c = new Container();
        $c->bind(Bar::class, fn () => new Bar());
        $foo = $c->make(Foo::class);

        $this->assertInstanceOf(Foo::class, $foo);
        $this->assertInstanceOf(Bar::class, $foo->bar);
    }

    public function test_make_accepts_parameters_for_constructor(): void
    {
        $c = new Container();

        $subject = $c->make(ParamSubject::class, ['name' => 'annabel']);

        $this->assertSame('annabel', $subject->name);
    }

    public function test_make_passes_parameters_to_closure_binding(): void
    {
        $c = new Container();
        $c->bind(
            ParamSubject::class,
            /**
             * @param array<string, mixed> $params
             */
            function (Container $container, array $params = []): ParamSubject {
                $name = $params['name'] ?? null;
                $this->assertIsString($name);

                return new ParamSubject($name);
            },
        );

        $subject = $c->make(ParamSubject::class, ['name' => 'annabel']);

        $this->assertSame('annabel', $subject->name);
    }

    public function test_singleton_throws_when_parameters_change_after_resolution(): void
    {
        $c = new Container();
        $c->singleton(
            ParamSubject::class,
            /**
             * @param array<string, mixed> $params
             */
            function (Container $container, array $params = []): ParamSubject {
                $name = $params['name'] ?? null;
                $this->assertIsString($name);

                return new ParamSubject($name);
            },
        );
        $c->make(ParamSubject::class, ['name' => 'first']);

        $this->expectException(\RuntimeException::class);
        $c->make(ParamSubject::class, ['name' => 'second']);
    }
}

class Foo
{
    public function __construct(public Bar $bar)
    {
    }
}

class Bar
{
}

class ParamSubject
{
    public function __construct(public string $name)
    {
    }
}
