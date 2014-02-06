<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2013 Marius Sarca
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

use Serializable;

class Dependency implements Serializable
{
    
    protected $concrete;
    
    protected $shared;
    
    protected $setters = array();
    
    public function __construct($concrete, $shared = false)
    {
        $this->concrete = $concrete;
        $this->shared = $shared;
    }
    
    public function getConcrete()
    {
        return $this->concrete;
    }
    
    public function isShared()
    {
        return $this->shared;
    }
    
    public function getSetters()
    {
        return $this->setters;
    }
    
    public function setter(callable $setter)
    {
        $this->setters[] = $setter;
        return $this;
    }
    
    public function serialize()
    {
        return serialize(array(
            'concrete' => $this->concrete,
            'shared' => $this->shared,
            'setters' => $this->setters,
        ));
    }
    
    public function unserialize($data)
    {
        $object = unserialize($data);
        foreach($object as $key => $value)
        {
            $this->{$key} = $value;
        }
    }
    
}