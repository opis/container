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

use Closure;
use Serializable;
use Opis\Closure\SerializableClosure;

class Dependency implements Serializable
{
    
    protected $concrete;
    
    protected $shared;
    
    protected $setters = array();
    
    protected $extenders = array();
    
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
    
    public function getExtenders()
    {
        return $this->extenders;
    }
    
    public function setter(Closure $setter)
    {
        $this->setters[] = $setter;
        return $this;
    }
    
    public function extender(Closure $extender)
    {
        $this->extenders[] = $extender;
        return $this;
    }
    
    public function serialize()
    {
        $map = function($value) { return SerializableClosure::from($value); };
        
        SerializableClosure::enterContext();
        
        $object = serialize(array(
            'concrete' => $this->concrete instanceof Closure
                        ? SerializableClosure::from($this->concrete)
                        : $this->concrete,
            'shared' => $this->shared,
            'setters' => array_map($map, $this->setters),
            'extenders' => array_map($map, $this->extenders),
        ));
        
        SerializableClosure::exitContext();
        
        return $object;
    }
    
    public function unserialize($data)
    {
        $object = unserialize($data);
        
        $map = function($value) { return $value->getClosure(); };
        
        $this->concrete = ($object['concrete'] instanceof SerializableClosure)
                        ? $object['concrete']->getClosure()
                        : $object['concrete'];
                        
        $this->shared = $object['shared'];
        $this->setters = array_map($map, $object['setters']);
        $this->extenders = array_map($map, $object['extenders']);
    }
    
}