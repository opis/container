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
	
	protected $instances = array();
    
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
        
        $this->bindings[$abstract] = new Dependency($concrete, $shared);
		unset($this->instances[$abstract]);
		
		return $this->bindings[$abstract];
    }
    
    
    public function get($abstract, array $arguments = array())
    {
        if(!isset($this->bindings[$abstract]))
        {
            throw new InvalidArgumentException("No bindings were found for {$abstract} type");
        }
        
		return $this->bindings[$abstract];
    }
	
	public function __invoke($abstract, array $arguments = array())
	{
		if(isset($this->instances[$abstract]))
		{
			return $this->instances[$abstract];
		}
		
		$dependency = $this->get($abstract);
		
		$instance = $this->build($dependency->getConcrete(), $arguments);
		
		foreach($dependency->getExtenders() as $extender)
		{
			$extender($instance, $this);
		}
		
		if($instance instanceof ContainerAwareInterface)
		{
			$instance->setContainer($this);
		}
		
		if($dependency->isShared())
		{
			$this->instances[$abstract] = $instance;
		}
		
		return $instance;
	}
    
}