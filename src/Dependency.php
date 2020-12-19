<?php
/* ===========================================================================
 * Copyright 2018-2020 Zindex Software
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

class Dependency
{
    /** @var string|callable */
    protected $concrete;

    /** @var bool */
    protected bool $shared;

    /** @var callable[] */
    protected array $extenders = [];

    /** @var array */
    protected array $arguments;

    /**
     * Dependency constructor.
     * @param string|callable $concrete
     * @param array $arguments
     * @param bool $shared
     */
    public function __construct(string|callable $concrete, array $arguments, bool $shared)
    {
        $this->concrete = $concrete;
        $this->arguments = $arguments;
        $this->shared = $shared;
    }

    /**
     * @return string|callable
     */
    public function getConcrete(): string|callable
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
    public function addExtender(callable $callback): void
    {
        $this->extenders[] = $callback;
    }
}
