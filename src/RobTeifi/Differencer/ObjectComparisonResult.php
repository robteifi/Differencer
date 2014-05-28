<?php

namespace RobTeifi\Differencer;

class ObjectComparisonResult extends CompoundResult
{
    /** @var string  */
    private $className;

    /**
     * @param int $depth
     * @param object $object
     */
    public function __construct($depth, $object)
    {
        parent::__construct(true, $depth, null, null);
        $this->className = get_class($object);
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    public function addResult(ComparisonResult $result)
    {
        if (!$result->getMatched()) {
            $this->matched = false ;
        }

        parent::addResult($result);
    }
}
