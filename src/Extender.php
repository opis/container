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

namespace Opis\Container;

use Closure;
use Serializable;
use Opis\Closure\SerializableClosure;

class Extender implements Serializable
{
    /** @var callable */
    protected $callback;

    /** @var callable[] */
    protected $setters = [];


    /**
     * Extender constructor.
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }


    /**
     * @return callable
     */
    public function getCallback(): callable
    {
        return $this->callback;
    }


    /**
     * @return callable[]
     */
    public function getSetters(): array
    {
        return $this->setters;
    }


    /**
     * @param callable $setter
     * @return Extender
     */
    public function setter(callable $setter): self
    {
        $this->setters[] = $setter;
        return $this;
    }


    /**
     * @return string
     */
    public function serialize()
    {
        $map = function ($value) {
            return $value instanceof Closure ? SerializableClosure::from($value) : $value;
        };

        SerializableClosure::enterContext();

        $object = serialize([
            'callback' => $map($this->callback),
            'setters' => array_map($map, $this->setters),
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

        $map = function ($value) {
            return $value instanceof SerializableClosure ? $value->getClosure() : $value;
        };

        $this->callback = $map($object['callback']);
        $this->setters = array_map($map, $object['setters']);
    }

}
