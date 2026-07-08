<?php

namespace App\Livewire\Busqueda;

use App\Services\BusquedaGlobalService;
use Livewire\Attributes\On;
use Livewire\Component;

class BuscadorGlobal extends Component
{
    public bool $modalAbierto = false;

    public string $consulta = '';

    /** @var array<int, array<string, mixed>> */
    public array $categorias = [];

    public int $indiceActivo = 0;

    public bool $busquedaEjecutada = false;

    public ?string $mensajeError = null;

    #[On('abrir-buscador-global')]
    public function abrir(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->modalAbierto = true;
        $this->mensajeError = null;

        $this->dispatch('enfocar-buscador-global');
        $this->dispatch('buscador-global-abierto');
    }

    #[On('cerrar-buscador-global')]
    public function cerrar(): void
    {
        $this->modalAbierto = false;

        $this->reset([
            'consulta',
            'categorias',
            'indiceActivo',
            'busquedaEjecutada',
            'mensajeError',
        ]);

        $this->dispatch('buscador-global-cerrado');
    }

    public function limpiar(): void
    {
        $this->reset([
            'consulta',
            'categorias',
            'indiceActivo',
            'busquedaEjecutada',
            'mensajeError',
        ]);

        $this->dispatch('enfocar-buscador-global');
    }

    public function updatedConsulta(BusquedaGlobalService $servicio): void
    {
        $this->indiceActivo = 0;
        $this->mensajeError = null;

        $consulta = trim($this->consulta);

        if (mb_strlen($consulta) < 2) {
            $this->categorias = [];
            $this->busquedaEjecutada = false;

            return;
        }

        try {
            $categorias = $servicio->buscar($consulta, auth()->user());
            $indice = 0;

            foreach ($categorias as &$categoria) {
                foreach ($categoria['resultados'] as &$resultado) {
                    $resultado['indice'] = $indice++;
                }
                unset($resultado);
            }
            unset($categoria);

            $this->categorias = $categorias;
            $this->busquedaEjecutada = true;
        } catch (\Throwable $e) {
            report($e);

            $this->categorias = [];
            $this->busquedaEjecutada = true;
            $this->mensajeError = 'No fue posible completar la búsqueda. Revisa el registro de Laravel.';
        }
    }

    public function siguiente(): void
    {
        $total = $this->totalResultados();

        if ($total === 0) {
            return;
        }

        $this->indiceActivo = ($this->indiceActivo + 1) % $total;

        $this->dispatch('resultado-buscador-activo', indice: $this->indiceActivo);
    }

    public function anterior(): void
    {
        $total = $this->totalResultados();

        if ($total === 0) {
            return;
        }

        $this->indiceActivo = ($this->indiceActivo - 1 + $total) % $total;

        $this->dispatch('resultado-buscador-activo', indice: $this->indiceActivo);
    }

    public function seleccionar(int $indice): mixed
    {
        $resultado = $this->resultadoPorIndice($indice);

        if (!$resultado || blank($resultado['url'] ?? null)) {
            return null;
        }

        $url = (string) $resultado['url'];

        $this->cerrar();

        // Los módulos dinámicos por nivel se abren con una carga completa para
        // evitar que Livewire conserve una URL o filtros anteriores.
        return $this->redirect($url, navigate: false);
    }

    public function seleccionarActivo(): mixed
    {
        return $this->seleccionar($this->indiceActivo);
    }

    public function render()
    {
        return view('livewire.busqueda.buscador-global', [
            'totalResultados' => $this->totalResultados(),
        ]);
    }

    private function totalResultados(): int
    {
        return collect($this->categorias)
            ->sum(fn(array $categoria): int => count($categoria['resultados'] ?? []));
    }

    /** @return array<string, mixed>|null */
    private function resultadoPorIndice(int $indice): ?array
    {
        foreach ($this->categorias as $categoria) {
            foreach ($categoria['resultados'] ?? [] as $resultado) {
                if ((int) ($resultado['indice'] ?? -1) === $indice) {
                    return $resultado;
                }
            }
        }

        return null;
    }
}
