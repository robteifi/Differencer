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

    public function accept(Visitor $visitor)
    {
        $elementClass = join('', array_slice(explode('\\', get_class($this)), -1));

        $visitMethods = get_class_methods($visitor);

        foreach ($visitMethods as $method) {

            // we've found the visitation method for this class type
            if ('visit' . $elementClass == $method) {

                // visit the method and exit
                return $visitor->{'visit' . $elementClass}($this);
            }
        }

        // If no visitFoo, etc, call a default algorithm
        return $visitor->defaultVisit($this);
    }

    public static function getMarker()
    {
        return '>>>';
    }
}
