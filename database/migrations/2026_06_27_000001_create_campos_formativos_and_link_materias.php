<?php

use App\Support\CampoFormativoClassifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('campos_formativos')) {
            Schema::create('campos_formativos', function (Blueprint $table): void {
                $table->id();
                $table->string('nombre', 150);
                $table->string('slug', 170)->unique();
                $table->string('color_fondo', 7)->default('#E2E8F0');
                $table->string('color_texto', 7)->default('#0F172A');
                $table->unsignedInteger('orden')->default(0);
                $table->boolean('activo')->default(true)->index();
                $table->timestamps();
            });
        }

        $ahora = now();
        $campos = [
            [
                'nombre' => 'Lenguajes',
                'slug' => 'lenguajes',
                'color_fondo' => '#F5D0FE',
                'color_texto' => '#701A75',
                'orden' => 1,
            ],
            [
                'nombre' => 'Saberes y pensamiento científico',
                'slug' => 'saberes-pensamiento-cientifico',
                'color_fondo' => '#BBF7D0',
                'color_texto' => '#14532D',
                'orden' => 2,
            ],
            [
                'nombre' => 'Ética, naturaleza y sociedades',
                'slug' => 'etica-naturaleza-sociedades',
                'color_fondo' => '#FED7AA',
                'color_texto' => '#7C2D12',
                'orden' => 3,
            ],
            [
                'nombre' => 'De lo humano y lo comunitario',
                'slug' => 'humano-comunitario',
                'color_fondo' => '#A5F3FC',
                'color_texto' => '#164E63',
                'orden' => 4,
            ],
            [
                'nombre' => 'Sin campo formativo',
                'slug' => 'sin-campo-formativo',
                'color_fondo' => '#E2E8F0',
                'color_texto' => '#334155',
                'orden' => 99,
            ],
        ];

        foreach ($campos as $campo) {
            DB::table('campos_formativos')->updateOrInsert(
                ['slug' => $campo['slug']],
                [...$campo, 'activo' => true, 'updated_at' => $ahora, 'created_at' => $ahora]
            );
        }

        if (! Schema::hasColumn('materias', 'campo_formativo_id')) {
            Schema::table('materias', function (Blueprint $table): void {
                $table->foreignId('campo_formativo_id')
                    ->nullable()
                    ->after('semestre_id')
                    ->constrained('campos_formativos')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }

        $ids = DB::table('campos_formativos')->pluck('id', 'slug');
        $sinCampoId = $ids->get('sin-campo-formativo');

        DB::table('materias')
            ->select(['id', 'materia'])
            ->whereNull('campo_formativo_id')
            ->orderBy('id')
            ->chunkById(200, function ($materias) use ($ids, $sinCampoId): void {
                foreach ($materias as $materia) {
                    $slugCampo = $this->sugerirCampo((string) $materia->materia);
                    $campoId = $ids->get($slugCampo, $sinCampoId);

                    DB::table('materias')
                        ->where('id', $materia->id)
                        ->update(['campo_formativo_id' => $campoId]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('materias', 'campo_formativo_id')) {
            Schema::table('materias', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('campo_formativo_id');
            });
        }

        Schema::dropIfExists('campos_formativos');
    }

    private function sugerirCampo(string $nombre): string
    {
        return CampoFormativoClassifier::sugerir($nombre);
    }

};
