<?php

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