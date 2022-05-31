# Typed Enumerations
Typed-enum is A simple, lightweight and efficient enumeration library. Type hint your enumerated options efficiently and strictly to eliminate unintended side effects and bugs introduced by typos.

## How to use:

### Download

Download with composer:

### Extending from TypedEnum

Extend from TypedEnum and use any scalar constants to define the enumeration:

```php
final class Foo extends TypedEnum {
    private const BAR = 'bar';
    private const BAZ = 2;
    private const FOO = 2.1;
} 
```

You can now create instances of Foo by using the __callstatic method based on the name of the constant:

```php
Foo::BAR();  // Returns an instance of Foo with value 'bar'
```

### Testing for equality

As a bonus, you can now use strict comparisons. TypedEnum guarantees there to be only one instance of the same enumerated value at runtime.

```php
function isBar(Foo $enum): bool {
    return Foo::BAR() === $enum;     
}
```

### Return the actual value:

If your application depends on the value assigned to the enumeration, you can easily fetch it with the getValue() getter.

```php
echo Foo::BAR()->getValue(); //'bar'
```

### Resolve the enumeration

Resolve an enumeration based on its value.
The resolve method will return all enumerations with the same value in the array.

```php
echo Foo::resolve('bar')[0] === Foo::BAR() // true 
```

## Tips

### IDE integration

You can easily use the power of you ide by simply adding @method tags in the docblock of your class like this:

```php
/**
 * @method static TypedEnum TEST()
 */
final class Foo extends TypedEnum {
    private const TEST = 'test';
}
```

### Psalm

We built TypedEnum with psalm! With these powerful annotations, you can now hint the scalar value of the enumeration:

```php
/**
 * @extends TypedEnum<string>
 */
final class Foo extends TypedEnum {
    private const TEST = 'test';
}
```


### Easy Bug protection

Php 7.2 allows for protected constants, and 7.4 uses private ones. Use these features to protect against unintended side effects!

```
echo Foo::BAR === Foo::BAR() // echo's false but is impossible if it is a protected constant
```


***
Developed by [laudis](https://laudis.tech)
