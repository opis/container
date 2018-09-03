<?php
/* ===========================================================================
 * Copyright 2018 Zindex Software
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

use InvalidArgumentException;
use Opis\Closure\SerializableClosure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use Serializable;

class Container implements ContainerInterface, Serializable
{
    /** @var Dependency[] */
    protected $bindings = [];

    /** @var array */
    protected $instances = [];

    /** @var array */
    protected $aliases = [];

    /** @var ReflectionClass[] */
    protected $reflectionClass = [];

    /** @var ReflectionMethod[] */
    protected $reflectionMethod = [];

    /**
     * @param string $abstract
     * @param null|string|callable $concrete
     * @param array $arguments
     * @return Container
     */
    public function singleton(string $abstract, $concrete = null, array $arguments = []): self
    {
        return $this->bindDependency($abstract, $concrete, $arguments, true);
    }

    /**
     * @param string $abstract
     * @param null|string|callable $concrete
     * @param array $arguments
     * @return Container
     */
    public function bind(string $abstract, $concrete = null, array $arguments = []): self
    {
        return $this->bindDependency($abstract, $concrete, $arguments, false);
    }

    /**
     * @param string $alias
     * @param string $type
     * @return $this
     */
    public function alias(string $alias, string $type): self
    {
        $this->aliases[$alias] = $type;
        return $this;
    }

    /**
     * @param string $abstract
     * @param callable $extender
     * @return $this
     */
    public function extend(string $abstract, callable $extender): self
    {
        $this->resolve($abstract)->addExtender($extender);
        return $this;
    }

    /**
     * @param string $abstract
     * @return mixed
     */
    public function make(string $abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $dependency = $this->resolve($abstract);

        $instance = $this->build($dependency->getConcrete(), $dependency->getArguments());

        foreach ($dependency->getExtenders() as $callback) {
            $new_instance = $callback($instance, $this);
            if ($new_instance !== null) {
                $instance = $new_instance;
            }
        }

        if ($dependency->isShared()) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * @param string $abstract
     * @return mixed
     */
    public function __invoke(string $abstract)
    {
        return $this->make($abstract);
    }

    /**
     * @inheritDoc
     */
    public function get($id)
    {
        if (!isset($this->aliases[$id])) {
            throw new NotFoundException();
        }

        return $this->make($id);
    }

    /**
     * @inheritDoc
     */
    public function has($id)
    {
        return isset($this->aliases[$id]);
    }

    /**
     * @param string $abstract
     * @param string|null|callable $concrete
     * @param array $arguments
     * @param bool $shared
     * @return Container
     */
    protected function bindDependency(string $abstract, $concrete, array $arguments, bool $shared): self
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        if (!is_string($concrete) && !is_callable($concrete)) {
            throw new InvalidArgumentException('The second argument must be an instantiable class or a callable');
        }

        $dependency = new Dependency($concrete, $arguments, $shared);

        unset($this->instances[$abstract]);
        unset($this->aliases[$abstract]);

        $this->bindings[$abstract] = $dependency;

        return $this;
    }

    /**
     * Resolves an abstract type
     *
     * @param string $abstract
     * @param array $stack
     * @return Dependency
     */
    protected function resolve(string $abstract, array &$stack = []): Dependency
    {
        if (isset($this->aliases[$abstract])) {
            $alias = $this->aliases[$abstract];

            if (in_array($alias, $stack)) {
                $stack[] = $alias;
                $error = implode(' => ', $stack);
                throw new BindingException("Circular reference detected: $error");
            } else {
                $stack[] = $alias;
                return $this->resolve($alias, $stack);
            }
        }

        if (!isset($this->bindings[$abstract])) {
            $this->bind($abstract, null);
        }

        return $this->bindings[$abstract];
    }

    /**
     * Builds an instance of a concrete type
     *
     * @param string|callable $concrete
     * @param array $arguments
     * @return object
     */
    protected function build($concrete, array $arguments = [])
    {
        if (is_callable($concrete)) {
            return $concrete($this, $arguments);
        }

        if (isset($this->reflectionClass[$concrete])) {
            $reflection = $this->reflectionClass[$concrete];
        } else {
            try {
                $reflection = $this->reflectionClass[$concrete] = new ReflectionClass($concrete);
            } catch (\ReflectionException $e) {
                throw new BindingException('ReflectionException: ' . $e->getMessage());
            }
        }

        if (!$reflection->isInstantiable()) {
            throw new BindingException("The '${concrete}' type is not instantiable");
        }

        if (isset($this->reflectionMethod[$concrete])) {
            $constructor = $this->reflectionMethod[$concrete];
        } else {
            $constructor = $this->reflectionMethod[$concrete] = $reflection->getConstructor();
        }

        if (is_null($constructor)) {
            return new $concrete();
        }

        // Resolve arguments
        $parameters = array_diff_key($constructor->getParameters(), $arguments);

        /**
         * @var int $key
         * @var  \ReflectionParameter $parameter
         */
        foreach ($parameters as $key => $parameter) {
            if (null === $class = $parameter->getClass()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[$key] = $parameter->getDefaultValue();
                } else {
                    throw new BindingException("Could not resolve [$parameter]");
                }
                continue;
            }

            try {
                $class = $class->name;
                $arguments[$key] = isset($this->bindings[$class]) ? $this->make($class) : $this->build($class);
            } catch (BindingException $e) {
                if (!$parameter->isOptional()) {
                    throw $e;
                }
                $arguments[$key] = $parameter->getDefaultValue();
            }
        }

        ksort($arguments);

        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * @return string
     */
    public function serialize()
    {
        SerializableClosure::enterContext();

        $object = serialize([
            'bindings' => $this->bindings,
            'aliases' => $this->aliases,
        ]);

        SerializableClosure::exitContext();

        return $object;
    }

    /**
     * @param string $data
     */
    public function unserialize($data)
    {
        $object = unserialize($data);
        $this->bindings = $object['bindings'];
        $this->aliases = $object['aliases'];
    }

}
