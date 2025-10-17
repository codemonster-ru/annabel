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
}

class Foo
{
    public function __construct(public Bar $bar) {}
}

class Bar {}
