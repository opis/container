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
use ReflectionClass;
use RuntimeException;
use InvalidArgumentException;
use Serializable;
use Opis\Closure\SerializableClosure;

/**
 * Container
 *
 * The serializable container class
 */

class Container implements Serializable
{
    /** @var	array	The container's bindings. */
    protected $bindings  = array();
    
    /** @var 	array 	The container's singleton instances. */
    protected $instances = array();
    
    /** @var 	array	Aliased types. */
    protected $aliases = array();
    
    /**
     * Builds an instance of a concrete class
     *
     * @access  protected
     *
     * @throws  \Opis\Container\BindingException    If the concrete type is not instantiable
     * 
     * @param   string|\Closure $concrete           The concrete class name or a closure callback
     * @param   array           $arguments          Arguments that will be passed to the constructor
     *
     * @return  mixed
     */
    
    protected function build($concrete, array $arguments = array())
    {
        if($concrete instanceof Closure)
        {
            return $concrete($this, $arguments);
        }
        
        $reflection = new ReflectionClass($concrete);
        
        if(!$reflection->isInstantiable())
        {
            throw new BindingException($concrete . ' is not instantiable');
        }
        
        $constructor = $reflection->getConstructor();
        
        if(is_null($constructor))
        {
            return new $concrete();
        }
        
        return $reflection->newInstanceArgs($this->resolveConstructor($constructor, $arguments));
        
    }
    
    /**
     * Resolves arguments that will be passed to the constructor of a concrete class
     *
     * @access  protected
     *
     * @throws  \Opis\Container\BindingException    If the arguments can't be resolved
     * 
     * @param   \ReflectionMethod   $constructor    Constructor info
     * @param	array               $arguments      Constructor's arguments
     *
     * @return  array   Resolved arguments
     */
    
    protected function resolveConstructor($constructor, array $arguments)
    {
        $parameters = array_diff_key($constructor->getParameters(), $arguments);
        
        foreach($parameters as $key => $parameter)
        {
            if(null === $class = $parameter->getClass())
            {
                if($parameter->isDefaultValueAvailable())
                {
                    $arguments[$key] = $parameter->getDefaultValue();
                }
                else
                {
                    throw new BindingException("Could not resolve [$parameter]");
                }
            }
            else
            {
                $class = $class->name;
                try
                {
                    $arguments[$key] = isset($this->bindings[$class]) ? $this->make($class) : $this->build($class);
                }
                catch(BindingException $e)
                {
                    if($parameter->isOptional())
                    {
                        $arguments[$key] = $parameter->getDefaultValue();
                    }
                    else
                    {
                        throw $e;
                    }
                }
            }
        }
        
        ksort($arguments);
        
        return $arguments;
    }
    
    /**
     * Resolves an abstract type to a concrete class
     *
     * @access  protected
     *
     * @throws  \RuntimeException   If circular reference is detected
     * 
     * @param   string  $abstract   Abstract class
     * @param   array   $stack      (optional) A stack of maped aliases used to prevent circular reference
     *
     * @return  string  Resolved concrete class
     */
    
    protected function get($abstract, array &$stack = array())
    {
        if(isset($this->aliases[$abstract]))
        {
            $alias = $this->aliases[$abstract];
            
            if(in_array($alias, $stack))
            {
                $stack[] = $alias;
                $error = implode(' => ', $stack);
                throw new RuntimeException("Circular reference detected: $error");
            }
            else
            {
                $stack[] = $alias;
                return $this->get($alias, $stack);
            }
        }
        
        if(!isset($this->bindings[$abstract]))
        {
            $this->bind($abstract, null);
        }
        
        return $this->bindings[$abstract];
    }
    
    /**
     * Binds an abstract type to a shared concrete type.
     *
     * @access  public
     *
     * @param   string          $abstract   Abstract type
     * @param   string|Closure  $concrete   (optional) Concrete class name or an anonymous function callback
     *
     * @return  \Opis\Container\Dependency
     */
    
    public function singleton($abstract, $concrete = null)
    {
        return $this->bind($abstract, $concrete, true);
    }
    
