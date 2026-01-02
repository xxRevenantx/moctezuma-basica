<div>
    <flux:navlist>
        <flux:navlist.group :heading="__('Niveles')" expandable>
            @foreach ($niveles as $nivel)
                <flux:navlist.item icon="rectangle-stack"
                    :href="route('submodulos.accion', ['slug_nivel' => $nivel->slug, 'accion' => 'matricula'])"
                    :current="request()->segment(2) === $nivel->slug" wire:navigate>
                    {{ $nivel->nombre }}
                </flux:navlist.item>
            @endforeach
        </flux:navlist.group>
    </flux:navlist>
</div>
