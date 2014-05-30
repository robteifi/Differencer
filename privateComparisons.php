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

new FormattingDifferencer(
    "Class with embedded class identical values",
    new PrivateClass(new PrivateClass('abc')),
    new PrivateClass(new PrivateClass('abc')),
    true
);
new FormattingDifferencer(
    "Class with embedded class different values",
    new PrivateClass(new PrivateClass('abc')),
    new PrivateClass(new PrivateClass('def')),
    false
);

new FormattingDifferencer(
    "Array with embedded class identical values",
    ['a' => new PrivateClass('abc')],
    ['a' => new PrivateClass('abc')],
    true
);
new FormattingDifferencer(
    "Array with embedded class different values",
    ['a' => new PrivateClass('abc')],
    ['a' => new PrivateClass('def')],
    false
);

