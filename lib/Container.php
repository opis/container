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
use Serializable;
use Opis\Closure\SerializableClosure;

class BindingException extends \RuntimeException
{
	
}

class Container implements Serializable
{
	protected $bindings  = array();
	
	protected $instances = array();
	
	protected $aliases = array();
	
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
				$class = $class->name;
				try
				{
					$arguments[] = isset($this->bindings[$class]) ? $this->make($class) : $this->build($class);
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
	
	public function singleton($abstract, $concrete = null)
	{
		return $this->bind($abstract, $concrete, true);
	}
	
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
	
	public function alias($concrete, $alias)
	{
		$this->aliases[$alias] = $concrete;
		return $this;
	}
	
	public function extend($abstract, Closure $extender)
	{
		$this->get($abstract)->extender($extender);
		return $this;
	}
	
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
			$newinstance = $extender($instance, $this);
			
			if($newinstance === null || $newinstance === $instance)
			{
				continue;
			}
			
			$instance = $newinstance;
		}
		
		if($dependency->isShared())
		{
			$this->instances[$abstract] = $instance;
		}
		
		return $instance;
	}
	
	public function __invoke($abstract, array $arguments = array())
	{
		return $this->make($abstract, $arguments);
	}
	
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
	
	public function unserialize($data)
	{
		$object = unserialize($data);
		$this->bindings = $object['bindings'];
		$this->aliases = $object['aliases'];
	}
    
}