<?php
/* ===========================================================================
 * Copyright 2013-2018 The Opis Project
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================================ */

namespace Opis\Container\Test;

use Opis\Container\Container;
use Opis\Container\Test\Fixture\Bar2;
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

    public function testDIArguments()
    {
        $this->container
            ->bind(Fixture\FooInterface::class, Fixture\Foo::class);
        $this->container->bind(Fixture\Bar2::class)->arguments([1 => 2]);
        $this->assertInstanceOf(Fixture\Bar2::class, $this->container->make(Fixture\Bar2::class));
    }

    public function testDICallback()
    {
        $this->container
            ->bind(Fixture\FooInterface::class, Fixture\Foo::class);
        $this->container->bind(Fixture\Bar2::class, function(Container $container, array $args = []){
            return new Bar2($container->make(Fixture\FooInterface::class), 2);
        });
        $this->assertInstanceOf(Fixture\Bar2::class, $this->container->make(Fixture\Bar2::class));
    }

    public function testDICallbackWithArgs()
    {
        $this->container
            ->bind(Fixture\FooInterface::class, Fixture\Foo::class);
        $this->container->bind(Fixture\Bar2::class, function(Container $container, array $args = []){
            return new Bar2($container->make(Fixture\FooInterface::class), $args['number']);
        })->arguments(['number' => 2]);
        $this->assertInstanceOf(Fixture\Bar2::class, $this->container->make(Fixture\Bar2::class));
    }

}