    /**
     * Binds an abstract type to a concrete type.
     *
     * @access  public
     *
     * @throws  \InvalidArgumentException	
     * 
     * @param   string          $abstract   Abstract type
     * @param   string|Closure  $concrete   (optional) Concrete class name or an anonymous function callback
     * @param   boolean         $shared     (optional) Mark this type as shared
     *
     * @return  \Opis\Container\Dependency
     */
        
    public function bind($abstract, $concrete = null, $shared = false)
    {
        if(is_null($concrete))
        {
            $concrete = $abstract;
        }
        
        if(!is_string($abstract))
        {
            throw new InvalidArgumentException('$abstract must be a string');
        }
        elseif(!is_string($concrete) && !($concrete instanceof Closure))
        {
            throw new InvalidArgumentException('$concrete must be a string or a closure');
        }
        
        $dependency = new Dependency($concrete, $shared);
        
        unset($this->instances[$abstract]);
        unset($this->aliases[$abstract]);
        
        $this->bindings[$abstract] = $dependency;
        
        return $dependency;
    }
    
    /**
     * Define a shorter name for a type
     *
     * @access  public
     *
     * @param   string  $type   Type name
     * @param   string  $alias  Alias name
     *
     * @return  \Opis\Container\Container   Self reference
     */
    
    public function alias($type, $alias)
    {
        $this->aliases[$alias] = $type;
        return $this;
    }
    
    /**
     * Extends an abstract type
     *
     * @access  public
     *
     * @param   string      $abstract   Abstract type name
     * @param   \Closure    $extender   The anonymous function callback that will return the extended instance of the specified abstract type
     *
     * @return  \Opis\Container\Extender
     */
    
    public function extend($abstract, Closure $extender)
    {
        return $this->get($abstract)->extender($extender);
    }
    
    /**
     * Builds an instance of an abstract type
     *
     * @access  public
     *
     * @param   string  $abstract   Abstract type name
     * @param   array   $arguments  (optional) Arguments that will be passed to the constructor
     *
     * @return  mixed
     */
    
    public function make($abstract, array $arguments = array())
    {
        if(isset($this->instances[$abstract]))
        {
            return $this->instances[$abstract];
        }
        
        $dependency = $this->get($abstract);
        
        $instance = $this->build($dependency->getConcrete(), $arguments);
        
        foreach($dependency->getSetters() as $setter)
        {
            $setter($instance, $this);
        }
        
        foreach($dependency->getExtenders() as $extender)
        {
            $callback = $extender->getCallback();
            
            $newinstance = $callback($instance, $this);
            
            if($newinstance === null || $newinstance === $instance)
            {
                continue;
            }
            
            foreach($extender->getSetters() as $setter)
            {
                $setter($newinstance, $this);
            }
            
            $instance = $newinstance;
        }
        
        if($dependency->isShared())
        {
            $this->instances[$abstract] = $instance;
        }
        
        return $instance;
    }
    
    /**
     * Invokes the 'make' method
     *
     * @access  public
     *
     * @param   string  $abstract   Abstract type name
     * @param   array   $arguments  (optional) Arguments that will be passed to the constructor
     *
     * @return  mixed
     */
        
    public function __invoke($abstract, array $arguments = array())
    {
        return $this->make($abstract, $arguments);
    }
    
    /**
     * Serialize the container
     *
     * @access  public
     *
     * @return  string
     */
    
    public function serialize()
    {
        SerializableClosure::enterContext();
        
        $object = serialize(array(
            'bindings' => $this->bindings,
            'aliases' => $this->aliases,
        ));
        
        SerializableClosure::exitContext();
        
        return $object;
    }
    
    /**
     * Deserialize the container
     *
     * @access  public
     *
     * @param   string  Serialized data
     */
    
    public function unserialize($data)
    {
        $object = SerializableClosure::unserializeData($data);
        $this->bindings = $object['bindings'];
        $this->aliases = $object['aliases'];
    }
    
}

/**
 * Exception class
 *
 * This exception is raised if an abstract type can't be resolved to a concrete type
 */

class BindingException extends \RuntimeException
{
	
}
