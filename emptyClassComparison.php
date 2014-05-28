<?php
use RobTeifi\Differencer\FormattingDifferencer;

class EmptyClass {

}

new FormattingDifferencer("Empty classes that should match", new EmptyClass(), new EmptyClass(), true);
