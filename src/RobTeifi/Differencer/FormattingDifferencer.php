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
        $this->showBehatTableQuickFix($value2);
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
        $widths = $this->measureArray($value);
        $result = $this->formatRow(array_keys($value[0]), $widths);
        foreach ($value as $row) {
            if (!is_array($row)) {
                return '';
            }
            $result .= $this->formatRow(array_values($row), $widths);
        }
        return $result;
    }

    private function measureArray(array $value)
    {
        $header = $this->measureRow(array_keys($value[0]));
        $result = $header;
        foreach ($value as $row) {
            if (!is_array($row)) {
                return '';
            }
            $result = $this->mergeMeasurements($this->measureRow(array_values($row)), $header);
        }
        return $result;
    }

    private function measureRow(array $row)
    {
        $result = [];
        foreach ($row as $value) {
            $result[] = strlen((string) $value);
        }
        return $result ;
    }

    private function formatRow($row, array $widths)
    {
        if (!is_array($row)) {
            return '';
        }

        $result = '| ';
        $idx = 0 ;
        foreach ($row as $value) {
            $width = $widths[$idx++];
            $result .= $value . str_repeat(' ', $width - strlen((string) $value)) . '| ';
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

    /**
     * @param $value2
     */
    private function showBehatTableQuickFix($value2)
    {
        if (is_array($value2) && $this->isNonAssocArray($value2) && $this->isBehatFormat($value2)) {
            echo "\nTo quick fix the test use this table in your feature\n";
            echo $this->formatArray($value2);
        }
    }

    /**
     * @param array $value
     * @return bool
     */
    private function isBehatFormat(array $value)
    {
        return count($value) > 0 && is_array($value[0]);
    }


    private function mergeMeasurements($row, $header)
    {
        $result = [];
        foreach ($header as $key => $value) {
            $result[] = max($row[$key], $value);
        }
        return $result;
    }
}
