<?php

namespace RobTeifi\Differencer;

class ScalarComparisonResult extends ComparisonResult
{

    /** @var  int */
    protected $position;

    public function __construct($matched, $depth, $left, $right, $position)
    {
        parent::__construct($matched, $depth, $left, $right);
        $this->position = $position;
    }

    public function getPos()
    {
        return $this->position;
    }

    public static function getMarker()
    {
        return 'SCA';
    }

    /**
     * @param $value
     * @return string
     */
    protected function makeMarker($value)
    {
        return "| " .
            $this->makeIndent($this->position) .
            "^" .
            $this->pad(strlen((string) $value) - $this->position - 1) .
            " ";
    }
}
