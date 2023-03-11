<?php

namespace MockChain;

use PHPUnit\Framework\TestCase;

/**
 * Primary mock-chain class.
 *
 * Defines a chain of object mocks that can call each other and simulate
 * complex dependencies.
 *
 * @package MockChain
 */
class Chain
{
    /**
     * The PHPUnit TestCase class to base our mock chain on.
     */
    private TestCase $testCase;

    private array $definitions = [];
    private $root = null;
    private array $storeIds = [];
    private array $store = [];

    private $lastClass;

    /**
     * Constructor.
     *
     * @param \PHPUnit\Framework\TestCase $case
     *   As a chain is usually instantiated from inside a PHPUnit test method,
     *   this will often look like `new Chain($this)`.
     */
    public function __construct(TestCase $case)
    {
        $this->testCase = $case;
    }

    /**
     * Add a class and method to the chain.
     *
     * @param string $objectClass
     *   Qualified class name of the object to be mocked.
     * @param string|null $method
     *   Method name.
     * @param string|Sequence|Options|\Exception $return
     *   Return value or object for further processing.
     * @param string $storeId
     *   Identifier for a particular return value that can be retrieved later.
     * @return Chain
     *   Updated chain object.
     */
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

    /**
     * Add a method to the chain, using the class passed with the last add() call.
     *
     * @param string|null $method
     *   Method name.
     * @param string|Sequence|Options $return
     *   Return value or object for further processing.
     * @param string $storeId
     *   Identifier for a particular return value that can be retrieved later.
     * @return Chain
     *   Updated chain object.
     */
    public function addd($method, $return = null, $storeId = null)
    {
        if (!isset($this->lastClass)) {
            throw new \Exception("You should use the add method before using addd.");
        }

        return $this->add($this->lastClass, $method, $return, $storeId);
    }

    /**
     * Build a usable mock object from the chain.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public function getMock()
    {
        return $this->build($this->root);
    }

    /**
     * Retrieve stored input by store id.
     *
     * @param string $id
     *   Arbitrary store identifier.
     *
     * @return array
     *   Input array.
     *
     * @todo Better docs for this method!
     */
    public function getStoredInput($id): array
    {
        return $this->store[$id] ?? [];
    }

    /**
     * Add the methods to a specific mock object.
     *
     * @param string $objectClass
     *   Qualified class name.
     * @return \PHPUnit\Framework\MockObject\MockObject
     *   A built PHPUnit mock object.
     */
    private function build($objectClass)
    {
        $methods = $this->getMethods($objectClass);

        $builder = $this->getBuilder($objectClass);

        if (!empty($methods)) {
            $builder->onlyMethods($methods);
        }
        $mock = $builder->getMockForAbstractClass();

        foreach ($methods as $method) {
            $mock->method($method)->willReturnCallback(fn() => $this->buildReturn(
                $objectClass,
                $mock,
                $method,
                func_get_args()
            ));
        }

        return $mock;
    }

    /**
     * Get a PHPUnit MockBuilder for the class.
     * @param string $class
     *   Qualified class name.
     *
     * @return \PHPUnit\Framework\MockObject\MockBuilder
     *   MockBuilder object.
     */
    private function getBuilder($class)
    {
        $builder = $this->testCase->getMockBuilder($class);
        $builder->disableOriginalConstructor();
        return $builder;
    }

    /**
     * Built the return value for a mocked method.
     *
     * @param string $objectClass
     *   Qualified class name.
     * @param string $method
     *   Method name.
     * @param array $inputs
     *   Array of input arguments.
     * @param ReturnNull|Sequence|Options|\Exception|string $return
     *   The return value defined in the chain.
     *
     * @return mixed
     *   The correct mocked return value.
     */
    private function buildReturn(string $objectClass, $mock, string $method, array $inputs, $return = null)
    {
        $return ??= $this->getReturn($objectClass, $method);
        $storeId = $this->getStoreId($objectClass, $method);
        if ($storeId) {
            $this->store[$storeId] = $inputs;
        }

        if ($return instanceof ReturnNull) {
            $return = null;
        } elseif ($return instanceof Sequence) {
            $return = $this->buildReturn($objectClass, $mock, $method, $inputs, $return->return());
        } elseif ($return instanceof Options) {
            $actualReturn = $this->getReturnFromOptions($inputs, $return);
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

    /**
     * Extract a return value from an Options object.
     * @param array $myInputs
     *   Method inputs.
     * @param Options $return
     *   A mock chain options object.
     * @return mixed
     *   The correct mocked return for the method.
     */
    private function getReturnFromOptions($myInputs, $return)
    {
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

        return $actualReturn;
    }

    /**
     * Get the methods to be mocked for a given class.
     *
     * @param string $objectClass
     *   Qualified class name.
     *
     * @return array
     *   Methods to be mocked.
     */
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

    /**
     * Get the return defined by the chain for the given object and method.
     *
     * @param string $objectClass
     *   Qualified class name.
     * @param mixed $method
     *   Method name.
     *
     * @return string|Sequence|Options|\Exception|null
     *   The return as defined.
     */
    private function getReturn($objectClass, $method)
    {
        return $this->definitions[$objectClass][$method] ?? null;
    }

    /**
     * Find the storeId for a given mock class and method.
     *
     * @param string $objectClass
     *   Qualified class name.
     * @param mixed $method
     *   Method name.
     *
     * @return string|null
     *   Store ID.
     *
     * @todo Better docs for this method.
     */
    private function getStoreId($objectClass, $method)
    {
        return $this->storeIds[$objectClass][$method] ?? null;
    }
}
