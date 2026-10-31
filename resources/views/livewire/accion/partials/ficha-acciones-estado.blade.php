@if ($estadoFicha === 'finalizada')
    @unless ($esProfesor)
        <flux:button type="button" size="sm" variant="outline" icon="lock-open" wire:click="reabrirFicha({{ $alumno->id }})" wire:confirm="¿Reabrir esta ficha finalizada para permitir correcciones?">Reabrir</flux:button>
    @endunless
@elseif ($cantidadCapturada === count($campos))
    @if ($estadoFicha === 'en_revision')
        @unless ($esProfesor)
            <flux:button type="button" size="sm" variant="outline" icon="shield-check" wire:click="finalizarFicha({{ $alumno->id }})" wire:confirm="¿Validar y finalizar la ficha de este alumno?">Finalizar</flux:button>
        @endunless
    @else
        <flux:button type="button" size="sm" variant="outline" icon="clipboard-document-check" wire:click="enviarRevision({{ $alumno->id }})" wire:confirm="¿Enviar esta ficha completa a revisión?">Enviar a revisión</flux:button>
    @endif
@endif
