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
     * @param  mixed $argument1
     * @param  mixed $argument2
     * @return ComparisonResult
     */
    private function internalCompare($depth, $argument1, $argument2)
    {
        if (is_null($argument1) && is_null($argument2)) {
            return new NullComparisonResult(true, $depth);
        }
        if (gettype($argument1) === gettype($argument2)) {
            if (is_string($argument1) && is_string($argument2)) {
                return $this->stringCompare($depth, $argument1, $argument2);
            }
            if (is_array($argument1) && is_array($argument2)) {
                return $this->arrayCompare($depth, $argument1, $argument2);
            }
            if (is_object($argument1) && is_object($argument2)) {
                if (get_class($argument1) === get_class($argument2)) {
                    // same class
                    return $this->objectCompare($depth, (object)$argument1, (object)$argument2);
                }

                return new MismatchedClassResult($depth, (object)$argument1, (object)$argument2);
            }
            // either both scalars or mismatch in types
            if (is_numeric($argument1) && is_numeric($argument2)) {
                return new ScalarComparisonResult(
                    $this->scalarsEqual($argument1, $argument2),
                    $depth,
                    $argument1,
                    $argument2,
                    $this->findMismatchPosition((string)$argument1, (string)$argument2)
                );
            }
            if (is_bool($argument1) && is_bool($argument2)) {
                return new ScalarComparisonResult(
                    $this->scalarsEqual($argument1, $argument2),
                    $depth,
                    $argument1 ? 'true' : 'false',
                    $argument2 ? 'true' : 'false',
                    0
                );
            }
        }

        return new MismatchedTypesResult(false, $depth, $argument1, $argument2);
    }

    /**
     * @param  int $depth
     * @param  string $argument1
     * @param  string $argument2
     * @return ComparisonResult
     */
    private function stringCompare($depth, $argument1, $argument2)
    {
        $pos = strcmp($argument1, $argument2);
        if ($pos === 0) {
            // they match
            return new StringComparisonResult(true, $depth, $argument1, $argument2, -1);
        }

        return new StringComparisonResult(false, $depth, $argument1, $argument2, $this->findMismatchPosition(
            $argument1,
            $argument2
        ));
    }

    /**
     * @param  int $depth
     * @param  array $argument1
     * @param  array $argument2
     * @return array
     */
    private function arrayCompare($depth, array $argument1, array $argument2)
    {
        $keys1 = array_keys($argument1);
        $keys2 = array_keys($argument2);
        $keyDiffs = array_merge(array_diff($keys1, $keys2), array_diff($keys2, $keys1));

        $result = new ArrayComparisonResult($depth);
        if (count($keyDiffs) > 0) {
            // key mismatch
            $allKeys = $this->getAllKeys($keys1, $keys2);
            foreach ($allKeys as $key) {
                $keyIn1 = array_key_exists($key, $argument1);
                $keyIn2 = array_key_exists($key, $argument2);

                if ($keyIn1 && !$keyIn2) {
                    $result->addResult(
                        new KeyValueComparisonResult(
                            $depth + 1,
                            $key,
                            new RightValueMissingResult($depth + 1, $argument1[$key], $key)
                        )
                    );
                } elseif (!$keyIn1 && $keyIn2) {
                    $result->addResult(
                        new KeyValueComparisonResult(
                            $depth + 1,
                            $key,
                            new LeftValueMissingResult($depth + 1, $argument2[$key], $key)
                        )
                    );
                } else {
                    // must be in both
                    $this->compareValues($result, $key, $depth, $argument1[$key], $argument2[$key]);
                }
            }
        } else {
            // same keys
            foreach ($argument1 as $key => $value) {
                $other = $argument2[$key];
                $this->compareValues($result, $key, $depth, $value, $other);
            }
        }

        return $result;
    }

    private function objectKeys($object)
    {
        $keys = [];
        foreach ($object as $key => $val) {
            $keys[] = $key;
        }
        sort($keys);
        return $keys;
    }

    /**
     * @param  int $depth
     * @param  object $argument1
     * @param  object $argument2
     * @return array
     */
    private function objectCompare($depth, $obj1, $obj2)
    {
        $result = new ObjectComparisonResult($depth, $obj1);

        $refl1 = new \ReflectionClass($obj1);
        $refl2 = new \ReflectionClass($obj2);

        $props1 = $refl1->getProperties();

//        $keys1 = $this->objectKeys($obj1);
//        $keys2 = $this->objectKeys($obj2);
//        $keyDiffs = array_merge(array_diff($keys1, $keys2), array_diff($keys2, $keys1));

//        if (count($keyDiffs) > 0) {
//            // key mismatch
//            $allKeys = $this->getAllKeys($keys1, $keys2);
//            foreach ($allKeys as $key) {
//
//                $keyIn1 = in_array($key, $keys1);
//                $keyIn2 = in_array($key, $keys2);
//
//                if ($keyIn1 && !$keyIn2) {
//                    $result->addResult(
//                        new KeyValueComparisonResult(
//                            $depth + 1,
//                            $key,
//                            new RightValueMissingResult($depth + 1, $obj1->$key, $key)
//                        )
//                    );
//                } elseif (!$keyIn1 && $keyIn2) {
//                    $result->addResult(
//                        new KeyValueComparisonResult(
//                            $depth + 1,
//                            $key,
//                            new LeftValueMissingResult($depth + 1, $obj2->$key, $key)
//                        )
//                    );
//                } else {
//                    // must be in both
//                    $this->compareValues($result, $key, $depth, $obj1->$key, $obj2->$key);
//                }
//            }
//        } else {
        foreach ($props1 as $prop) {
            $prop->setAccessible(true);
            $key = $prop->getName();
            $value = $prop->getValue($obj1);
            $other = $refl2->getProperty($key);
            $other->setAccessible(true);
            $comparison = new PropertyValueComparisonResult(
                $depth+1,
                $key,
                $this->internalCompare($depth + 1, $value, $other->getValue($obj2))
            );
            $result->addResult($comparison);
        }
//        }
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
     * @param $argument1
     * @param $argument2
     * @return int
     */
    private function findMismatchPosition($argument1, $argument2)
    {
        $minIndex = min(strlen($argument1), strlen($argument2));
        $idx = 0;
        while ($idx < $minIndex && $argument1[$idx] === $argument2[$idx]) {
            $idx++;
        }

        return $idx;
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
