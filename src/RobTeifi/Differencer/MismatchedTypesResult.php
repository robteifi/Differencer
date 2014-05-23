<?php

namespace RobTeifi\Differencer;

class MismatchedTypesResult extends ComparisonResult
{
    public static function getMarker()
    {
        return 'TYP';
    }
}
