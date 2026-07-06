<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('constancia_plantillas')
            ->where('clave', 'constancia_de_estudios')
            ->update([
                'contenido_html' => <<<'HTML'
<p><span style="font-family: arial, helvetica, sans-serif; font-size: 12pt;">&nbsp;&nbsp;&nbsp;@sexo <strong><span style="text-decoration: underline;">@nombre</span></strong>, con CURP: <strong>@curp</strong> y matrícula: <strong>@matricula</strong>, se encuentra <strong>@descripcion</strong> y cursando en el <strong>@grado&deg; grado de @nivel_minuscula</strong>, grupo: "<strong>@grupo</strong>", en esta institución educativa con Clave de Incorporación C.C.T: <strong>@cct</strong>, en el ciclo escolar <strong>2025-2026</strong>.</span></p>
HTML,
                'variables' => json_encode([
                    '@nombre',
                    '@curp',
                    '@grado',
                    '@nivel',
                    '@nivel_minuscula',
                    '@grupo',
                    '@generacion',
                    '@ciclo',
                    '@cct',
                    '@fecha',
                    '@dirigido',
                    '@sexo',
                    '@alumno',
                    '@matricula',
                    '@descripcion',
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);

        DB::table('constancia_plantillas')
            ->where('clave', 'estudios_termino')
            ->update([
                'contenido_html' => <<<'HTML'
<p><span style="font-family: arial, helvetica, sans-serif; font-size: 12pt;">&nbsp;&nbsp;&nbsp;@sexo <strong>@nombre</strong>, con CURP: <strong>@curp</strong> y matrícula <strong>@matricula</strong>, ha concluido satisfactoriamente sus estudios en el <strong>@grado&deg; grado de @nivel_minuscula</strong>, grupo: "<strong>@grupo</strong>", de la generación <strong>@generacion</strong>, durante el ciclo escolar <strong>2025-2026</strong> en esta institución con CCT: <strong>@cct</strong>.</span></p>
HTML,
                'variables' => json_encode([
                    '@sexo',
                    '@nombre',
                    '@alumno',
                    '@curp',
                    '@matricula',
                    '@grado',
                    '@nivel',
                    '@nivel_minuscula',
                    '@grupo',
                    '@generacion',
                    '@ciclo',
                    '@cct',
                    '@descripcion',
                    '@fecha',
                    '@dirigido',
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('constancia_plantillas')
            ->where('clave', 'constancia_de_estudios')
            ->update([
                'contenido_html' => <<<'HTML'
<p><span style="font-family: times new roman, times, serif;"><span style="font-size: 12pt;"> &nbsp;<span style="font-family: arial, helvetica, sans-serif;"> &nbsp;@sexo<strong> <span style="text-decoration: underline;">@nombre</span> </strong>con CURP: <strong>@curp </strong>y matrícula: <strong>@matricula</strong>, se encuentra <strong>@descripcion </strong>y cursando en el grado</span></span></span><span style="font-size: 12pt; font-family: arial, helvetica, sans-serif;"><span style="line-height: 107%;"> <strong>@grado&deg;</strong> de&nbsp;</span><strong>@nivel</strong>, grupo: <strong>@grupo,&nbsp; </strong>en esta institución educativa con Clave de Incorporación C.C.T: <strong>@cct</strong>, en el ciclo escolar 2025-2026<strong>.&nbsp;</strong></span></p>
HTML,
                'variables' => json_encode([
                    '@nombre',
                    '@curp',
                    '@grado',
                    '@nivel',
                    '@grupo',
                    '@generacion',
                    '@ciclo',
                    '@cct',
                    '@fecha',
                    '@dirigido',
                    '@sexo',
                    '@alumno',
                    '@matricula',
                    '@descripcion',
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);

        DB::table('constancia_plantillas')
            ->where('clave', 'estudios_termino')
            ->update([
                'contenido_html' => <<<'HTML'
<p><span style="font-size: 12pt;"><span style="font-family: arial, helvetica, sans-serif;">&nbsp; &nbsp; &nbsp; @sexo</span>&nbsp;<strong>@nombre</strong>, con CURP: <strong>@curp</strong> y matrícula <strong>@matricula</strong>, ha concluido satisfactoriamente sus estudios en el nivel <strong>@nivel</strong>, <strong>@grado&deg; </strong>grado, perteneciente al grupo "<strong>@grupo"</strong> de la generación <strong>@generacion</strong>, durante el ciclo escolar <strong>2025-2026</strong> en esta institución con CCT: <span style="font-size: 12pt; font-family: arial, helvetica, sans-serif;"><strong>@cct</strong></span></span></p>
HTML,
                'variables' => json_encode([
                    '@sexo',
                    '@nombre',
                    '@alumno',
                    '@curp',
                    '@matricula',
                    '@grado',
                    '@nivel',
                    '@grupo',
                    '@generacion',
                    '@ciclo',
                    '@cct',
                    '@descripcion',
                    '@fecha',
                    '@dirigido',
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }
};
