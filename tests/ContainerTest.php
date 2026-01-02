<?php

use Codemonster\Annabel\Container;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function test_bind_and_make()
    {
        $c = new Container();
        $c->bind('foo', fn() => 'bar');
        $this->assertSame('bar', $c->make('foo'));
    }

    public function test_singleton_returns_same_instance()
    {
        $c = new Container();
        $c->singleton(stdClass::class, fn() => new stdClass());

        $a = $c->make(stdClass::class);
        $b = $c->make(stdClass::class);

        $this->assertSame($a, $b);
    }

    public function test_autowiring_works()
    {
        $c = new Container();
        $c->bind(Bar::class, fn() => new Bar());
        $foo = $c->make(Foo::class);

        $this->assertInstanceOf(Foo::class, $foo);
        $this->assertInstanceOf(Bar::class, $foo->bar);
    }

    public function test_make_accepts_parameters_for_constructor()
    {
        $c = new Container();

        $subject = $c->make(ParamSubject::class, ['name' => 'annabel']);

        $this->assertSame('annabel', $subject->name);
    }

    public function test_make_passes_parameters_to_closure_binding()
    {
        $c = new Container();
        $c->bind(ParamSubject::class, fn($container, array $params) => new ParamSubject($params['name']));

        $subject = $c->make(ParamSubject::class, ['name' => 'annabel']);

        $this->assertSame('annabel', $subject->name);
    }

    public function test_singleton_throws_when_parameters_change_after_resolution()
    {
        $c = new Container();
        $c->singleton(ParamSubject::class, fn($container, array $params) => new ParamSubject($params['name']));
        $c->make(ParamSubject::class, ['name' => 'first']);

        $this->expectException(RuntimeException::class);
        $c->make(ParamSubject::class, ['name' => 'second']);
    }
}

class Foo
{
    public function __construct(public Bar $bar) {}
}

class Bar {}


class ParamSubject
{
    public function __construct(public string $name) {}
}
