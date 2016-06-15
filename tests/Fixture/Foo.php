<?php

namespace Opis\Container\Test\Fixture;


class Foo implements FooInterface
{
    protected $prop;

    public function setProperty($value)
    {
        $this->prop = $value;
    }

    public function getProperty()
    {
        return $this->prop;
    }
}