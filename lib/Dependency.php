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
 * This class holds informations about a concrete type
 */

class Dependency implements Serializable
{
    /** @var    string  $concrete   Concrete type name. */
    protected $concrete;
    
    /** @var    boolean $shared     A flag that indicates if this type will be build as a singleton(shared). */
    protected $shared;
    
    /** @var    array   $setters    An array of callbacks */
    protected $setters = array();
    
    /** @var    array   $extenders  An array of extenders. */
    protected $extenders = array();
    
    /**
     * Constructor
     *
     * @access public
     *
     * @param   string  $concrete   Concrete class name
     * @param   boolean $shared     Shared flag
     */
    
    public function __construct($concrete, $shared = false)
    {
        $this->concrete = $concrete;
        $this->shared = $shared;
    }
    
    /**
     * Returns the concrete class name
     *
     * @access  public
     * 
     * @return  string
     */
    
    public function getConcrete()
    {
        return $this->concrete;
    }
    
    /**
     * Returns TRUE if this concrete type is shared or FALSE otherwise
     *
     * @access  public
     * 
     * @return  boolean
     */
    
    public function isShared()
    {
        return $this->shared;
    }
    
    /**
     * Returns an array of setters that will be invoked after an instance
     * of the concrete class will be instanciated
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
     * Returns an array of extenders that will be invoked after an instance
     * of the concrete type will be instantiated
     *
     * @access  public
     * 
     * @return  array
     */
    
    public function getExtenders()
    {
        return $this->extenders;
    }
    
    /**
     * Add a setter that will be invoked after an instance of the concrete type is instantiated
     *
     * @access  public
     *
     * @param   \Closure    $setter Setter callback
     *
     * @return  \Opis\Container\Dependency  Self reference
     */
    
    public function setter(Closure $setter)
    {
        $this->setters[] = $setter;
        return $this;
    }
    
    /**
     * Add an extender that will be invoked after an instance of the concrete type is instantiated
     *
     * @access  public
     *
     * @param   \Closure    $setter Setter callback
     *
     * @return  \Opis\Container\Extender
     */
        
    public function extender(Closure $callback)
    {
        $extender = new Extender($callback);
        $this->extenders[] = $extender;
        return $extender;
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
            'concrete' => $this->concrete instanceof Closure
                        ? SerializableClosure::from($this->concrete)
                        : $this->concrete,
            'shared' => $this->shared,
            'setters' => array_map($map, $this->setters),
            'extenders' => $this->extenders,
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
        
        $this->concrete = ($object['concrete'] instanceof SerializableClosure)
                        ? $object['concrete']->getClosure()
                        : $object['concrete'];
                        
        $this->shared = $object['shared'];
        $this->extenders = $object['extenders'];
        $this->setters = array_map($map, $object['setters']);
    }
    
}
