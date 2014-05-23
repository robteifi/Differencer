<?php

namespace RobTeifi\Differencer\Visitors;

use RobTeifi\Differencer\ArrayComparisonResult;
use RobTeifi\Differencer\ComparisonResult;
use RobTeifi\Differencer\CompoundResult;
use RobTeifi\Differencer\KeyValueComparisonResult;
use RobTeifi\Differencer\LeftValueMissingResult;
use RobTeifi\Differencer\MismatchedTypesResult;
use RobTeifi\Differencer\RightValueMissingResult;
use RobTeifi\Differencer\ScalarComparisonResult;
use RobTeifi\Differencer\StringComparisonResult;

class MeasuringVisitor implements Visitor
{
    const MAP_TO = " => ";
    const INDENT = 3;
    /** @var  int */
    private $leftWidth ;
    /** @var  int */
    private $rightWidth ;

    /** @var  int */
    private $indentWidth ;

    /** @var  boolean */
    private $matched ;

    /**
     * @return int
     */
    public function getIndentWidth()
    {
        return $this->indentWidth;
    }

    public function __construct()
    {
        $this->reset();
    }

    public function defaultVisit(ComparisonResult $result)
    {
        // TODO: Implement defaultVisit() method.
    }

    public function visitStringComparisonResult(StringComparisonResult $result)
    {
        $this->checkMatched($result);
        $this->updateLeftWidth(strlen($result->getLeft()));
        $this->updateRightWidth(strlen($result->getRight()));
    }

    public function visitScalarComparisonResult(ScalarComparisonResult $result)
    {
        $this->checkMatched($result);
        $this->updateLeftWidth(strlen((string) $result->getLeft()));
        $this->updateRightWidth(strlen((string) $result->getRight()));
    }

    public function visitCompoundResult(CompoundResult $compound)
    {
        $results = $compound->getResults() ;
        foreach ($results as $result) {
            $result->accept($this);
            $this->checkMatched($result);
        }
    }

    public function visitKeyValueComparisonResult(KeyValueComparisonResult $result)
    {
        $this->checkMatched($result);
        $key = $result->getKey();
        $this->updateIndentWidth(strlen($key) + strlen(self::MAP_TO));
        $inner = $result->getResult();
        $inner->accept($this);
    }

    public function visitRightValueMissingResult(RightValueMissingResult $result)
    {
        $this->updateLeftWidth($this->computeWidth($result->getLeft()));
        $this->updateRightWidth(strlen(ComparisonResult::NOT_PRESENT));
    }

    public function visitLeftValueMissingResult(LeftValueMissingResult $result)
    {
        $this->updateLeftWidth(strlen(ComparisonResult::NOT_PRESENT));
        $this->updateRightWidth($this->computeWidth($result->getRight()));
    }

    public function visitArrayComparisonResult(ArrayComparisonResult $aResult)
    {
        $results = $aResult->getResults() ;
        foreach ($results as $result) {
            $visitor = new MeasuringVisitor();
            $visitor->updateFrom($this);

            $result->accept($this);
            if (!$visitor->hasMatched()) {
                $this->matched = false;
            }
            $this->updateFrom($visitor);
            $this->checkMatched($result);
        }
    }

    public function visitMismatchedTypesResult(MismatchedTypesResult $result)
    {
        $this->updateLeftWidth(strlen(ComparisonResult::typeAndValue($result->getLeft())));
        $this->updateRightWidth(strlen(ComparisonResult::typeAndValue($result->getRight())));
    }

    /**
     * @return int
     */
    public function getLeftWidth()
    {
        return $this->leftWidth;
    }

    /**
     * @return int
     */
    public function getRightWidth()
    {
        return $this->rightWidth;
    }

    /**
     * @param mixed $value
     *
     * @return int
     */
    public function computeWidth($value)
    {
        if (is_object($value) && ($value instanceof ComparisonResult)) {
            $visitor = new MeasuringVisitor();
            $visitor->updateFrom($this);
            $value->accept($visitor);
            if (!$visitor->hasMatched()) {
                $this->matched = false ;
            }
            $this->updateFrom($visitor);

            return $visitor->getLeftWidth() + self::INDENT;
        } elseif (is_array($value)) {
            $asTextArray = explode("\n", print_r($value, true));
            $width = 0 ;
            foreach ($asTextArray as $line) {
                $width = max($width, strlen($line));
            }
            return $width;
        }

        return strlen($value);
    }

    /**
     * @return boolean
     */
    public function hasMatched()
    {
        return $this->matched;
    }

    /**
     * @param int $value
     */
    public function updateLeftWidth($value)
    {
        $this->leftWidth = max($this->leftWidth, $value);
    }

    /**
     * @param int $value
     */
    public function updateRightWidth($value)
    {
        $this->rightWidth = max($this->rightWidth, $value);
    }

    public function updateIndentWidth($value)
    {
        $this->indentWidth = max($this->indentWidth, $value);
    }

    public function reset()
    {
        $this->leftWidth = 0;
        $this->rightWidth = 0 ;
        $this->indentWidth = 0 ;
    }

    /**
     * @param \RobTeifi\Differencer\ComparisonResult $result
     */
    public function checkMatched(ComparisonResult $result)
    {
        if (!$result->getMatched()) {
            $this->matched = false;
        }
    }

    protected function updateFrom(MeasuringVisitor $other)
    {
        $this->updateLeftWidth($other->getLeftWidth());
        $this->updateRightWidth($other->getRightWidth());
        $this->updateIndentWidth($other->getIndentWidth());
    }
}
