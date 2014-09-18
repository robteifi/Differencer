<?php

namespace RobTeifi\Differencer;

use RobTeifi\Differencer\Visitors\Visitor;

abstract class ComparisonResult
{
    const INDENT = 3 ;
    const NOT_PRESENT = '<<< KEY MISSING >>>';
    /** @var  mixed */
    protected $left ;
    /** @var  mixed */
    protected $right ;
    /** @var  int */
    private $depth ;
    /** @var  boolean */
    protected $matched ;

    public function __construct($matched, $depth, $left, $right)
    {
        $this->depth = $depth;
        $this->left = $left;
        $this->matched = $matched;
        $this->right = $right;
    }

    /**
     * @param  mixed  $value
     * @return string
     */
    public static function typeAndValue($value)
    {
        if (is_array($value)) {
            $value = print_r($value, true);
        }
        return ((string) $value) . ':(' . gettype($value) . ')';
    }

    /**
     * @return int
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * @return mixed
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @return boolean
     */
    public function getMatched()
    {
        return $this->matched;
    }

    /**
     * @return mixed
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     * @param  int    $extra
     * @internal param int $depth
     * @return string
     */
    protected function makeIndent($extra)
    {
        return $this->pad($extra + $this->getDepth() * self::INDENT);
    }

    /**
     * @param $repetitions
     * @return string
     */
    protected function pad($repetitions)
    {
        return str_repeat(' ', $repetitions);
    }

    public static function getMarker()
    {
        return '>>>';
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->{$this->getVisitMethodName($visitor, get_class($this))}($this);
    }

    /**
     * @param $className
     * @return string
     */
    private function makeVisitMethodName($className)
    {
        return 'visit' . join('', array_slice(explode('\\', $className), -1));
    }

    /**
     * @param Visitor $visitor
     * @param $className
     * @return string
     */
    private function getVisitMethodName(Visitor $visitor, $className)
    {
        $visitMethods = get_class_methods($visitor);
        while ($className) {
            $methodName = $this->makeVisitMethodName($className);
            if (in_array($methodName, $visitMethods)) {
                return $methodName;
            }
            $className = get_parent_class($className);
        }
        return 'defaultVisit';
    }
}
