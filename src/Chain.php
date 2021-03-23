<?php

namespace MockChain;

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

    private $lastClass;

    public function __construct(TestCase $case)
    {
        $this->testCase = $case;
    }

    public function add($objectClass, $method = null, $return = null, $storeId = null)
    {
        $this->lastClass = $objectClass;

        if (!$this->root) {
            $this->root = $objectClass;
        }

        if (!isset($this->definitons[$objectClass])) {
            $this->definitons[$objectClass] = [];
        }

        if ($method) {
            $this->definitons[$objectClass][$method] = $return;
        }

        if ($storeId) {
            $this->storeIds[$objectClass][$method] = $storeId;
        }

        return $this;
    }

    public function addd($method, $return = null, $storeId = null)
    {
        if (!isset($this->lastClass)) {
            throw new \Exception("You should use the add method before using addd.");
        }

        return $this->add($this->lastClass, $method, $return, $storeId);
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

        $builder = $this->getBuilder($objectClass);

        if (!empty($methods)) {
            $builder->setMethods($methods);
        }
        $mock = $builder->getMockForAbstractClass();

        foreach ($methods as $method) {
            if (!method_exists($objectClass, $method)) {
                throw new \Exception("method {$method} does not exist in {$objectClass}");
            }

            $mock->method($method)->willReturnCallback(function () use (
                $objectClass,
                $mock,
                $method
            ) {
                return $this->buildReturn(
                    $objectClass,
                    $mock,
                    $method,
                    func_get_args()
                );
            });
        }

        return $mock;
    }

    private function getBuilder($class)
    {
        $builder = $this->testCase->getMockBuilder($class);
        $builder->disableOriginalConstructor();
        return $builder;
    }

    private function buildReturn(string $objectClass, $mock, string $method, array $inputs, $return = null)
    {
        $return = (isset($return)) ? $return : $this->getReturn($objectClass, $method);
        $storeId = $this->getStoreId($objectClass, $method);
        if ($storeId) {
            $this->store[$storeId] = $inputs;
        }

        if ($return instanceof ReturnNull) {
            $return = null;
        } elseif ($return instanceof Sequence) {
            $return = $this->buildReturn($objectClass, $mock, $method, $inputs, $return->return());
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
            $actualReturn = $return->return($input);

            if (!isset($actualReturn)) {
              throw new \Exception("Option {$input} does not exist.");
            }

            $return = $this->buildReturn($objectClass, $mock, $method, $inputs, $actualReturn);
        } elseif ($return instanceof \Exception) {
            throw $return;
        } elseif (is_string($return)) {
            // Class exists is case insensitive, to keep string from being
            // confused with actual classes, we make the, sometimes invalid,
            // assumption that class names start with an uppercase letter.
            if ((ucfirst($return) == $return) && (class_exists($return) || interface_exists($return))) {
                if ($return == $objectClass) {
                    $return = $mock;
                } else {
                    $return = $this->build($return);
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
