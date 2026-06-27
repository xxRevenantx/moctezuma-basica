<?php

use App\Support\PromedioExcel;

test('el promedio oficial de primaria reproduce el formato SEP', function () {
    $lenguajes = PromedioExcel::truncar(PromedioExcel::calcular([10, 9, 10]), 1);
    $saberes = PromedioExcel::truncar(PromedioExcel::calcular([9, 9, 9]), 1);
    $etica = PromedioExcel::truncar(PromedioExcel::calcular([9, 10, 9]), 1);
    $humano = PromedioExcel::truncar(PromedioExcel::calcular([9, 10, 10]), 1);

    expect($lenguajes)->toBe(9.6)
        ->and($saberes)->toBe(9.0)
        ->and($etica)->toBe(9.3)
        ->and($humano)->toBe(9.6);

    $promedioGrado = PromedioExcel::truncar(
        PromedioExcel::calcular([$lenguajes, $saberes, $etica, $humano]),
        1
    );

    expect($promedioGrado)->toBe(9.3);
});

test('los promedios internos conservan precision antes de presentarse', function () {
    $promedio = PromedioExcel::calcular([8.909090, 9.000000, 8.545454]);

    expect(PromedioExcel::formatear($promedio, 1))->toBe('8.8');
});
