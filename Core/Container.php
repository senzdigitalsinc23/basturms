<?php
namespace App\Core;

use ReflectionClass;
use Exception;

class Container
{
    protected array $bindings = [];
    protected array $instances = [];

    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function singleton(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
        $this->instances[$abstract] = null;
    }

    public function resolve(string $abstract)
    {
        if (isset($this->instances[$abstract]) && $this->instances[$abstract] !== null) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $object = $this->bindings[$abstract]($this);
            if (array_key_exists($abstract, $this->instances)) {
                $this->instances[$abstract] = $object;
            }
            return $object;
        }

        // Fallback: auto-resolve using reflection
        $reflector = new ReflectionClass($abstract);
        if (!$reflector->isInstantiable()) {
            throw new Exception("Class {$abstract} is not instantiable");
        }

        $constructor = $reflector->getConstructor();
        if (!$constructor) {
            return new $abstract;
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                $dependencies[] = $this->resolve($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();
            } else {
                throw new Exception("Unresolvable dependency: {$param->getName()}");
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
