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
Here, we are calling the constructor of the `Chain` class to create a `Chain` object. The extra parenthesis around the call to the constructor allow us to immediately start calling methods without keeping a reference of the `Chain` object itself.

The `Chain` class is a "better" interface around the mocking capabilities provided by `phpunit`, but all the mocking power comes from `phpunit`. This is why the constructor of the `Chain` class takes a `PHPUnit\Framework\TestCase` object.

```php
->add(Organ::class, "getName", "heart")
```
The `add` method is used to inform the `Chain` object of the structure of the mock or mocks that we wish to create.

The first argument to `add` is the **full name of the class** for which we want to create a mock object. In our example we want to create an `Organ` object.

The class name is the only required parameter in the `add` method, but more often than not we want to mock a call to a method of an object. The extra, optional, parameters allow exactly that.

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

To mock multiple methods, we simply call `add` multiple times.

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
When `addd` is used, the `Chain` assumes that the method is a mock of whatever the last named class was before `addd` was called. In our case it is the `Organ` class.

The impact is very subtle, but we have found that in complex mocks, using `addd` also provides a visual break to easily see the different types of objects being mocked.

### Returning Mocks
The third parameter of the `add` method can be given anything to be return by the mocked method: strings, arrays, objects, booleans, etc.

We can even return another mocked object. Addressing this scenario is the main reason this library exist, and why it is called `mock-chain`: We want to be able to define chains of mocked objects and methods easily.

To accomplish our goal we simply return the class name of the mock object we want to return.

```php
$mock = (new Chain($this))
        ->add(System::class, "getOrgan", Organ::class)
        ->add(Organ::class, "getName", "heart")
        ->addd("shoutName", "HEART")
        ->getMock();
```

It is important to note that in this new example the **main** mock object returned by `getMock` is of the `System` class. Whatever the first named class that is registered with the `Chain` is, becomes the **root** of the chain. Any other mocks will only be accessible through interactions with the **root** object.

A second mock object of class `Organ` is also being defined, and it is accessible through the `getOrgan` method from the mocked `System` object.

Given this structure, we can make assertions across our mocks:

```php
$this->assertEquals("heart",
    $mock->getOrgan("blah")->getName());
```

### Mocking Different Returns with Sequences
Through some paths of our code, we might need the same mocked object to respond differently under different circumstances. There are multiple ways to accomplish this with `mock-chain`, but the simplest way is to use the `Sequence` class.

A `Sequence` allows us to define a number of things that should be returned, in order, every time a method is called.

```php
$organNames = (new Sequence())
        ->add("heart")
        ->add("lungs");

$mock = (new Chain($this))
  ->add(Organ::class, "getName", $organNames)
  ->getMock();

$this->assertEquals("heart", $mock->getName());
$this->assertEquals("lungs", $mock->getName());
```

In this example we are creating a `Sequence` of organ names, and we are telling the chain that this sequence of things should be returned when the `getName` method in our `Organ` mock is called.

Our assertions confirm the expected behavior by showing that _"heart"_ is returned when `getName` is first called, and _"lungs"_ when `getName` is called a second time. If `getName` was to be called a third or fourth time, _"lungs"_ would be returned again.

Similarly to how we can return anything from mocked methods, including other mocks, we can do the same with sequences.

```php
$organs = (new Sequence())
        ->add(Organ::class)
        ->add("lungs");

$mock = (new Chain($this))
  ->add(System::class, "getOrgan", $organs)
  ->add(Organ::class, "getName", "heart")
  ->getMock();

$this->assertEquals("heart", $mock->getOrgan("blah")->getName());
$this->assertEquals("lungs", $mock->getOrgan("blah"));
```

Here we are returning a mock of `Organ` as the first element of the sequence, and a string as the second without any issues.

### Mocking Different Returns with Options

`Options` give us a bit more power than `Sequence` by allowing us to take into account the input to the mocked methods as we decide what should be returned.

```php
$organs = (new Options())
  ->add("heart", Organ::class)
  ->add("lungs", "yep, the lungs");

$mock = (new Chain($this))
  ->add(System::class, "getOrgan", $organs)
  ->add(Organ::class, "getName", "heart")
  ->getMock();

$this->assertEquals("yep, the lungs",
    $mock->getOrgan("lungs"));
$this->assertEquals("heart",
    $mock->getOrgan("heart")->getName());
```

In this `Options` object we are defining that a call to `getOrgan` with an input of _"hearts"_ should return our `Organ` mock, but a call to `getOrgan` with an input of _"lungs"_ should return the string _"yep, the lungs"_. Notice in the assertions that the order of the options does not matter.

If we are dealing with more complex methods that take multiple inputs/arguments, `Options` have two mechanisms to deal with these scenarios: index and JSON string.

#### Index
```php
$organs = (new Options())
  ->add("heart",Organ::class)
  ->add("lung", "yep, the left lung")
  ->index(0);

$mock = (new Chain($this))
  ->add(System::class, "getOrganByNameAndIndex", $organs)
  ->add(Organ::class, "getName", "heart")
  ->getMock();

$this->assertEquals("yep, the left lung",
  $mock->getOrganByNameAndIndex("lung", 0));
$this->assertEquals("heart",
  $mock->getOrganByNameAndIndex("heart", 0)->getName());
```

In this example we have a more complex method `getOrganByNameAndIndex` that takes 2 arguments: an **organ name** and an **index**. If during the process of mocking we determine that we only care about one of the arguments to our method, we could model that by using the `index` method of the `Options` class. In this example, we are describing that we only care about the first argument, the **organ name**, when determining what to return.

#### JSON string
```php
$organs = (new Options())
  ->add(json_encode(["lung", 0]),"yep, the left lung")
  ->add(json_encode(["lung", 1]), "yep, the right lung");

$mock = (new Chain($this))
  ->add(System::class, "getOrganByNameAndIndex", $organs)
  ->add(Organ::class, "getName", "heart")
  ->getMock();

$this->assertEquals("yep, the left lung",
  $mock->getOrganByNameAndIndex("lung", 0));
$this->assertEquals("yep, the right lung",
  $mock->getOrganByNameAndIndex("lung", 1));
```
When we have complex methods with multiple arguments that we want to take into account when making decisions about what to return, we can always create a JSON string of an array representing the inputs to our method.

In our example, when the inputs to `getOrganByNameAndIndex` are _"lung"_ and _0_, we want to return _"yep, the left lung"_. But, if the inputs to our method are _"lung"_, and _1_, we would like to return _"yep, the right lung"_.


