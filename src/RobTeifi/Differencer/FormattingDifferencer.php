<?php

namespace RobTeifi\Differencer;

use RobTeifi\Differencer\Visitors\FormattingVisitor;

class FormattingDifferencer
{
    /** @var CompoundResult */
    private $result;

    /** @var  Differencer */
    private $differencer;
    /** @var  \RobTeifi\Differencer\Visitors\FormattingVisitor */
    private $visitor;
    /** @var boolean */
    private $expected;

    public function __construct($title, $value1, $value2, $expected, $precision = 0.0001)
    {
        $result = $this->compare($value1, $value2, $precision);
        echo "\n\n$title ";
        echo ": ". ($result->getMatched() ? 'matched' : 'failed') ;
        if ($result->getMatched() !== $expected) {
            echo " ******* UNEXPECTED RESULT ******\n";
        } else {
            echo "\n";
        }

        $this->visitor = new FormattingVisitor($result, 'Original', 'Comparison');
        $result->accept($this->visitor);
        echo $this->visitor->getOutput();
        $this->expected = $expected;
    }

    public static function tryCompare($value1, $value2, $precision)
    {
        $differencer = new Differencer();
        $differencer->setFloatPrecision($precision);
        $differencer->compare($value1, $value2);
        return $differencer->getResult();
    }

    public static function explain(CompoundResult $result)
    {
        $visitor = new FormattingVisitor($result, 'Original', 'Comparison');
        $result->accept($visitor);
        echo $visitor->getOutput();
    }

    /**
     * @param $value1
     * @param $value2
     * @param float $precision
     * @return CompoundResult
     */
    private function compare($value1, $value2, $precision)
    {
        $this->differencer = new Differencer();
        $this->differencer->setFloatPrecision($precision);
        $this->differencer->compare($value1, $value2);
        $this->result = $this->differencer->getResult();
        return $this->result;
    }

    public function matched()
    {
        return $this->result->getMatched();
    }
}
