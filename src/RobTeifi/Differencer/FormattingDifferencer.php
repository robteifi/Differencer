<?php

namespace RobTeifi\Differencer;

use RobTeifi\Differencer\Visitors\FormattingVisitor;

class FormattingDifferencer
{

    /** @var  Differencer */
    private $differencer;
    /** @var  \RobTeifi\Differencer\Visitors\FormattingVisitor */
    private $visitor;
    private $expected;

    public function __construct($title, $value1, $value2, $expected, $precision = 0.0001)
    {
        $this->differencer = new Differencer();
        $this->differencer->setFloatPrecision($precision);
        $this->differencer->compare($value1, $value2);
        echo "\n\n$title ";
        $result = $this->differencer->getResult();
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
}
