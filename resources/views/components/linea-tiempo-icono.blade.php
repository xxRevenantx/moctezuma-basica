@props([
    'icono' => 'clock',
    'class' => 'h-5 w-5',
])

@switch($icono)
    @case('academic-cap')
        <flux:icon.academic-cap {{ $attributes->class($class) }} />
        @break
    @case('clipboard-document-check')
        <flux:icon.clipboard-document-check {{ $attributes->class($class) }} />
        @break
    @case('map-pin')
        <flux:icon.map-pin {{ $attributes->class($class) }} />
        @break
    @case('arrows-right-left')
        <flux:icon.arrows-right-left {{ $attributes->class($class) }} />
        @break
    @case('identification')
        <flux:icon.identification {{ $attributes->class($class) }} />
        @break
    @case('chart-bar-square')
        <flux:icon.chart-bar-square {{ $attributes->class($class) }} />
        @break
    @case('rectangle-stack')
        <flux:icon.rectangle-stack {{ $attributes->class($class) }} />
        @break
    @case('arrow-up-tray')
        <flux:icon.arrow-up-tray {{ $attributes->class($class) }} />
        @break
    @case('trophy')
        <flux:icon.trophy {{ $attributes->class($class) }} />
        @break
    @case('pencil-square')
        <flux:icon.pencil-square {{ $attributes->class($class) }} />
        @break
    @case('document-check')
        <flux:icon.document-check {{ $attributes->class($class) }} />
        @break
    @case('document-text')
        <flux:icon.document-text {{ $attributes->class($class) }} />
        @break
    @case('history')
        <flux:icon.history {{ $attributes->class($class) }} />
        @break
    @case('shield-check')
        <flux:icon.shield-check {{ $attributes->class($class) }} />
        @break
    @case('arrow-uturn-left')
        <flux:icon.arrow-uturn-left {{ $attributes->class($class) }} />
        @break
    @case('arrow-right')
        <flux:icon.arrow-right {{ $attributes->class($class) }} />
        @break
    @case('check-circle')
        <flux:icon.check-circle {{ $attributes->class($class) }} />
        @break
    @case('x-circle')
        <flux:icon.x-circle {{ $attributes->class($class) }} />
        @break
    @default
        <flux:icon.clock {{ $attributes->class($class) }} />
@endswitch
