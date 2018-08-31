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
    protected $extenders = [];

    /** @var array */
    protected $arguments;

    /**
     * Dependency constructor.
     * @param string|callable $concrete
     * @param array $arguments
     * @param bool $shared
     */
    public function __construct($concrete, array $arguments, bool $shared)
    {
        $this->concrete = $concrete;
        $this->arguments = $arguments;
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
     * @param callable $callback
     */
    public function addExtender(callable $callback)
    {
        $this->extenders[] = $callback;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        SerializableClosure::enterContext();

        $concrete = !$this->concrete instanceof Closure ?: SerializableClosure::from($this->concrete);
        $extenders = [];
        $arguments = [];

        foreach ($this->extenders as $value) {
            $extenders[] = !$value instanceof Closure ?: SerializableClosure::from($value);
        }

        foreach ($this->arguments as $value) {
            $arguments[] = !$value instanceof Closure ?: SerializableClosure::from($value);
        }

        $object = serialize([
            'concrete' => $concrete,
            'arguments' => $arguments,
            'shared' => $this->shared,
            'extenders' => $extenders,
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

        $this->concrete = !$object['concrete'] instanceof SerializableClosure ?: $object['concrete']->getClosure();
        $this->shared = $object['shared'];

        foreach ($object['extenders'] as &$value) {
            if ($value instanceof SerializableClosure) {
                $value = $value->getClosure();
            }
        }

        foreach ($object['arguments'] as &$value) {
            if ($value instanceof SerializableClosure) {
                $value = $value->getClosure();
            }
        }

        $this->arguments = $object['arguments'];
        $this->extenders = $object['extenders'];
    }
}
