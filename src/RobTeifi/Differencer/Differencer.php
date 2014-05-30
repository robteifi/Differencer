<?php

namespace RobTeifi\Differencer;

class Differencer
{
    private $floatPrecision = 0.0001;

    /** @var  CompoundResult */
    private $result;

    /** @var  bool */
    private $matched;

    /**
     * @param  mixed $expected
     * @param  mixed $actual
     * @return bool
     */
    public function compare($expected, $actual)
    {
        $this->result = new CompoundResult(0);
        $result = $this->internalCompare(0, $expected, $actual);
        $this->addResult($result);
        if (!$result->getMatched()) {
            $this->matched = false;
        }

        return $this->matched;
    }

    /**
     * @param  int $depth
     * @param  mixed $expected
     * @param  mixed $actual
     * @return ComparisonResult
     */
    private function internalCompare($depth, $expected, $actual)
    {
        if (is_null($expected) && is_null($actual)) {
            return new NullComparisonResult(true, $depth);
        }
        if (gettype($expected) === gettype($actual)) {
            if (is_scalar($expected)) {
                return $this->scalarCompare($depth, $expected, $actual);
            }
            if (is_array($expected)) {
                return $this->arrayCompare($depth, $expected, $actual);
            }
            if (is_object($expected)) {
                if (get_class($expected) === get_class($actual)) {
                    // same class - what about a class that descends from another?
                    return $this->objectCompare($depth, (object)$expected, (object)$actual);
                }

                return new MismatchedClassResult($depth, (object)$expected, (object)$actual);
            }
        }

        return new MismatchedTypesResult(false, $depth, $expected, $actual);
    }

    private function scalarCompare($depth, $expected, $actual)
    {
        if (is_string($expected)) {
            return $this->stringCompare($depth, $expected, $actual);
        }
        // either both scalars or mismatch in types
        if (is_numeric($expected)) {
            return new ScalarComparisonResult(
                $this->scalarsEqual($expected, $actual),
                $depth,
                $expected,
                $actual,
                $this->findMismatchPosition((string)$expected, (string)$actual)
            );
        }
        if (is_bool($expected)) {
            return new ScalarComparisonResult(
                $this->scalarsEqual($expected, $actual),
                $depth,
                $expected ? 'true' : 'false',
                $actual ? 'true' : 'false',
                0
            );
        }
        return null ;
    }

    /**
     * @param  int $depth
     * @param  string $expected
     * @param  string $actual
     * @return ComparisonResult
     */
    private function stringCompare($depth, $expected, $actual)
    {
        $pos = strcmp($expected, $actual);
        $matched = $pos === 0;
        return new StringComparisonResult(
            $matched,
            $depth,
            $expected,
            $actual,
            $matched ? -1 : $this->findMismatchPosition($expected, $actual)
        );
    }

    /**
     * @param  int $depth
     * @param  array $expected
     * @param  array $actual
     * @return array
     */
    private function arrayCompare($depth, array $expected, array $actual)
    {
        $keys1 = array_keys($expected);
        $keys2 = array_keys($actual);
        $keyDiffs = array_merge(array_diff($keys1, $keys2), array_diff($keys2, $keys1));

        $result = new ArrayComparisonResult($depth);
        if (count($keyDiffs) > 0) {
            // key mismatch
            $allKeys = $this->getAllKeys($keys1, $keys2);
            foreach ($allKeys as $key) {
                $this->keyValueCompare($depth, $expected, $actual, $key, $result);
            }
        } else {
            // same keys
            foreach ($expected as $key => $value) {
                $this->compareValues($result, $key, $depth, $value, $actual[$key]);
            }
        }

        return $result;
    }


    /**
     * @param $depth
     * @param array $expected
     * @param array $actual
     * @param $key
     * @param $result
     */
    private function keyValueCompare($depth, array $expected, array $actual, $key, ArrayComparisonResult $result)
    {
        $keyIn1 = array_key_exists($key, $expected);
        $keyIn2 = array_key_exists($key, $actual);

        if ($keyIn1 && !$keyIn2) {
            $result->addResult(
                new KeyValueComparisonResult(
                    $depth + 1,
                    $key,
                    new RightValueMissingResult($depth + 1, $expected[$key], $key)
                )
            );
        } elseif (!$keyIn1 && $keyIn2) {
            $result->addResult(
                new KeyValueComparisonResult(
                    $depth + 1,
                    $key,
                    new LeftValueMissingResult($depth + 1, $actual[$key], $key)
                )
            );
        } else {
            // must be in both
            $this->compareValues($result, $key, $depth, $expected[$key], $actual[$key]);
        }
    }

    /**
     * @param ArrayComparisonResult $result
     * @param $key
     * @param $depth
     * @param $leftVal
     * @param $rightVal
     */
    private function compareValues(ArrayComparisonResult $result, $key, $depth, $leftVal, $rightVal)
    {
        if (is_array($leftVal) && is_array($rightVal)) {
            $subDiff = new Differencer();
            $subDiff->compare($leftVal, $rightVal);
            $result->addResult(new KeyValueComparisonResult($depth + 1, $key, $subDiff->getResult()));
        } else {
            $theMatch = $this->internalCompare(0, $leftVal, $rightVal);
            $result->addResult(new KeyValueComparisonResult($depth + 1, $key, $theMatch));
        }
    }

    /**
     * @param  int $depth
     * @param  object $argument1
     * @param  object $argument2
     * @return array
     */
    private function objectCompare($depth, $expected, $actual)
    {
        $result = new ObjectComparisonResult($depth, $expected);

        $reflector1 = new \ReflectionClass($expected);
        $reflector2 = new \ReflectionClass($actual);

        $props1 = $reflector1->getProperties();

        foreach ($props1 as $prop) {
            $prop->setAccessible(true);
            $key = $prop->getName();
            $value = $prop->getValue($expected);
            $other = $reflector2->getProperty($key);
            $other->setAccessible(true);
            $comparison = new PropertyValueComparisonResult(
                $depth + 1,
                $key,
                $this->internalCompare($depth + 1, $value, $other->getValue($actual))
            );
            $result->addResult($comparison);
        }
        return $result;
    }

    /**
     * @param ComparisonResult $result
     */
    private function addResult(ComparisonResult $result)
    {
        $this->result->addResult($result);
    }

    /**
     * @return CompoundResult
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param $expected
     * @param $actual
     * @return int
     */
    private function findMismatchPosition($expected, $actual)
    {
        $minIndex = min(strlen($expected), strlen($actual));
        $idx = 0;
        while ($idx < $minIndex && $expected[$idx] === $actual[$idx]) {
            $idx++;
        }

        return $idx;
    }

    /**
     * @param $keys1
     * @param $keys2
     * @return array
     */
    private function getAllKeys(array $keys1, array $keys2)
    {
        $keys = [];
        foreach ($keys1 as $key) {
            $keys[$key] = true;
        }
        foreach ($keys2 as $key) {
            $keys[$key] = true;
        }

        return array_keys($keys);
    }

    /**
     * @param $argument1
     * @param $argument2
     * @return bool
     */
    private function scalarsEqual($argument1, $argument2)
    {
        if (is_float($argument1) && is_float($argument2)) {
            return (abs($argument1 - $argument2) < $this->floatPrecision);
        }

        return ($argument1 == $argument2);
    }

    /**
     * @param float $floatPrecision
     */
    public function setFloatPrecision($floatPrecision)
    {
        $this->floatPrecision = $floatPrecision;
    }
}
