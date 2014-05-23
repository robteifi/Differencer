<?php

namespace RobTeifi\Differencer\Visitors;

class LineBuffer
{
    /** @var  Line[] */
    private $lines;

    public function __construct()
    {
        $this->lines = [];
    }

    /**
     * @param boolean $matched
     * @param string  $main
     * @param string $marker
     */
    public function add($matched, $main, $marker = '>>>')
    {
        if (strpos($main, "\n")) {
            $first = true ;
            foreach (explode("\n", $main) as $partial) {
                if ($first) {
                    $first = false ;
                    $this->add($matched, $partial, $marker);
                } else {
                    $this->add($matched, $partial, '   ');
                }
            }
            return ;
        }
        $line = new Line($matched, $main, $marker);
        $this->lines[] = $line;
    }

    /**
     * @param LineBuffer $buffer
     * @param int $indent
     * @param string $firstPrefix
     */
    public function addBuffer(LineBuffer $buffer, $indent = 0, $firstPrefix = '')
    {
        $first = true ;
        foreach ($buffer->getLines() as $line) {
            $newline = null ;
            if ($first) {
                $first = false ;
                $newline = new Line($line->hasMatched(), $firstPrefix . $line->getText(), $line->getMarker());
                $newline->setExtraIndent(max(0, $indent - strlen($firstPrefix)));
            } else {
                $newline = $line ;
                $newline->setExtraIndent($indent);
            }
            $this->lines[] = $newline ;
        }
    }

    /**
     * @return Line[]
     */
    public function getLines()
    {
        return $this->lines;
    }

    public function __toString()
    {
        return array_reduce(
            $this->getLines(),
            function ($out, Line $line) {
                return $out . ($line->hasMatched() ? '    ' : ($line->getMarker() . ' ')) . $line->getText() . "\n";
            },
            ''
        );
    }
}
