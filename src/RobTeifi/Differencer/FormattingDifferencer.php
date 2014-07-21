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
        if (is_array($value2) && $this->isNonAssocArray($value2)) {
            echo "\nTo quick fix the test use this table in your feature\n";
            echo $this->formatArray($value2);
        }
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

    private function formatArray(array $value)
    {
        $result = '';
        if (count($value) > 0) {
            if (is_array($value[0])) {
                $result .= $this->formatRow(array_keys($value[0]));
                foreach ($value as $row) {
                    if (!is_array($row)) {
                        return '';
                    }
                    $result .= $this->formatRow(array_values($row));
                }
            }
        }
        return $result;
    }

    private function formatRow($row)
    {
        if (!is_array($row)) {
            return '';
        }

        $result = '| ';
        foreach ($row as $value) {
            $result .= $value . ' | ';
        }
        return $result . "\n";
    }

    private function isNonAssocArray(array $value)
    {
        $keys = array_keys($value) ;
        $last = -1 ;
        foreach ($keys as $key) {
            if (!is_numeric($key)) {
                return false ;
            }
            if ($key != $last + 1) {
                return false ;
            }
            $last = $key ;
        }
        return true ;
    }
}
