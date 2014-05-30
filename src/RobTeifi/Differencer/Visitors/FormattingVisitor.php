<?php

namespace RobTeifi\Differencer\Visitors;

use RobTeifi\Differencer\ArrayComparisonResult;
use RobTeifi\Differencer\ComparisonResult;
use RobTeifi\Differencer\CompoundResult;
use RobTeifi\Differencer\KeyValueComparisonResult;
use RobTeifi\Differencer\LeftValueMissingResult;
use RobTeifi\Differencer\MismatchedTypesResult;
use RobTeifi\Differencer\NullComparisonResult;
use RobTeifi\Differencer\ObjectComparisonResult;
use RobTeifi\Differencer\RightValueMissingResult;
use RobTeifi\Differencer\ScalarComparisonResult;
use RobTeifi\Differencer\StringComparisonResult;

class FormattingVisitor implements Visitor
{
    const INDENT = 3;
    const PAD_CHAR = ' ';
    const NOT_PRESENT = '***NOT PRESENT***';
    const MAP_TO = ' => ';
    /** @var  int */
    private $leftFieldWidth = 0;
    /** @var  int */
    private $rightFieldWidth = 0;
    /** @var  int */
    private $indentFieldWidth = 0;
    /** @var  LineBuffer */
    private $buffer;

    /**
     * @param \RobTeifi\Differencer\ComparisonResult $result
     * @param string $leftName
     * @param string $rightName
     */
    public function __construct(ComparisonResult $result, $leftName = null, $rightName = null)
    {
        $this->measureResult($result);

        $this->buffer = new LineBuffer();
        if (is_null($leftName) || is_null($rightName)) {
            return;
        }
    }

    /**
     * @return int
     */
    public function getLeftFieldWidth()
    {
        return $this->leftFieldWidth;
    }

    /**
     * @return mixed
     */
    public function getRightFieldWidth()
    {
        return $this->rightFieldWidth;
    }

    public function setWidthsFrom(FormattingVisitor $other)
    {
        $this->setLeftFieldWidth($other->getLeftFieldWidth());
        $this->setRightFieldWidth($other->getRightFieldWidth());
        $this->setIndentFieldWidth($other->getIndentFieldWidth());
    }

    /**
     * @param int $width
     */
    public function setIndentFieldWidth($width)
    {
        $this->indentFieldWidth = max($this->indentFieldWidth, $width);
    }

    /**
     * @return int
     */
    public function getIndentFieldWidth()
    {
        return $this->indentFieldWidth;
    }

    public function visitStringComparisonResult(StringComparisonResult $result)
    {
        $matched = $result->getMatched();
        $left = (string)$result->getLeft();
        $right = (string)$result->getRight();

        $buffer = new LineBuffer();
        $buffer->add($matched, $this->formatAsRow($left, $right), StringComparisonResult::getMarker());
        $marker = null;
        if (!$matched) {
            $marker = $this->makeMarker($result);
            $buffer->add($matched, $this->formatAsRow($marker, $marker), '   ');
        }
        $this->buffer->addBuffer($buffer);
    }

    public function visitScalarComparisonResult(ScalarComparisonResult $result)
    {
        $matched = $result->getMatched();
        $left = (string)$result->getLeft();
        $right = (string)$result->getRight();

        $buffer = new LineBuffer();
        $buffer->add($matched, $this->formatAsRow($left, $right), ScalarComparisonResult::getMarker());
        $marker = null;
        if (!$matched) {
            $marker = $this->makeMarker($result);
            $buffer->add($matched, $this->formatAsRow($marker, $marker), '   ');
        }
        $this->buffer->addBuffer($buffer);
    }

    public function visitNullComparisonResult(NullComparisonResult $result)
    {
        $this->buffer->add(true, $this->formatAsRow('null', 'null'), '   ');
    }

    public function visitCompoundResult(CompoundResult $result)
    {
        foreach ($result->getResults() as $result) {
            $visitor = $this->innerVisit($result);

            $result->accept($visitor);

            $this->buffer->addBuffer($visitor->getBuffer());
        }
    }

