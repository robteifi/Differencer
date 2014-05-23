<?php
require_once './vendor/autoload.php';

use RobTeifi\Differencer\FormattingDifferencer;

new FormattingDifferencer('Booleans that should match(false)', false, false, true);
new FormattingDifferencer('Booleans that should match(true)', true, true, true);
new FormattingDifferencer('Booleans that should not match(true,false)', true, false, false);
new FormattingDifferencer('Booleans that should not match(false, true)', false, true, false);

$s1 = "Now is the winter of our discontent";
$s2 = "Now is the winter of our discontent, made glorious...";

new FormattingDifferencer('Strings that match', $s1, $s1, true);
new FormattingDifferencer('Strings that don\'t match', $s1, $s2, false);

new FormattingDifferencer('Numbers that match', 1000, 1000, true);
new FormattingDifferencer('Numbers that don\'t match', 1000, 1010, false);

$a1 = ['a' => 'abc', 'val' => $s1];
$a2 = ['a' => 'abc', 'val' => $s2];
new FormattingDifferencer('Arrays that match', $a1, $a1, true);
new FormattingDifferencer('Arrays that don\'t match', $a1, $a2, false);
new FormattingDifferencer('Arrays that don\'t match key mismatch', ['abc' => 'def'], ['ghi' => 'jkl'], false);


new FormattingDifferencer('2D Arrays that don\'t match', [
    'abc' =>
        [
            'def' => 'xyz',
            'ghi' => 1354
        ]
], [
    'abc' =>
        [
            'def' => 'jkl',
            'ghi' => '1354'
        ]
], false);

new FormattingDifferencer('2D Arrays that don\'t match (key mismatch)', [
    'abc' =>
        [
            'def' => 'ghi',
            'ghi' => 1354
        ]
], [
    'abc' =>
        [
            'def' => 'jkl'
        ],
    'ghi' => '1354'
], false);

$arr1 = [
    'first' => 'Robert',
    'last'  => 'Heyes',
    'address' => [
        'line1' => '2, Riverside Mews',
        'line2' => 'St Mary Street',
        'town' => 'Cardigan',
        'county' => 'Ceredigion',
        'postcode' => 'SA43 1DH'
    ],
    'phone' => "613105",
    'float' => 789.34578
];

$arr2 = $arr1;
$arr2['address']['town'] = 'Cardiff';
$arr2['last'] = null ;
$arr2['first'] = 'Rob';
$arr2['phone'] = 613015;
$arr2['float'] = 789.34579;
unset($arr2['address']['county']);
new FormattingDifferencer('2D Arrays that match', $arr1, $arr1, true, 0.00000001);
new FormattingDifferencer('2D Arrays that don\'t match (key mismatch)', $arr1, $arr2, false, 0.00000001);

$arr3 = $arr1 ;
unset($arr3['address']);
new FormattingDifferencer('Arrays that don\'t match (missing array)', $arr1, $arr3, false, 0.00001);
new FormattingDifferencer('Arrays that don\'t match (missing array)', $arr3, $arr1, false, 0.00001);
new FormattingDifferencer('Arrays that should match with nulls', ['a' => null], ['a' => null], true);

new FormattingDifferencer('Empty arrays that should match', [], [], true);
