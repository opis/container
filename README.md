Opis Container
==============

Serializable dependency injection container for PHP 5.3+

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