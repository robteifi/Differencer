<?php
use RobTeifi\Differencer\FormattingDifferencer;

class PrivateClass
{
    private $value ;

    public function __construct($value)
    {
        $this->value = $value;
    }


}

new FormattingDifferencer(
    "Class with identical values",
    new PrivateClass('abc'),
    new PrivateClass('abc'),
    true
);
new FormattingDifferencer(
    "Class with different values",
    new PrivateClass('abc'),
    new PrivateClass('def'),
    false
);