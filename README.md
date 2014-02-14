Opis Container
==============
[![Latest Stable Version](https://poser.pugx.org/opis/container/version.png)](https://packagist.org/packages/opis/container)
[![Latest Unstable Version](https://poser.pugx.org/opis/container/v/unstable.png)](//packagist.org/packages/opis/container)
[![License](https://poser.pugx.org/opis/container/license.png)](https://packagist.org/packages/opis/container)

Serializable dependency injection container
-------------------


###Installation

This library is available on [Packagist](https://packagist.org/packages/opis/container) and can be installed using [Composer](http://getcomposer.org)

```json
{
    "require": {
        "opis/container": "2.0.*"
    }
}
```

###Examples

```php
use Opis\Container\Container;

interface UserInterface
{
    public function name();
}

class UserClass implements UserInterface
{
    protected $name;
    
    public function setName($name)
    {
        $this->name = $name;
    }
    
    public function name()
    {
        return $this->name;
    }
}

$container = new Container();
$container->bind('UserInterface', 'UserClass')
          ->setter(function($instance, $container){
                $instance->setName('My name');
          });

$container->alias('UserInterface', 'User'); //aliasing

print $container('UserInterface')->name(); //> My name
print $container->make('UserInterface')->name(); //> My name

print $container->make('User')->name(); //> My name
print $container('User')->name(); //> My name

//Serialize
$serialized = serialize($container);
//Unserialize
$unserialized = unserialize($serialized);

print $unserialized('User')->name(); //> My name
print $unserialized->make('User')->name(); //> My name

```
