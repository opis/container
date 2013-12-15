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
use ReflectionClass;
use RuntimeException;
use InvalidArgumentException;

class Container
{
    protected $bindings  = array();
    
    protected $extenders = array();
    
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
    
    protected function resolveConstructor($constructor, array $arguments)
    {
        
        $parameters = array_diff_key($constructor->getParameters(), $arguments);
        
        foreach($parameters as $parameter)
        {
            if(null === $class = $parameter->getClass())
            {
                if($parameter->isDefaultValueAvailable())
                {
                    $arguments[] = $parameter->getDefaultValue();
                }
                else
                {
                    throw new BindingException("Could not resolve [$parameter]");
                }
            }
            else
            {
                try
                {
                    $arguments[] = isset($this->bindings[$class]) ? $this->get($class) : $this->build($class);
                }
                catch(BindingException $e)
                {
                    if($parameter->isOptional())
                    {
                        $arguments[] = $parameter->getDefaultValue();
                    }
                    else
                    {
                        throw $e;
                    }
                }
            }
        }
        
        return $arguments;
    }
    
    public function bind($abstract, $concrete = null, $shared = false)
    {
        if(is_null($concrete))
        {
            $concrete = $abstract;
        }
        
        $this->bindings[$abstract] = array(
            'concrete' => $concrete,
            'shared' => $shared,
            'instance' => null,
        );
    }
    
    public function extend($abstract, Closure $extender)
    {
        if(!isset($this->bindings[$abstract]))
        {
            throw new InvalidArgumentException("No bindings were found for {$abstract} type");
        }
        
        $this->extenders[$abstract][] = $extender;
    }
    
    
    public function get($abstract, array $arguments = array())
    {
        if(!isset($this->bindings[$abstract]))
        {
            throw new InvalidArgumentException("No bindings were found for {$abstract} type");
        }
        
        $binding = &$this->bindings[$abstract];
        
        
        if($binding['shared'] === true)
        {
            if($binding['instance'] === null)
            {
                $binding['instance'] = $this->build($binding['concrete'], $arguments);
            }
            
            $instance = $binding['instance'];
        }
        else
        {
            $instance = $this->build($binding['concrete'], $arguments);
        }
        
        if(isset($this->extenders[$abstract]))
        {
            foreach($this->extenders[$abstract] as $extender)
            {
                $instance = $extender($instance, $this);
            }
        }
		
		if($instance instanceof ContainerAwareInterface)
		{
			$instance->setContainer($this);
		}
        
        return $instance;
        
    }
	
	public function __invoke($abstract, array $arguements = array())
	{
		return $this->get($abstract, $arguements);
	}
    
}