    /**
     * @return LineBuffer
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    public function visitArrayComparisonResult(ArrayComparisonResult $aResult)
    {
        $buffer = new LineBuffer();
        $this->buffer->add(true, '[', '');
        foreach ($aResult->getResults() as $result) {
            $visitor = $this->innerVisit($result);
            $result->accept($visitor);
            $this->buffer->addBuffer($visitor->getBuffer(), 3);
        }
        $this->buffer->add(true, ']', '');

        return (string)$buffer;
    }

    public function visitKeyValueComparisonResult(KeyValueComparisonResult $result)
    {
        $firstPrefix = $this->padToWidth(
            $result->getKey(),
            $this->indentFieldWidth - strlen(self::MAP_TO)
        )
            . $result->mapString();
        $visitor = $this->innerVisit($result->getResult());

        $result->getResult()->accept($visitor);
        $this->buffer->addBuffer($visitor->buffer, strlen($firstPrefix), $firstPrefix);
    }

    public function visitRightValueMissingResult(RightValueMissingResult $result)
    {
        $this->buffer->add(
            $result->getMatched(),
            $this->formatAsRow($result->getLeft(), ComparisonResult::NOT_PRESENT),
            RightValueMissingResult::getMarker()
        );
    }

    public function visitLeftValueMissingResult(LeftValueMissingResult $result)
    {
        $this->buffer->add(
            $result->getMatched(),
            $this->formatAsRow(ComparisonResult::NOT_PRESENT, $result->getRight()),
            LeftValueMissingResult::getMarker()
        );
    }

    public function visitMismatchedTypesResult(MismatchedTypesResult $result)
    {
        $this->buffer->add(
            false,
            $this->formatAsRow(
                ComparisonResult::typeAndValue($result->getLeft()),
                ComparisonResult::typeAndValue($result->getRight())
            ),
            MismatchedTypesResult::getMarker()
        );
    }

    public function visitObjectComparisonResult(ObjectComparisonResult $aResult)
    {
        $buffer = new LineBuffer();
        $this->buffer->add(true, $aResult->getClassName() . ' {', '');
        foreach ($aResult->getResults() as $result) {
            $visitor = $this->innerVisit($result);
            $result->accept($visitor);
            $this->buffer->addBuffer($visitor->getBuffer(), 3);
        }
        $this->buffer->add(true, '}', '');

        return (string)$buffer;
    }

    private function padToWidth($value, $width)
    {
        $outArray = $this->padToWidthArray($value, $width);
        return implode("\n", $outArray);
    }

    public function defaultVisit(ComparisonResult $result)
    {
        echo "No visitor defined for " . gettype($result) . "\n";
    }

    /**
     * @param $leftField
     * @param $rightField
     * @param string $prefixFirst
     * @param int $prefixWidth
     * @return string
     */
    public function formatAsRow($leftField, $rightField, $prefixFirst = '', $prefixWidth = 0)
    {
        $prefix = $this->padToWidth($prefixFirst, $prefixWidth);
        $prefixOther = $this->padToWidth('', $prefixWidth);
        $leftText = $this->padToWidthArray($leftField, $this->leftFieldWidth);
        $rightText = $this->padToWidthArray($rightField, $this->rightFieldWidth);
        $maxIndex = max(count($leftText), count($rightText));
        $out = [];
        for ($idx = 0; $idx < $maxIndex; $idx++) {
            $lValue = $this->getPartialLine($leftText, $idx, $this->leftFieldWidth);
            $rValue = $this->getPartialLine($rightText, $idx, $this->rightFieldWidth);
            $out[] = ($idx == 0 ? $prefix : $prefixOther) . "| " . $lValue . "| " . $rValue;
        }
        return implode("\n", $out);
    }

    /**
     * @param $prefix
     * @param $leftField
     * @param $rightField
     * @return string
     */
    public function formatAsRowWithPrefix($prefix, $leftField, $rightField)
    {
        return $this->formatAsRow($leftField, $rightField, $prefix, $this->indentFieldWidth);
    }

    /**
     * @param boolean $matched
     * @param string $main
     * @param string $marker
     *
     * @return mixed
     */
    public function addLine($matched, $main, $marker = '>>>')
    {
        $this->buffer->add($matched, $main, $marker);
    }

    /**
     * @param ComparisonResult $result
     */
    public function measureResult(ComparisonResult $result)
    {
        $measuringVisitor = new MeasuringVisitor();
        $result->accept($measuringVisitor);

        $this->setLeftFieldWidth($measuringVisitor->getLeftWidth() + 1);
        $this->setRightFieldWidth($measuringVisitor->getRightWidth() + 1);
        $this->setIndentFieldWidth($measuringVisitor->getIndentWidth());
    }

    /**
     * @param int $width
     */
    public function setLeftFieldWidth($width)
    {
        $this->leftFieldWidth = max($this->leftFieldWidth, $width);
    }

    /**
     * @param int $width
     */
    public function setRightFieldWidth($width)
    {
        $this->rightFieldWidth = max($this->rightFieldWidth, $width);
    }

    /**
     * @param $result
     * @return FormattingVisitor
     */
    public function innerVisit($result)
    {
        $visitor = new FormattingVisitor($result);
        $visitor->setWidthsFrom($this);

        return $visitor;
    }

    /**
     * @param  ComparisonResult $result
     * @param  int $extra
     * @return string
     */
    protected function makeIndent(ComparisonResult $result, $extra)
    {
        return $this->pad($extra + $result->getDepth() * self::INDENT);
    }

    /**
     * @param $repetitions
     * @return string
     */
    protected function pad($repetitions)
    {
        return str_repeat(self::PAD_CHAR, $repetitions);
    }

    /**
     * @param  ScalarComparisonResult $result
     * @return string
     */
    protected function makeMarker(ScalarComparisonResult $result)
    {
        return $this->pad($result->getPos()) . "^";
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return (string)$this->buffer;
    }

    /**
     * @param $value
     * @param $width
     * @return array
     */
    private function padToWidthArray($value, $width)
    {
        if (is_array($value)) {
            $asTextArray = explode("\n", print_r($value, true));
            $outArray = [];
            foreach ($asTextArray as $line) {
                $outArray[] = $this->padToWidth($line, $width);
            }
            return $outArray;
        } else {
            $excess = $width - strlen($value);
            if ($excess < 0) {
                $excess = 0;
            }

            $outArray = [$value . $this->pad($excess)];
            return $outArray;
        }
    }

    /**
     * @param $text
     * @param $idx
     * @param $width
     * @return mixed
     */
    private function getPartialLine(array $text, $idx, $width)
    {
        if (!isset($text[$idx])) {
            return $this->padToWidth('', $width);
        } else {
            return $text[$idx];
        }
    }
}
