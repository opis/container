<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
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

class SetterTest extends TestCase
{
    /**
     * @var Container
     */
    protected $container;

    public function setUp()
    {
        $this->container = new Container();
    }

    public function testSetter()
    {
        $this->container->bind(Fixture\Foo::class)->setter(function($instance){
            $instance->setProperty(123);
        });

        $this->assertEquals(123, $this->container->make(Fixture\Foo::class)->getProperty());
    }

    public function testMultipleSetters()
    {
        $this->container->bind(Fixture\Foo::class)->setter(function($instance){
            $instance->setProperty(123);
        })->setter(function($instance){
            $instance->setProperty(321);
        });

        $this->assertEquals(321, $this->container->make(Fixture\Foo::class)->getProperty());
    }

    public function testStaticCallback()
    {
        $this->container->bind(Fixture\Foo::class)->setter(static::class . '::setProp1');
        $this->assertEquals(123, $this->container->make(Fixture\Foo::class)->getProperty());
    }

    public function testArrayCallback()
    {
        $this->container->bind(Fixture\Foo::class)->setter([$this, 'setProp2']);
        $this->assertEquals(123, $this->container->make(Fixture\Foo::class)->getProperty());
    }

    public static function setProp1($instance)
    {
        $instance->setProperty(123);
    }

    public function setProp2($instance)
    {
        $instance->setProperty(123);
    }
}