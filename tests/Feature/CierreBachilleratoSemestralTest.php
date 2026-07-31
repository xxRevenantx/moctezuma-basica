<?php

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\MesesBachillerato;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Services\CierreGeneracionContinuidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->nivelBachillerato = Nivel::query()->create([
        'nombre' => 'Bachillerato',
        'slug' => 'bachillerato',
        'cct' => '12PBH0001A',
        'color' => '#006492',
    ]);

    $this->cicloActual = CicloEscolar::query()->create([
        'inicio_anio' => 2026,
        'fin_anio' => 2027,
        'es_actual' => true,
    ]);

    $this->cicloSiguiente = CicloEscolar::query()->create([
        'inicio_anio' => 2027,
        'fin_anio' => 2028,
        'es_actual' => false,
    ]);

    $this->generacion = Generacion::query()->create([
        'nivel_id' => $this->nivelBachillerato->id,
        'nombre' => '2026-2029',
        'anio_ingreso' => 2026,
        'anio_egreso' => 2029,
        'status' => true,
        'estado_cierre' => 'activa',
    ]);

    $mesPrimero = MesesBachillerato::query()->create([
        'meses' => 'Agosto - Enero',
        'meses_corto' => 'Ago-Ene',
    ]);
    $mesSegundo = MesesBachillerato::query()->create([
        'meses' => 'Febrero - Julio',
        'meses_corto' => 'Feb-Jul',
    ]);

    $this->semestres = collect();
    foreach ([1 => 'Primer grado', 2 => 'Segundo grado', 3 => 'Tercer grado'] as $numeroGrado => $nombreGrado) {
        $grado = Grado::query()->create([
            'nivel_id' => $this->nivelBachillerato->id,
            'nombre' => $nombreGrado,
            'slug' => 'bachillerato-'.$numeroGrado,
            'orden' => $numeroGrado,
        ]);

        $primerNumero = (($numeroGrado - 1) * 2) + 1;
        foreach ([$primerNumero, $primerNumero + 1] as $numeroSemestre) {
            $this->semestres->put($numeroSemestre, Semestre::query()->create([
                'grado_id' => $grado->id,
                'mes_id' => $numeroSemestre % 2 === 1 ? $mesPrimero->id : $mesSegundo->id,
                'numero' => $numeroSemestre,
                'orden_global' => $numeroSemestre,
            ]));
        }
    }
});

it('resuelve cada transición semestral de bachillerato en el ciclo correcto', function (
    int $semestreOrigen,
    ?int $semestreDestino,
    ?string $tipoCiclo,
    ?int $inicioCicloDestino,
    string $modo,
): void {
    $service = app(CierreGeneracionContinuidadService::class);
    $origen = $this->semestres->get($semestreOrigen);

    $destino = $service->destinoSugerido(
        $this->nivelBachillerato,
        $this->cicloActual,
        $origen->grado_id,
        $origen->id,
        $this->generacion->id,
    );

    expect($destino['modo'])->toBe($modo);

    if ($modo === 'egreso_terminal') {
        expect($destino['semestre_destino_id'])->toBeNull()
            ->and($destino['ciclo_destino_id'])->toBeNull()
            ->and($destino['nivel_destino_id'])->toBeNull();

        return;
    }

    $semestreCalculado = Semestre::query()->find($destino['semestre_destino_id']);
    $cicloCalculado = CicloEscolar::query()->find($destino['ciclo_destino_id']);
    $regla = $service->reglaCicloDestino(
        $this->nivelBachillerato,
        $this->cicloActual,
        $modo,
        $origen->id,
        $semestreCalculado?->id,
    );

    expect($semestreCalculado?->numero)->toBe($semestreDestino)
        ->and($regla['tipo'])->toBe($tipoCiclo)
        ->and((int) $cicloCalculado?->inicio_anio)->toBe($inicioCicloDestino)
        ->and($destino['generacion_destino_id'])->toBe($this->generacion->id);
})->with([
    '1.º a 2.º permanece en 2026-2027' => [1, 2, 'mismo_ciclo', 2026, 'promocion_grado'],
    '2.º a 3.º avanza a 2027-2028' => [2, 3, 'ciclo_consecutivo', 2027, 'promocion_grado'],
    '3.º a 4.º permanece en 2026-2027' => [3, 4, 'mismo_ciclo', 2026, 'promocion_grado'],
    '4.º a 5.º avanza a 2027-2028' => [4, 5, 'ciclo_consecutivo', 2027, 'promocion_grado'],
    '5.º a 6.º permanece en 2026-2027' => [5, 6, 'mismo_ciclo', 2026, 'promocion_grado'],
    '6.º concluye como egreso terminal' => [6, null, null, null, 'egreso_terminal'],
]);

it('bloquea la transición 2.º a 3.º cuando todavía no existe el ciclo consecutivo', function (): void {
    $this->cicloSiguiente->delete();
    $service = app(CierreGeneracionContinuidadService::class);
    $origen = $this->semestres->get(2);

    $destino = $service->destinoSugerido(
        $this->nivelBachillerato,
        $this->cicloActual,
        $origen->grado_id,
        $origen->id,
        $this->generacion->id,
    );
    $regla = $service->reglaCicloDestino(
        $this->nivelBachillerato,
        $this->cicloActual,
        'promocion_grado',
        $origen->id,
        $destino['semestre_destino_id'],
    );

    expect(Semestre::query()->find($destino['semestre_destino_id'])?->numero)->toBe(3)
        ->and($destino['ciclo_destino_id'])->toBeNull()
        ->and($regla['tipo'])->toBe('ciclo_consecutivo')
        ->and($regla['ciclo'])->toBeNull()
        ->and($regla['etiqueta'])->toBe('2027-2028');
});

it('solo ofrece el ciclo de origen para una transición semestral interna', function (): void {
    $service = app(CierreGeneracionContinuidadService::class);
    $origen = $this->semestres->get(3);
    $destino = $this->semestres->get(4);

    $permitidos = $service->ciclosDestinoPermitidos(
        $this->nivelBachillerato,
        $this->cicloActual,
        'promocion_grado',
        $origen->id,
        $destino->id,
    );

    expect($permitidos)->toHaveCount(1)
        ->and($permitidos->first()->is($this->cicloActual))->toBeTrue();
});

it('no aplica la regla de mismo ciclo a un nivel distinto de bachillerato', function (): void {
    $primaria = Nivel::query()->create([
        'nombre' => 'Primaria',
        'slug' => 'primaria',
        'cct' => '12PPR0001A',
        'color' => '#88AC2E',
    ]);

    $service = app(CierreGeneracionContinuidadService::class);
    $regla = $service->reglaCicloDestino(
        $primaria,
        $this->cicloActual,
        'promocion_grado',
    );

    expect($regla['tipo'])->toBe('ciclo_consecutivo')
        ->and($regla['ciclo_id'])->toBe($this->cicloSiguiente->id);
});
