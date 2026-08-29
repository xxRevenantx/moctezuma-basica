<?php

return [
    /*
     | La Ley General de Educación establece 3 años para preescolar y
     | 6 años para primaria, cumplidos al 31 de diciembre del año de inicio
     | del ciclo escolar. A partir de esa base se usa la progresión anual
     | esperada por grado para control escolar interno.
     */
    'corte_escolar' => '12-31',

    /*
     | El Formato 911 de inicio contabiliza la matrícula (altas y bajas)
     | hasta el 30 de septiembre. La edad del alumnado se distribuye con los
     | años cumplidos al 1 de septiembre. Ambos cortes se mantienen separados
     | y configurables para evitar mezclar control escolar con estadística.
     */
    'corte_911' => '09-30',
    'corte_edad_911' => '09-01',

    'edades_esperadas' => [
        'preescolar' => [
            1 => 3,
            2 => 4,
            3 => 5,
        ],
        'primaria' => [
            1 => 6,
            2 => 7,
            3 => 8,
            4 => 9,
            5 => 10,
            6 => 11,
        ],
        'secundaria' => [
            1 => 12,
            2 => 13,
            3 => 14,
        ],
        'bachillerato' => [
            1 => 15,
            2 => 16,
            3 => 17,
        ],
    ],

    'niveles_basica' => ['preescolar', 'primaria', 'secundaria'],
];
