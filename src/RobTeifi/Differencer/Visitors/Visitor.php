<?php

namespace RobTeifi\Differencer\Visitors;

use RobTeifi\Differencer\ComparisonResult;

interface Visitor
{
    public function defaultVisit(ComparisonResult $result);


}
