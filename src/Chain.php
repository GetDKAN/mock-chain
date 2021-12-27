<?php

namespace MockChain;

use PHPUnit\Framework\TestCase;

/**
 * Class Chain.
 */
class Chain
{

    private $testCase;
    private $definitions = [];
    private $root = null;
    private $storeIds = [];
    private $store = [];

    private $lastClass;

    /**
     * @var array
     * Instances of already built mocks, keyed by object class.
     */
    private $mocks;

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

        if (!isset($this->definitions[$objectClass])) {
            $this->definitions[$objectClass] = [];
        }

        if ($method) {
            $this->definitions[$objectClass][$method] = $return;
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
        $mock = $this->getMockFor($objectClass);

        foreach ($this->getMethods($objectClass) as $method) {
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
                    $method,
                    func_get_args()
                );
            });
        }

        return $mock;
    }

    private function getMockFor($objectClass)
    {
        if (!isset($this->mocks[$objectClass])) {
            $methods = $this->getMethods($objectClass);

            $builder = $this->getBuilder($objectClass);

            if (!empty($methods)) {
                $builder->onlyMethods($methods);
            }

            $this->mocks[$objectClass] = $builder->getMockForAbstractClass();
        }

        return $this->mocks[$objectClass];
    }

    private function getBuilder($class)
    {
        $builder = $this->testCase->getMockBuilder($class);
        $builder->disableOriginalConstructor();
        return $builder;
    }

    private function buildReturn(string $objectClass, string $method, array $inputs, $return = null)
    {
        $return = (isset($return)) ? $return : $this->getReturn($objectClass, $method);

        $this->storeInputs($objectClass, $method, $inputs);

        if ($return instanceof ReturnNull) {
            $return = null;
        } elseif ($return instanceof Sequence) {
            $return = $this->buildReturn($objectClass, $method, $inputs, $return->return());
        } elseif ($return instanceof Options) {
            $actualReturn = $this->getReturnFromOptions($inputs, $return);
            $return = $this->buildReturn($objectClass, $method, $inputs, $actualReturn);
        } elseif ($return instanceof \Exception) {
            throw $return;
        } elseif (is_string($return)) {
            // Class exists is case-insensitive, to keep string from being
            // confused with actual classes, we make the, sometimes invalid,
            // assumption that class names start with an uppercase letter.
            if ((ucfirst($return) == $return) && (class_exists($return) || interface_exists($return))) {
                if ($return == $objectClass) {
                    $return = $this->getMockFor($objectClass);
                } else {
                    $return = $this->build($return);
                }
            }
        }

        return $return;
    }

    private function getReturnFromOptions($myInputs, $return)
    {

        if ($use = $return->getUse()) {
            $myInputs = array_merge($myInputs, $this->getStoredInput($use));
        }

        $jsonInput = json_encode($myInputs);
        $input = array_shift($myInputs);
    
        if (isset($index)) {
            $input = $return->return($myInputs[$index]);
        }
        
        $actualReturn = $return->return($input) ?? $return->return($jsonInput);
        
        if (!isset($actualReturn)) {
            throw new \Exception("Option {$input} does not exist.");
        }

        return $actualReturn;
    }

    private function storeInputs($objectClass, $method, $inputs)
    {
        $storeId = $this->getStoreId($objectClass, $method);
        if ($storeId) {
            $this->store[$storeId] = $inputs;
        }
    }

    private function getMethods($objectClass)
    {
        $methods = [];

        if (isset($this->definitions[$objectClass])) {
            foreach ($this->definitions[$objectClass] as $method => $blah) {
                $methods[] = $method;
            }
        }

        return $methods;
    }

    private function getReturn($objectClass, $method)
    {
        if (isset($this->definitions[$objectClass][$method])) {
            return $this->definitions[$objectClass][$method];
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
