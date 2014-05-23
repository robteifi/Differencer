<?php

namespace RobTeifi\Differencer;

class StringComparisonResult extends ScalarComparisonResult
{

    public function __toString()
    {
        $leftMarker = $this->makeMarker($this->getLeft());
        $rightMarker = $this->makeMarker($this->getRight());

        return "| {$this->left} | {$this->getRight()} |\n"
            . $leftMarker . $rightMarker . "|\n";
    }

    public static function getMarker()
    {
        return 'STR';
    }
}
