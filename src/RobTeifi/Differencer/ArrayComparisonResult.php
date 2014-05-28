<?php

namespace RobTeifi\Differencer;

class ArrayComparisonResult extends CompoundResult
{
    public function __construct($depth)
    {
        parent::__construct(true, $depth, null, null);
    }

    public function addResult(ComparisonResult $result)
    {
        if (!$result->getMatched()) {
            $this->matched = false ;
        }

        parent::addResult($result);
    }
}
