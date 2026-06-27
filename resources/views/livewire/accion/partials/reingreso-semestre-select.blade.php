<flux:select wire:model.live="semestre_destino_id" label="Semestre">
    <option value="">Selecciona</option>
    @foreach ($this->semestres as $item)
        <option value="{{ $item->id }}">Semestre {{ $item->numero }}</option>
    @endforeach
</flux:select>
