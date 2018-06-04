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
use Opis\Container\Test\Fixture\FooExtender;
use PHPUnit\Framework\TestCase;

class ExtenderTest extends TestCase
{
    /**
     * @var Container
     */
    protected $container;

    public function setUp()
    {
        $this->container = new Container();
    }

    public function testExtender()
    {
        $this->container->bind(Fixture\FooInterface::class, Fixture\Foo::class)
        ->setter(function(Fixture\Foo $instance, Container $container){
            $instance->setProperty('foo');
        })->extender(function($instance){
           return new FooExtender($instance);
        });

        $this->assertEquals('parent:foo', $this->container->make(Fixture\FooInterface::class)->getProperty());
    }

    public function testExtenderCustomSetter()
    {
        $this->container->bind(Fixture\FooInterface::class, Fixture\Foo::class)
            ->setter(function(Fixture\Foo $instance, Container $container){
                $instance->setProperty('foo');
            })->extender(function($instance){
                return new FooExtender($instance);
            })->setter(function(Fixture\FooInterface $instance){
                $instance->setProperty('bar');
            });

        $this->assertEquals('self:bar', $this->container->make(Fixture\FooInterface::class)->getProperty());
    }

    public function testChainedExtenders()
    {
        $bind = $this->container->bind(Fixture\FooInterface::class, Fixture\Foo::class)
            ->setter(function(Fixture\Foo $instance, Container $container){
                $instance->setProperty('foo');
            });
        $bind->extender(function($instance){
                return new FooExtender($instance);
            });
        $bind->extender(function($instance){
            return new FooExtender($instance);
        });
        $this->assertEquals('parent:parent:foo', $this->container->make(Fixture\FooInterface::class)->getProperty());
    }

    public function testChainedExtendersSetter()
    {
        $bind = $this->container->bind(Fixture\FooInterface::class, Fixture\Foo::class)
            ->setter(function(Fixture\Foo $instance, Container $container){
                $instance->setProperty('foo');
            });
        $bind->extender(function($instance){
            return new FooExtender($instance);
        });
        $bind->extender(function($instance){
            return new FooExtender($instance);
        })->setter(function(Fixture\FooInterface $instance){
            $instance->setProperty('bar');
        });
        $this->assertEquals('self:bar', $this->container->make(Fixture\FooInterface::class)->getProperty());
    }
}