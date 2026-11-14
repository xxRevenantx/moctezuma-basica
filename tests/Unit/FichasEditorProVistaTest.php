<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FichasEditorProVistaTest extends TestCase
{
    public function test_modal_mantiene_integridad_de_livewire_tres(): void
    {
        $vista = file_get_contents(__DIR__ . '/../../resources/views/livewire/accion/ficha.blade.php');

        self::assertIsString($vista);
        self::assertStringContainsString('wire:key="ficha-pro-modal-shell"', $vista);
        self::assertStringContainsString('x-show="$wire.modalAbierto"', $vista);
        self::assertStringContainsString('wire:ignore wire:key="ficha-tinymce-', $vista);
        self::assertStringContainsString('data-ficha-editor', $vista);
        self::assertStringContainsString('min-h-[370px]', $vista);
        self::assertStringContainsString('@script', $vista);
        self::assertStringContainsString('@endscript', $vista);
        self::assertStringContainsString("$" . "wire.on('abrir-modal-ficha'", $vista);
        self::assertStringNotContainsString('x-data="fichaEditorPro($wire)"', $vista);
        self::assertStringNotContainsString('window.cambiarCampoFicha', $vista);
    }

    public function test_alpine_recibe_urls_y_claves_mediante_atributos_html(): void
    {
        $vista = file_get_contents(__DIR__ . '/../../resources/views/livewire/accion/ficha.blade.php');

        self::assertIsString($vista);
        self::assertStringNotContainsString('@js(', $vista);
        self::assertSame(4, substr_count($vista, 'data-pdf-url="{{'));
        self::assertStringContainsString('data-campo="{{ $clave }}"', $vista);
        self::assertStringContainsString('$el.dataset.pdfUrl', $vista);
        self::assertStringContainsString('$el.dataset.campo', $vista);
        self::assertStringContainsString("$" . "dispatch('ficha-vista-pdf', { url: $" . "el.dataset.pdfUrl })", $vista);
    }

    public function test_tinymce_es_opcional_y_los_errores_son_visibles(): void
    {
        $vista = file_get_contents(__DIR__ . '/../../resources/views/livewire/accion/ficha.blade.php');
        $config = file_get_contents(__DIR__ . '/../../resources/views/components/head/tinymce-config.blade.php');

        self::assertStringContainsString('data-ficha-editor-error', $vista);
        self::assertStringContainsString('if (!window.tinymce)', $vista);
        self::assertStringContainsString('while (hayCambios ||', $vista);
        self::assertStringContainsString('revisionEnviada !== revision', $vista);
        self::assertStringContainsString('tinymce.api_key', $config);
        self::assertStringNotContainsString('fichas-editor-pro.js', $config);
    }
}
