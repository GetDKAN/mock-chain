# mock-chain
[![CircleCI](https://circleci.com/gh/GetDKAN/mock-chain.svg?style=svg)](https://circleci.com/gh/GetDKAN/mock-chain)
[![Maintainability](https://api.codeclimate.com/v1/badges/d5f2830059dbf477002f/maintainability)](https://codeclimate.com/github/getdkan/mock-chain/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/d5f2830059dbf477002f/test_coverage)](https://codeclimate.com/github/getdkan/mock-chain/test_coverage)
[![GPLv3 license](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.en.html)


A library that helps create chains of mocked objects.

### Example

Imagine a dependency on an object of a class like this:

```php
$body->getSystem('nervous')->getOrgan('brain')->getName();
```

Creating a double for the body object with plain phpunit might look like this:

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

As you can see, the implementation of a simple chain of doubles/mocks can become very verbose. The purpose of this library is to make this process simpler. Here is the same mocked object implemented with a mock-chain:

```php
$body = (new Chain($this))
    ->add(Body::class, 'getSystem', System::class)
    ->add(System::class, 'getOrgan', Organ::class)
    ->add(Organ::class, 'getName', 'brain')
    ->getMock();
```