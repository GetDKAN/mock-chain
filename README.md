# mock-chain
[![CircleCI](https://circleci.com/gh/GetDKAN/mock-chain.svg?style=svg)](https://circleci.com/gh/GetDKAN/mock-chain)
[![Maintainability](https://api.codeclimate.com/v1/badges/d5f2830059dbf477002f/maintainability)](https://codeclimate.com/github/getdkan/mock-chain/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/d5f2830059dbf477002f/test_coverage)](https://codeclimate.com/github/getdkan/mock-chain/test_coverage)
[![GPLv3 license](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.en.html)


Create complex mocks/doubles with ease.

### Example

Imagine a chain of methods and objects like this:

```php
$body->getSystem('nervous')->getOrgan('brain')->getName();
```

Creating a mock for the body object with phpunit alone might look like this:

```php
$organ = $this->getMockBuilder(Organ::class)
    ->disableOriginalConstructor()
    ->onlyMethods(['getName'])
    ->getMock();

$organ->method('getName')->willReturn('brain');

$system = $this->getMockBuilder(System::class)
  ->disableOriginalConstructor()
  ->onlyMethods(['getOrgan'])
  ->getMock();

$system->method('getOrgan')->willReturn($organ);

$body = $this->getMockBuilder(Body::class)
  ->disableOriginalConstructor()
  ->onlyMethods(['getSystem'])
  ->getMock();

$body->method('getSystem')->willReturn($system);
```

The implementation of a simple chain of mocks can become very verbose. The purpose of this library is to make this process simpler. Here is the same mocked object implemented with a mock-chain:

```php
$body = (new Chain($this))
    ->add(Body::class, 'getSystem', System::class)
    ->add(System::class, 'getOrgan', Organ::class)
    ->add(Organ::class, 'getName', 'brain')
    ->getMock();
```

## Documentation

The majority of the work that can be done with this library happens through a single class: The `Chain` class.

By exploring the few methods exposed by this class, we should be able to understand the full power of the library.

### Mocking an Object and a Single Method

With `mock-chain` we can mock an object and one of its methods in a single line of code.

```php
$mock = (new Chain($this))
      ->add(Organ::class, "getName", "heart")
      ->getMock();
```

Let's explore what is happening here.
```php
(new Chain($this))
```
Here we are calling the constructor of the `Chain` class to create a `Chain` object. The extra parenthesis around the call of the constructor allow us to immediately start calling methods of the constructed object without keeping a reference of the `Chain` object itself.

The `Chain` class is a "better" interface around the mocking capabilities provided by `phpunit`, but all the mocking power comes from `phpunit`. This is why the constructor of the `Chain` class takes a `PHPUnit\Framework\TestCase` object.

```php
->add(Organ::class, "getName", "heart")
```
The `add` method is used to inform the `Chain` object of the structure of the mock or mocks that we wish to be created.

The first argument to `add` is the **full name of the class** for which we want to create a mock object. In our example we want to create an `Organ` object.

The class name is the only required parameter in the `add` class, but more often than not we want to mock a call to a method of an object. The extra, optional, parameters allow exactly that.

The second parameter is the **name of a method** in the `Organ` class: `getName`.

The third parameter is what we want the **mocked object to return** when `getName` is called. In our example we want to return the string _"heart"_.

Finally,
```php
->getMock()
```
returns the mock object constructed by the `Chain` class.

We can easily check in a test that our mock object is working as expected:

```php
$this->assertEquals("heart", $mock->getName());
```
### Mocking an Object and Multiple Methods

To mock multiple methods of an object, we simply call `add` multiple times.

```php
$mock = (new Chain($this))
        ->add(Organ::class, "getName", "heart")
        ->add(Organ::class, "shoutName", "HEART")
        ->getMock();
```

`Chain` assumes that each class name is used to generate a single mock object of that class. So, this chain does not create two mock `Organ` objects, but a single `Organ` object with both `getName` and `shoutName` mocked.

Because it is common to mock multiple methods for a single object, the `Chain` class provides a method to make this operation less verbose: `addd` _(with three Ds)_.

With the `addd` method we can simplify our example like this:

```php
$mock = (new Chain($this))
        ->add(Organ::class, "getName", "heart")
        ->addd("shoutName", "HEART")
        ->getMock();
```
When `addd` is used, the `Chain` assumes that the method is a mock of whatever the last named class was before `addd` is called. In our case it is, the only one named, `Organ`.

The impact is very subtle, but we have found that in complex mocks, using `addd` also provides a visual break to more easily see the different types of objects being mocked.

### Returning Mocks
The third parameter of the `add` method can be given anything to be return by the mocked method: strings, arrays, objects, booleans, etc.

But what if what we want returned is another mocked object? Addressing this scenario was the main purpose for which this library was created, and why the library is called `mock-chain`: We want to be able to define chains of mock objects and methods easily.

To accomplish our goal we simply return the class name of the mock object we want to return.

```php
$mock = (new Chain($this))
        ->add(System::class, "getOrgan", Organ::class)
        ->add(Organ::class, "getName", "heart")
        ->addd("shoutName", "HEART")
        ->getMock();
```

It is important to note that in this new example the **main** mock object returned by `getMock` is of the `System` class. Whatever the first named class that is registered with the `Chain` becomes the **root** of the chain. Any other mocks will only be accessible through interactions with the **root** object.

A second mock object of class `Organ` is also being defined, and it is accessible through the `getOrgan` method from the mocked `System` object.

Given this structure, we can make assertions across our mocks:

```php
$this->assertEquals("heart",
    $mock->getOrgan("blah")->getName());
```

