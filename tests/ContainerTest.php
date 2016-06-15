<?php

namespace Opis\Container\Test;

use Opis\Container\Container;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    /**
     * @var Container
     */
    protected $container;

    public function setUp()
    {
        $this->container = new Container();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBindWrongConcreteType()
    {
        $this->container->bind('foo', 1);
    }

    public function testMakeWithoutBind()
    {
        $this->assertInstanceOf(Fixture\Foo::class, $this->container->make(Fixture\Foo::class));
    }

    /**
     * @expectedException \Opis\Container\BindingException
     */
    public function testMakeWithoutBindFail()
    {
        $this->container->make(Fixture\FooInterface::class);
    }


    public function testMake()
    {
        $this->container->bind(Fixture\FooInterface::class, Fixture\Foo::class);
        $this->assertInstanceOf(Fixture\FooInterface::class, $this->container->make(Fixture\FooInterface::class));
    }

    public function testMakeWithCallback()
    {
        $this->container->bind(Fixture\FooInterface::class, function(){
            return new Fixture\Foo();
        });
        $this->assertInstanceOf(Fixture\FooInterface::class, $this->container->make(Fixture\FooInterface::class));
    }

    public function testSingleton()
    {
        $this->container->singleton(Fixture\FooInterface::class, Fixture\Foo::class);
        $obj1 = $this->container->make(Fixture\FooInterface::class);
        $obj2 = $this->container->make(Fixture\FooInterface::class);
        $this->assertSame($obj1, $obj2);
    }

    public function testAlias()
    {
        $this->container->alias(Fixture\Foo::class, 'foo');
        $this->assertInstanceOf(Fixture\Foo::class, $this->container->make('foo'));
    }

    public function testMultipleAliases()
    {
        $this->container->alias(Fixture\Foo::class, 'foo');
        $this->container->alias('foo', 'bar');
        $this->assertInstanceOf(Fixture\Foo::class, $this->container->make('bar'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testAliasCircularException()
    {
        $this->container->alias(Fixture\Foo::class, 'foo');
        $this->container->alias('foo', 'bar');
        $this->container->alias('bar', 'foo');
        $this->assertInstanceOf(Fixture\Foo::class, $this->container->make('bar'));
    }


    public function testDependencyInjection()
    {
        $this->container->bind(Fixture\FooInterface::class, Fixture\Foo::class);
        $this->assertInstanceOf(Fixture\Bar::class, $this->container->make(Fixture\Bar::class));
    }

    public function testDIArguments1()
    {
        $this->container->bind(Fixture\FooInterface::class, Fixture\Foo::class);
        $this->assertInstanceOf(Fixture\Bar2::class, $this->container->make(Fixture\Bar2::class, [1 => 2]));
    }

}