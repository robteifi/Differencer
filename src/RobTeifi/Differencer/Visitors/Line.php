<?php

namespace RobTeifi\Differencer\Visitors;

final class Line
{
    /** @var  boolean */
    private $matched ;

    /** @var  string */
    private $text ;

    /** @var int  */
    private $extraIndent;

    /** @var string  */
    private $marker = '>>>';

    public function __construct($matched, $text, $marker)
    {
        $this->matched = $matched;
        $this->text = $text;
        $this->extraIndent = 0 ;
        $this->marker = $marker;
    }

    public function setMarker($marker)
    {
        $this->marker = $marker ;
    }

    /**
     * @return string
     */
    public function getMarker()
    {
        return $this->marker;
    }

    /**
     * @return boolean
     */
    public function hasMatched()
    {
        return $this->matched;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return str_repeat(' ', $this->extraIndent) . $this->text;
    }

    public function setExtraIndent($indent)
    {
        $this->extraIndent += $indent ;
    }
}
