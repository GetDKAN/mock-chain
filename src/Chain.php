<?php

namespace MockChain;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class MockChain.
 */
class Chain
{

    private $testCase;
    private $definitons = [];
    private $root = null;
    private $storeIds = [];
    private $store = [];

    public function __construct(TestCase $case)
    {
        $this->testCase = $case;
    }

    public function add($objectClass, $method, $return, $storeId = null)
    {
        if (!$this->root) {
            $this->root = $objectClass;
        }

        $this->definitons[$objectClass][$method] = $return;

        if ($storeId) {
            $this->storeIds[$objectClass][$method] = $storeId;
        }

        return $this;
    }

    public function getMock()
    {
        return $this->build($this->root);
    }

    public function getStoredInput($id): array
    {
        return (isset($this->store[$id])) ? $this->store[$id] : [];
    }

    private function build($objectClass)
    {
        $methods = $this->getMethods($objectClass);

        $mock = $this->testCase->getMockBuilder($objectClass)
        ->disableOriginalConstructor()
        ->setMethods($methods)
        ->getMockForAbstractClass();

        foreach ($methods as $method) {
            $mock->method($method)->willReturnCallback(function () use ($objectClass, $mock, $method) {
                return $this->buildReturn($objectClass, $mock, $method, func_get_args());
            });
        }

        return $mock;
    }

    private function buildReturn(string $objectClass, $mock, string $method, array $inputs, $return = null)
    {
        $return = (isset($return)) ? $return : $this->getReturn($objectClass, $method);
        $storeId = $this->getStoreId($objectClass, $method);
        if ($storeId) {
            $this->store[$storeId] = $inputs;
        }

        if ($return instanceof ReturnNull) {
            return null;
        }
        elseif ($return instanceof Sequence) {
            return $this->buildReturn($objectClass, $mock, $method, $inputs, $return->return());
        } elseif ($return instanceof Options) {
            $myInputs = $inputs;
            if ($use = $return->getUse()) {
                $myInputs = array_merge($myInputs, $this->getStoredInput($use));
            }

            $index = $return->getIndex();
            if (count($myInputs) == 1) {
                $input = array_shift($myInputs);
            } elseif (isset($index)) {
                $input = $myInputs[$index];
            } else {
                $input = json_encode($myInputs);
            }

            return $this->buildReturn($objectClass, $mock, $method, $inputs, $return->return($input));
        } elseif ($return instanceof \Exception) {
            throw $return;
        } elseif (is_string($return)) {
            if (class_exists($return) || interface_exists($return)) {
                if ($return == $objectClass) {
                    return $mock;
                } else {
                    return $this->build($return);
                }
            }
        }

        return $return;
    }

    private function getMethods($objectClass)
    {
        $methods = [];

        if (isset($this->definitons[$objectClass])) {
            foreach ($this->definitons[$objectClass] as $method => $blah) {
                $methods[] = $method;
            }
        }

        return $methods;
    }

    private function getReturn($objectClass, $method)
    {
        if (isset($this->definitons[$objectClass][$method])) {
            return $this->definitons[$objectClass][$method];
        }
        return null;
    }

    private function getStoreId($objectClass, $method)
    {
        if (isset($this->storeIds[$objectClass][$method])) {
            return $this->storeIds[$objectClass][$method];
        }
        return null;
    }
}
