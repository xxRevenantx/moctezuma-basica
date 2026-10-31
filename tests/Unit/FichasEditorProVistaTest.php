<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FichasEditorProVistaTest extends TestCase
{
    public function test_editor_usa_modal_estable_y_script_de_livewire(): void
    {
        $vista = file_get_contents(__DIR__ . '/../../resources/views/livewire/accion/ficha.blade.php');

        self::assertIsString($vista);
        self::assertStringContainsString('wire:key="ficha-pro-modal-shell"', $vista);
        self::assertStringContainsString('x-show="$wire.modalAbierto"', $vista);
        self::assertStringContainsString('@script', $vista);
        self::assertStringContainsString('@endscript', $vista);
        self::assertStringContainsString("wire:click=\"abrirModal(", $vista);
        self::assertStringNotContainsString('wire:init="verificarGroq"', $vista);
        self::assertStringNotContainsString("document.addEventListener('livewire:init'", $vista);
    }
}
