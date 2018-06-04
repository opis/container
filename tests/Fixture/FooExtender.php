<?php
/* ===========================================================================
 * Copyright 2018 The Opis Project
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

namespace Opis\Container\Test\Fixture;

class FooExtender implements FooInterface
{
    private $foo;
    private $prop;

    public function __construct(FooInterface $foo)
    {
        $this->foo = $foo;
    }

    public function setProperty(string $value)
    {
        $this->prop = $value;
    }

    public function getProperty(): string
    {
        if ($this->prop === null) {
            return 'parent:' . $this->foo->getProperty();
        }
        return 'self:' . $this->prop;
    }


}