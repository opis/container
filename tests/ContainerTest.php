<?php
/* ===========================================================================
 * Copyright 2018 Zindex Software
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
        $obj = $this->container->make(Fixture\FooInterface::class);
        $this->assertInstanceOf(Fixture\FooInterface::class, $obj);
        $this->assertEquals('foo', $obj->getValue());
    }

    public function testMakeWithCallback()
    {
        $this->container->bind(Fixture\FooInterface::class, function(){
            return new Fixture\Foo();
        });
        $obj = $this->container->make(Fixture\FooInterface::class);
        $this->assertInstanceOf(Fixture\FooInterface::class, $obj);
        $this->assertEquals('foo', $obj->getValue());
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
        $this->container->alias('foo', Fixture\Foo::class);
        $this->assertInstanceOf(Fixture\Foo::class, $this->container->make('foo'));
    }

    public function testMultipleAliases()
    {
        $this->container->alias('foo', Fixture\Foo::class);
        $this->container->alias('bar', 'foo');
        $this->assertInstanceOf(Fixture\Foo::class, $this->container->make('bar'));
    }

    /**
     * @expectedException \Opis\Container\BindingException
     */
    public function testAliasCircularException()
    {
        $this->container->alias('foo', Fixture\Foo::class);
        $this->container->alias('bar', 'foo');
        $this->container->alias('foo', 'bar');
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
        $this->container->bind(Fixture\Bar2::class, null, [1 => 2]);
        $this->assertInstanceOf(Fixture\Bar2::class, $this->container->make(Fixture\Bar2::class));
    }

    public function testDICallback()
    {
        $this->container
            ->bind(Fixture\FooInterface::class, Fixture\Foo::class);
        $this->container->bind(Fixture\Bar2::class, function(Container $container, array $args = []){
            return new Fixture\Bar2($container->make(Fixture\FooInterface::class), 2);
        });
        $this->assertInstanceOf(Fixture\Bar2::class, $this->container->make(Fixture\Bar2::class));
    }

    public function testDICallbackWithArgs()
    {
        $this->container
            ->bind(Fixture\FooInterface::class, Fixture\Foo::class);
        $this->container->bind(Fixture\Bar2::class, function(Container $container, array $args = []){
            return new Fixture\Bar2($container->make(Fixture\FooInterface::class), $args['number']);
        }, ['number' => 2]);
        $this->assertInstanceOf(Fixture\Bar2::class, $this->container->make(Fixture\Bar2::class));
    }

    public function testExtendMethod()
    {
        $this->container->bind(Fixture\FooInterface::class, Fixture\Foo::class);

        $this->container->extend(Fixture\FooInterface::class, function(Fixture\Foo $instance){
            $instance->setValue('extended');
        });

        $this->assertEquals('extended', $this->container->make(Fixture\FooInterface::class)->getValue());
    }

    public function testWrapperExtender()
    {
        $this->container->bind(Fixture\FooInterface::class, Fixture\Foo::class);

        $this->container->extend(Fixture\FooInterface::class, function(Fixture\Foo $instance){
            return new Fixture\FooExtender($instance);
        });

        $this->assertEquals('FOO', $this->container->make(Fixture\FooInterface::class)->getValue());
    }

    public function testWrapperExtenderExtended()
    {
        $this->container->bind(Fixture\FooInterface::class, Fixture\Foo::class);

        $this->container->extend(Fixture\FooInterface::class, function(Fixture\FooInterface $instance){
            $instance->setValue('bar');
            return new Fixture\FooExtender($instance);
        });

        $this->container->extend(Fixture\FooInterface::class, function(Fixture\FooInterface $instance){
            $instance->setValue('bar');
            return new Fixture\FooExtender($instance);
        });

        $this->assertEquals('+BAR', $this->container->make(Fixture\FooInterface::class)->getValue());
    }

    public function testPsrNotFound()
    {
        $this->container->bind(Fixture\FooInterface::class, Fixture\Foo::class);

        $this->assertFalse($this->container->has(Fixture\FooInterface::class));
    }

    /**
     * @expectedException \Opis\Container\NotFoundException
     */
    public function testPsrNotFoundException()
    {
        $this->container->bind(Fixture\FooInterface::class, Fixture\Foo::class);

        $this->container->get(Fixture\FooInterface::class);
    }

    public function testPsrFound()
    {
        $this->container->bind(Fixture\FooInterface::class, Fixture\Foo::class);
        $this->container->alias('foo', Fixture\FooInterface::class);
        $this->assertInstanceOf(Fixture\FooInterface::class, $this->container->get('foo'));
    }

    public function testSerializeWithClass()
    {
        $this->container->bind(Fixture\FooInterface::class, Fixture\Foo::class);
        $container = unserialize(serialize($this->container));
        $obj = $container->make(Fixture\FooInterface::class);
        $this->assertInstanceOf(Fixture\FooInterface::class, $obj);
        $this->assertEquals('foo', $obj->getValue());
    }

    public function testSerializeWithClosure()
    {
        $this->container->bind(Fixture\FooInterface::class, function(){
            return new Fixture\Foo();
        });
        $container = unserialize(serialize($this->container));
        $obj = $container->make(Fixture\FooInterface::class);
        $this->assertInstanceOf(Fixture\FooInterface::class, $obj);
        $this->assertEquals('foo', $obj->getValue());
    }
}