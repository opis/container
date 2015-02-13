<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2013-2015 Marius Sarca
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

/**
 * Holds informations about an extender of an abstract type
 */

class Extender implements Serializable
{
    /** @var    \Closure    Callback. */
    protected $callback;
    
    /** @var    array       An array of setters. */
    protected $setters = array();
    
    
    /**
     * Constructor
     *
     * @access  public
     *
     * @param   \Closure    $callback   Extender's callback
     */
    
    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }
    
    /**
     * Get the callback associated with this extender
     *
     * @access  public
     *
     * @return  \Closure
     */
    
    public function getCallback()
    {
        return $this->callback;
    }
    
    /**
     * Get all setters associated with this extender
     *
     * @access  public
     *
     * @return  array
     */
    
    public function getSetters()
    {
        return $this->setters;
    }
    
    /**
     * Add a new setter
     *
     * @access  public
     *
     * @param   \Closure    $setter Add a new setter
     *
     * @return  \Opis\Container\Extender    Self reference
     */
    
    public function setter(Closure $setter)
    {
        $this->setters[] = $setter;
        return $this;
    }
    
    /**
     * Serialize
     *
     * @access  public
     *
     * @return  string  Serialized object
     */
    
    public function serialize()
    {
        $map = function($value) { return SerializableClosure::from($value); };
        
        SerializableClosure::enterContext();
        
        $object = serialize(array(
            'callback' => SerializableClosure::from($this->callback),
            'setters' => array_map($map, $this->setters),
        ));
        
        SerializableClosure::exitContext();
        
        return $object;
    }
    
    /**
     * Deserialize
     *
     * @access  public
     *
     * @param   string  $data   Serialized object
     */
    
    public function unserialize($data)
    {
        $object = SerializableClosure::unserializeData($data);
        
        $map = function($value) { return $value->getClosure(); };
        
        $this->callback = $object['callback']->getClosure();
        $this->setters = array_map($map, $object['setters']);
    }
    
}
