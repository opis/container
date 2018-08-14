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

    /** @var  callable[] */
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
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return callable[]
     */
    public function getExtenders(): array
    {
        return $this->extenders;
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
     * @return Dependency
     */
    public function extender(callable $callback): self
    {
        $this->extenders[] = $callback;
        return $this;
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
            'extenders' => array_map($callback, $this->extenders),
            'arguments' => $this->arguments,
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
        $this->extenders = array_map($callback, $object['extenders']);
        $this->arguments = $object['arguments'];
    }
}
