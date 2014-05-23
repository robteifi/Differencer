<?php

namespace RobTeifi\Differencer;

class KeyValueComparisonResult extends ComparisonResult
{
    /** @var  string */
    private $key;

    /** @var  ComparisonResult */
    private $result ;

    public function __construct($depth, $key, ComparisonResult $result)
    {
        parent::__construct($result->getMatched(), $depth, null, null);
        $this->key = $key;
        $this->result = $result;
    }

    public static function getMarker()
    {
        return 'VAL';
    }

    /**
     * @return ComparisonResult
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }
}
