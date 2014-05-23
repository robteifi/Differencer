<?php

namespace RobTeifi\Differencer;

class CompoundResult extends ComparisonResult
{
    /** @var  ComparisonResult[] */
    private $results;

    public function __construct($depth)
    {
        parent::__construct(true, $depth, null, null);
        $this->results = [];
    }

    public function addResult(ComparisonResult $result)
    {
        $this->results[] = $result ;
        if (!$result->getMatched()) {
            $this->matched = false ;
        }
    }

    /**
     * @return ComparisonResult[]
     */
    public function getResults()
    {
        return $this->results ;
    }
}
