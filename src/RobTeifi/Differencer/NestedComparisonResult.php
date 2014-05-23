<?php

namespace RobTeifi\Differencer;

class NestedComparisonResult extends ComparisonResult
{

    /** @var  $key */
    private $key ;
    /**
     * @param int              $depth
     * @param string           $key
     * @param ComparisonResult $result
     */
    public function __construct($depth, $key, ComparisonResult $result)
    {
        parent::__construct(
            $result->getMatched(),
            $depth,
            $this->addKey($key, $result->getLeft()),
            $this->addKey($key, $result->getRight())
        );
        $this->key = $key ;
    }

    public function addKey($key, $result)
    {
        return $key . ' => ' . $result ;
    }
}
