<?php

return [
    'characters' => ['2', '3', '4', '6', '7', '8', '9', 'a', 'c', 'd', 'e', 'h', 'm', 'n', 'p', 'r', 't', 'u', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'M', 'N', 'P', 'Q', 'R', 'T', 'U', 'X', 'Y', 'Z'],
    'default' => [
        'length' => 4,
        'width' => 80,
        'height' => 35,
        'quality' => 90,
        'lines' => 6,
        'blur' => 1,
        'math' => false,
        'expire' => 60,
        'encrypt' => true,
    ],
    'math' => [
        'length' => 9,
        'width' => 120,
        'height' => 35,
        'quality' => 90,
        'math' => true,
    ],

    'flat' => [
        'length' => 4,
        'width' => 80,
        'height' => 35,
        'quality' => 90,
        'lines' => 4,
        'blur' => 2,
        'bgImage' => false,
        'bgColor' => '#ecf2f4',
        'fontColors' => ['#2c3e50', '#c0392b', '#16a085', '#c0392b', '#8e44ad', '#303f9f', '#f57c00', '#795548'],
        'contrast' => -1,
    ],
    'mini' => [
        'length' => 3,
        'width' => 60,
        'height' => 32,
    ],
    'inverse' => [
        'length' => 4,
        'width' => 80,
        'height' => 35,
        'quality' => 90,
//        'sensitive' => false,
        'angle' => 42,
        'sharpen' => 10,
        'blur' => 1,
        'invert' => true,
        'contrast' => -1,
    ]
];
