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

namespace Opis\Container;

use Closure;
use Serializable;
use Opis\Closure\SerializableClosure;

class Dependency implements Serializable
{
    /** @var string */
    protected $concrete;

    /** @var bool */
    protected $shared;

    /** @var callable[] */
    protected $setters = [];

    /** @var  Extender[] */
    protected $extenders = [];

    /** @var array */
    protected $arguments = [];


    /**
     * Dependency constructor.
     * @param string|callable $concrete
     * @param bool $shared
     */
    public function __construct($concrete, bool $shared = false)
    {
        $this->concrete = $concrete;
        $this->shared = $shared;
    }

    /**
     * @return string|callable
     */
    public function getConcrete()
    {
        return $this->concrete;
    }

    /**
     * @return bool
     */
    public function isShared(): bool
    {
        return $this->shared;
    }


    /**
     * @return callable[]
     */
    public function getSetters(): array
    {
        return $this->setters;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return Extender[]
     */
    public function getExtenders(): array
    {
        return $this->extenders;
    }

    /**
     * @param callable $setter
     * @return Dependency
     */
    public function setter(callable $setter): self
    {
        $this->setters[] = $setter;
        return $this;
    }

    /**
     * @param array $arguments
     * @return Dependency
     */
    public function arguments(array $arguments): self
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @param callable $callback
     * @return Extender
     */
    public function extender(callable $callback): Extender
    {
        $extender = new Extender($callback);
        $this->extenders[] = $extender;
        return $extender;
    }


    /**
     * @return string
     */
    public function serialize()
    {
        SerializableClosure::enterContext();

        $callback = function ($value) {
            return $value instanceof Closure ? SerializableClosure::from($value) : $value;
        };


        $object = serialize([
            'concrete' => $callback($this->concrete),
            'shared' => $this->shared,
            'setters' => array_map($callback, $this->setters),
            'extenders' => $this->extenders,
        ]);

        SerializableClosure::exitContext();

        return $object;
    }


    /**
     * @param string $data
     */
    public function unserialize($data)
    {
        $object = unserialize($data);

        $callback = function ($value) {
            return $value instanceof SerializableClosure ? $value->getClosure() : $value;
        };

        $this->concrete = $callback($object['concrete']);
        $this->shared = $object['shared'];
        $this->extenders = $object['extenders'];
        $this->setters = array_map($callback, $object['setters']);
    }

}
